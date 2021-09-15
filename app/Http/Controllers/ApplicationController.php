<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Requests;

use Log, DB, Validator, Auth, Setting, Exception;

use App\Helpers\Helper;

use App\VideoTape;

use App\User, App\Admin;

use App\Settings, App\Page;

use App\ChatMessage;

use App\LiveVideo;

use App\VodVideo;

use App\UserSubscription;

class ApplicationController extends Controller {

    /**
     * @method payment_failture()
     *      
     * @uses to show thw view page, whenever the payment failed.
     *
     * @created vidhya R
     *
     */

    public function payment_failure($error = "") {

        $paypal_error = \Session::get("paypal_error") ? \Session::get('paypal_error') : "";

        \Session::forget("paypal_error");

        // Redirect to angular payment failture page

        // @TODO Shobana please change this page to angular payment failure page 

        return redirect()->away(Setting::get('ANGULAR_SITE_URL'));

    }

    public function clear_cache() {
        
        $exitCode = \Artisan::call('config:cache');

        return back();
    }

    public function cron_delete_video() {
        
        Log::info('cron_delete_video');

        $admin = Admin::first();
        
        $timezone = 'Asia/Kolkata';

        if($admin) {

            if ($admin->timezone) {

                $timezone = $admin->timezone;

            } 

        }

        $date = convertTimeToUSERzone(date('Y-m-d H:i:s'), $timezone);

        $delete_hour = Setting::get('delete_video_hour');

        $less_than_date = date('Y-m-d H:i:s', strtotime($date." -{$delete_hour} hour"));

        $videos = LiveVideo::where('is_streaming' ,'=' ,DEFAULT_TRUE)
                        ->where('status' , 0)
                        ->where('created_at', '<=', $less_than_date)
                        ->get();

        foreach ($videos as $key => $video) {
            Log::info('Change the status');
            $video->status = 1;
            $video->save();
        }
    
    }

    public function send_notification_user_payment(Request $request) {

        Log::info("Notification to User for Payment");

        $time = date("Y-m-d");
        // Get provious provider availability data
        $query = "SELECT *, TIMESTAMPDIFF(SECOND, '$time',expiry_date) AS date_difference
                  FROM user_subscriptions";

        $payments = DB::select(DB::raw($query));

        Log::info(print_r($payments,true));

        if($payments) {

            foreach($payments as $payment){

                if($payment->date_difference <= 864000 && $payment->date_difference >= 0)
                {

                    // Delete provider availablity
                    Log::info('Send mail to user');

                    if($user = User::find($payment->user_id)) {

                        Log::info($user->email);

                        $email_data['subject'] = tr('subscription_remainder');

                        $email_data['page'] = "emails.payment-expiry";

                        $email_data['id'] = $user->id;

                        $email_data['name'] = $user->name;

                        $email_data['expiry_date'] = $payment->expiry_date;

                        $email_data['status'] = 0;

                        $email_data['email'] = $user->email;

                        $email_data['content'] = tr('subscription_expire_soon_email_content');

                        dispatch(new \App\Jobs\SendEmailJob($email_data));

                        \Log::info("Email".$result);
                    }
                }
            }
            Log::info("Notification to the User successfully....:-)");
        } else {
            Log::info(" records not found ....:-(");
        }
    
    }

    public function user_payment_expiry(Request $request) {

        Log::info("user_payment_expiry");

        $time = date("Y-m-d");
        // Get provious provider availability data
        $query = "SELECT *, TIMESTAMPDIFF(SECOND, '$time',expiry_date) AS date_difference
                  FROM user_subscriptions";

        $payments = DB::select(DB::raw($query));

        Log::info(print_r($payments, true));

        if($payments) {
            foreach($payments as $payment){
                if($payment->date_difference <= 0)
                {
                    // Delete provider availablity
                    Log::info('Send mail to user');

                    $email_data = array();
                    
                    if($user = User::find($payment->user_id)) {

                        $user->user_type = 0;

                        $user->save();

                        $email_data['subject'] = tr('payment_notification');

                        $email_data['page'] = "emails.payment-expiry";

                        $email_data['id'] = $user->id;

                        $email_data['name'] = $user->name;

                        $email_data['expiry_date'] = $payment->expiry_date;

                        $email_data['status'] = 1;

                        $email_data['email'] = $user->email;

                        dispatch(new \App\Jobs\SendEmailJob($email_data));

                        \Log::info("Email".$result);
                    }
                }
            }
            Log::info("Notification to the User successfully....:-)");
      
        } else {
      
            Log::info(" records not found ....:-(");
        }
    
    }

    public function save_admin_control(Request $request) {

        $model = Settings::get();

         $basicValidator = Validator::make(
                $request->all(),
                array(
                    'no_of_static_pages' => 'numeric|min:7|max:15',
                )
            );

            if($basicValidator->fails()) {

                $error_messages = implode(',', $basicValidator->messages()->all());

                return back()->with('flash_error', $error_messages);       

            } else {

        
                foreach ($model as $key => $value) {

                    if ($value->key == 'admin_delete_control') {
                        $value->value = $request->admin_delete_control;
                    } else if ($value->key == 'email_notification') {
                        $value->value = $request->email_notification;
                    } else if ($value->key == 'no_of_static_pages') {
                        $value->value = $request->no_of_static_pages;

                    } else if ($value->key == 'delete_video') {
                        $value->value = $request->delete_video;

                    } else if ($value->key == 'email_verify_control') {

                        if ($request->email_verify_control == 1) {

                            if(config('mail.username') &&  config('mail.password')) {

                                $value->value = $request->email_verify_control;

                            } else {

                                return back()->with('flash_error', tr('configure_smtp'));
                            }

                        }else {

                            $value->value = $request->email_verify_control;
                        }
                    } 
                    
                    $value->save();
                }

            }
        return back()->with('flash_success' , tr('settings_success'));
    }
   
    public function video_detail($id) {

        $video = LiveVideo::find($id);

        return view('admin.share')->with('video', $video);
    }

    /**
     * @method cron_vod_publish_video()
     *
     * @uses To publish a vod videos using cron
     
     * @created Shobana Chandrasekar
     *
     * @updated
     *
     * @param
     *
     * @return response of logs
     */
    public function cron_vod_publish_video(Request $request) {
        
        Log::info('cron_publish_video');

        $admin = Admin::first();
        
        $timezone = 'Asia/Kolkata';

        if($admin) {

            if ($admin->timezone) {

                $timezone = $admin->timezone;

            } 

        }

        $date = convertTimeToUSERzone(date('Y-m-d H:i:s'), $timezone);

        $videos = VodVideo::where('publish_time' ,'<=' ,$date)
                        ->where('publish_status' , VIDEO_NOT_YET_PUBLISHED)
                        ->get();
        foreach ($videos as $key => $video) {

            Log::info('Change the status');

            $video->publish_status = VIDEO_PUBLISHED;

            $video->save();
            
        }
    
    }

    /**
     * @method automatic_renewal()
     *
     * @uses to change the paid user to normal user based on the expiry date
     *
     * @created SHOBANA C 
     *
     * @updated 
     *
     * @param -
     *
     * @return JSON RESPONSE
     */

    public function automatic_renewal() {

        $current_time = date("Y-m-d H:i:s");


        $datas = UserSubscription::select(DB::raw('max(user_payments.id) as user_payment_id'),'user_payments.*')
                        ->leftjoin('subscriptions', 'subscriptions.id','=' ,'subscription_id')
                        ->where('subscriptions.amount', '>', 0)
                        ->where('user_payments.status', PAID_STATUS)
                        ->groupBy('user_payments.user_id')
                        ->orderBy('user_payments.created_at' , 'desc')
                        ->get();

        if($datas) {

            $total_renewed = 0;

            $s_data = $data = [];

            foreach($datas as $data){

                $payment = UserSubscription::find($data->user_payment_id);

                if ($payment) {

                    if ($payment->is_cancelled == AUTORENEWAL_ENABLED) {
                        // Check the pending payments expiry date

                        if(strtotime($payment->expiry_date) <= strtotime($current_time)) {

                            // Delete provider availablity

                            Log::info('Send mail to user');

                            $email_data = array();
                            
                            if($user_details = User::find($payment->user_id)) {

                                Log::info("the User exists....:-)");

                                $check_card_exists = User::where('users.id' , $payment->user_id)
                                                ->leftJoin('cards' , 'users.id','=','cards.user_id')
                                                ->where('cards.id' , $payment->card_id)
                                                ->where('cards.is_default' , DEFAULT_TRUE);

                                if($check_card_exists->count() != 0) {

                                    $user_card = $check_card_exists->first();
                                
                                    $subscription = Subscription::find($payment->subscription_id);

                                    if ($subscription) {

                                        $stripe_secret_key = Setting::get('stripe_secret_key');

                                        $customer_id = $user_card->customer_id;

                                        if($stripe_secret_key) {

                                            \Stripe\Stripe::setApiKey($stripe_secret_key);


                                        } else {

                                            Log::info(Helper::get_error_message(902));
                                        }

                                        $total = $subscription->amount;

                                        try {

                                            $user_charge =  \Stripe\Charge::create(array(
                                                "amount" => $total * 100,
                                                "currency" => "usd",
                                                "customer" => $customer_id,
                                            ));

                                           $payment_id = $user_charge->id;
                                           $amount = $user_charge->amount/100;
                                           $paid_status = $user_charge->paid;

                                            if($paid_status) {

                                                $previous_payment = UserPayment::where('user_id' , $payment->user_id)
                                                    ->where('status', DEFAULT_TRUE)->orderBy('created_at', 'desc')->first();

                                                $user_payment = new UserPayment;

                                                if($previous_payment) {

                                                    $expiry_date = $previous_payment->expiry_date;

                                                    $user_payment->expiry_date = date('Y-m-d H:i:s', strtotime($expiry_date. "+".$subscription->plan." months"));

                                                } else {
                                                    
                                                    $user_payment->expiry_date = date('Y-m-d H:i:s',strtotime("+".$subscription->plan." months"));
                                                }

                                                $user_payment->payment_id  = $payment_id;

                                                $user_payment->user_id = $payment->user_id;

                                                $user_payment->subscription_id = $subscription->id;

                                                $user_payment->status = 1;

                                               // $user_payment->from_auto_renewed = 1;

                                                $user_payment->amount = $amount;

                                                if ($user_payment->save()) {

                                                    $user_details->user_type = 1;
                                                    
                                                    $user_details->expiry_date = $user_payment->expiry_date;

                                                    $user_details->save();
                                                
                                                    Log::info(tr('payment_success'));

                                                    $total_renewed = $total_renewed + 1;

                                                } else {

                                                    Log::info(Helper::get_error_message(902));

                                                }

                                            } else {

                                               Log::info(Helper::get_error_message(903));

                                            }

                                        
                                        } catch(\Stripe\Error\RateLimit $e) {

                                            $error_message = $e->getMessage();

                                            $error_code = $e->getCode();

                                            $response_array = ['success'=>false, 'error_messages'=> $error_message , 'error_code' => $error_code];

                                            $pending_payment_details->reason_auto_renewal_cancel = $error_message;

                                            $pending_payment_details->save();

                                            Log::info("response array".print_r($response_array , true));

                                        } catch(\Stripe\Error\Card $e) {

                                            $error_message = $e->getMessage();

                                            $error_code = $e->getCode();

                                            $response_array = ['success'=>false, 'error_messages'=> $error_message , 'error_code' => $error_code];

                                            $pending_payment_details->reason_auto_renewal_cancel = $error_message;

                                            $pending_payment_details->save();

                                            $user_details->user_type = 0;

                                            $user_details->user_type_change_by = "AUTO-RENEW-PAYMENT-ERROR";
                                            
                                            $user_details->save();

                                            Log::info("response array".print_r($response_array , true));

                                        } catch (\Stripe\Error\InvalidRequest $e) {
                                            // Invalid parameters were supplied to Stripe's API
                                           
                                            $error_message = $e->getMessage();

                                            $error_code = $e->getCode();

                                            $response_array = ['success'=>false, 'error_messages'=> $error_message , 'error_code' => $error_code];

                                            $pending_payment_details->reason_auto_renewal_cancel = $error_message;

                                            $pending_payment_details->save();

                                            $user_details->user_type = 0;

                                            $user_details->user_type_change_by = "AUTO-RENEW-PAYMENT-ERROR";
                                            
                                            $user_details->save();


                                            Log::info("response array".print_r($response_array , true));

                                        } catch (\Stripe\Error\Authentication $e) {

                                            // Authentication with Stripe's API failed

                                            $error_message = $e->getMessage();

                                            $error_code = $e->getCode();

                                            $response_array = ['success'=>false, 'error_messages'=> $error_message , 'error_code' => $error_code];

                                            $pending_payment_details->reason_auto_renewal_cancel = $error_message;

                                            $pending_payment_details->save();

                                            $user_details->user_type = 0;

                                            $user_details->user_type_change_by = "AUTO-RENEW-PAYMENT-ERROR";
                                            
                                            $user_details->save();

                                            Log::info("response array".print_r($response_array , true));

                                        } catch (\Stripe\Error\ApiConnection $e) {

                                            // Network communication with Stripe failed

                                            $error_message = $e->getMessage();

                                            $error_code = $e->getCode();

                                            $response_array = ['success'=>false, 'error_messages'=> $error_message , 'error_code' => $error_code];

                                            $pending_payment_details->reason_auto_renewal_cancel = $error_message;

                                            $pending_payment_details->save();

                                            $user_details->user_type = 0;

                                            $user_details->user_type_change_by = "AUTO-RENEW-PAYMENT-ERROR";
                                            
                                            $user_details->save();

                                            Log::info("response array".print_r($response_array , true));

                                        } catch (\Stripe\Error\Base $e) {
                                          // Display a very generic error to the user, and maybe send
                                            
                                            $error_message = $e->getMessage();

                                            $error_code = $e->getCode();

                                            $response_array = ['success'=>false, 'error_messages'=> $error_message , 'error_code' => $error_code];

                                            $pending_payment_details->reason_auto_renewal_cancel = $error_message;

                                            $pending_payment_details->save();

                                            $user_details->user_type = 0;

                                            $user_details->user_type_change_by = "AUTO-RENEW-PAYMENT-ERROR";
                                            
                                            $user_details->save();

                                            Log::info("response array".print_r($response_array , true));

                                        } catch (Exception $e) {
                                            // Something else happened, completely unrelated to Stripe

                                            $error_message = $e->getMessage();

                                            $error_code = $e->getCode();

                                            $response_array = ['success'=>false, 'error_messages'=> $error_message , 'error_code' => $error_code];

                                            $pending_payment_details->reason_auto_renewal_cancel = $error_message;

                                            $pending_payment_details->save();

                                            $user_details->user_type = 0;

                                            $user_details->user_type_change_by = "AUTO-RENEW-PAYMENT-ERROR";
                                            
                                            $user_details->save();

                                            Log::info("response array".print_r($response_array , true));
                                       
                                        }

                                    }

                                    $email_data['subject'] = tr('automatic_renewal_notification');

                                    $email_data['page'] = "emails.automatic-renewal";

                                    $email_data['id'] = $user_details->id;

                                    $email_data['username'] = $user_details->name;

                                    $email_data['expiry_date'] = $payment->expiry_date;

                                    $email_data['status'] = 1;

                                    $email_data['email'] = $user_details->email;

                                    dispatch(new \App\Jobs\SendEmailJob($email_data));

                                    // \Log::info("Email".$result);

                                } else {

                                   /* $payment->reason = "NO CARD";

                                    $payment->save();*/

                                    $user_details->user_type = 0;

                                    // $user_details->user_type_change_by = "AUTO-RENEW-NO-CARD";
                                    
                                    $user_details->save();

                                    Log::info("No card available....:-)");

                                }
                           
                            }

                            $data['user_payment_id'] = $payment->id;

                            $data['user_id'] = $payment->user_id;

                            array_push($s_data , $data);
                        }

                    } else {

                        Log::info("No payment found....:-) ".$data->id);
                    }
                } else {

                    Log::info("No payment found....:-) ".$data->user_payment_id);

                }
                            
            
            }
            
            Log::info("Notification to the User successfully....:-)");

            $response_array = ['success' => true, 'total_renewed' => $total_renewed , 'data' => $s_data];

            return response()->json($response_array , 200);

        } else {

            Log::info(" records not found ....:-(");

            $response_array = ['success' => false , 'error_messages' => tr('no_pending_payments')];
        }

        return response()->json($response_array , 200);

    }

    /**
     * @method configuration_mobile()
     *
     * @uses to get the configurations for base products
     *
     * @created Vidhya R 
     *
     * @updated Vidhya R
     *
     * @param - 
     *
     * @return JSON Response
     */

    public function configuration_site(Request $request) {

        try {

            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:users,id',
                'token' => 'required',

            ]);

            if($validator->fails()) {

                $error = implode(',',$validator->messages()->all());

                throw new Exception($error, 101);

            } else {

                $config_data = $data = [];

                $payment_data['is_stripe'] = 1;

                $payment_data['stripe_publishable_key'] = Setting::get('stripe_publishable_key') ?: "";

                $payment_data['stripe_secret_key'] = Setting::get('stripe_secret_key') ?: "";

                $payment_data['stripe_secret_key'] = Setting::get('stripe_secret_key') ?: "";

                $payment_data['is_paypal'] = 1;

                $payment_data['PAYPAL_ID'] = envfile('PAYPAL_ID') ?: "";

                $payment_data['PAYPAL_SECRET'] = envfile('PAYPAL_SECRET') ?: "";

                $payment_data['PAYPAL_MODE'] = envfile('PAYPAL_MODE') ?: "sandbox";

                $data['payments'] = $payment_data;

                $data['urls']  = [];

                $url_data['base_url'] = envfile("APP_URL") ?: "";

                $url_data['socket_url'] = Setting::get("SOCKET_URL") ?: "";

                $url_data['chat_socket_url'] = Setting::get("chat_socket_url") ?: "";

                $url_data['live_url'] = Setting::get("live_url") ?: "";

                $data['urls'] = $url_data;

                $notification_data['FCM_SENDER_ID'] = "";

                $notification_data['FCM_SERVER_KEY'] = $notification_data['FCM_API_KEY'] = "";

                $notification_data['FCM_PROTOCOL'] = "";

                $data['notification'] = $notification_data;

                $data['site_name'] = Setting::get('site_name');

                $data['site_logo'] = Setting::get('site_logo');

                $data['currency'] = Setting::get('currency');

                // Streaming Keys

                $data['stream'] = [];

                $wowza['is_wowza'] = Setting::get('is_wowza_configured');

                $details['wowza_port_number'] = Setting::get('wowza_port_number');

                $details['wowza_app_name'] = Setting::get('wowza_app_name');

                $details['wowza_username'] = Setting::get('wowza_username');

                $details['wowza_password'] = Setting::get('wowza_password');

                $details['wowza_license_key'] = Setting::get('wowza_license_key');

                $details['wowza_ip_address'] = Setting::get('wowza_ip_address');

                $wowza['wowza'] = $details;

                $data['stream'] = $wowza;

                $response_array = ['success' => true , 'data' => $data];

                return response()->json($response_array , 200);

            }

        } catch(Exception $e) {

            $error_message = $e->getMessage();

            $response_array = ['success' => false,'error' => $error_message,'error_code' => 101];

            return response()->json($response_array , 200);

        }
   
    }

    public function user_payment_expiry_new(Request $request) {

        $current_time = date("Y-m-d H:i:s");

        $pending_payments = UserSubscription::leftJoin('users' , 'user_subscriptions.user_id' , '=' , 'users.id')
                                ->where('user_subscriptions.status' , 1)
                                ->where('user_subscriptions.expiry_date' ,"<=" , $current_time)
                                ->where('user_type' ,1)
                                ->get();

        if($pending_payments) {

            $count = 0;

            foreach($pending_payments as $pending_payment_details) {

                // Check expiry date one more time (Cross Verification)

                if(strtotime($pending_payment_details->expiry_date) <= strtotime($current_time)) {

                    // Delete User 

                    Log::info('Send mail to user');

                    $email_data = array();
                    
                    if($user_details = User::where('id' ,$pending_payment_details->user_id)->where('user_type' , 1)->first()) {

                        $user_details->user_type = 0;

                        // $user_details->user_type_change_by = "CRON";
                        
                        $user_details->save();

                        $count = $count +1;

                        if($user_details->status == 1 && $user_details->is_verified == 1) {

                            $email_data['subject'] = tr('payment_notification');

                            $email_data['page'] = "emails.payment-expiry";

                            $email_data['id'] = $user->id;

                            $email_data['name'] = $user->name;

                            $email_data['expiry_date'] = $payment->expiry_date;

                            $email_data['status'] = 1;

                            $email_data['email'] = $user->email;

                            $email_data['content'] = tr('notification_expired');

                            dispatch(new \App\Jobs\SendEmailJob($email_data));

                            \Log::info("Email".$result);
                        
                        }
                   
                    }
                
                }
            }

            Log::info("Notification to the User successfully....:-)");

            $response_array = ['success' => true , 'message' => "Notification to the User successfully....:-)" , 'count' => $count];

            return response()->json($response_array , 200);

        } else {

            Log::info("PAYMENT EXPIRY - Records Not Found ....:-(");

            $response_array = ['success' => false , 'error_messages' => "PAYMENT EXPIRY - Records Not Found ....."];

            return response()->json($response_array , 200);

        }
    
    }

        /**
     * @method demo_credential_cron()
     *
     * @uses To update demo login credentials.
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param  
     *
     * @return 
     */
    public function demo_credential_cron() {

        Log::info('Demo Credential CRON STARTED');

        try {
            
            DB::beginTransaction(); 

            $demo_admin = 'admin@streamnow.com';
            $admin_details = Admin::where('email' ,$demo_admin)->first();

            if(!$admin_details) {

                $admin_details->name = 'Admin';
                $admin_details->picture = "https://live.appswamy.com/images/default-profile.jpg";
                $admin_details->created_at = date('Y-m-d H:i:s');
                $admin_details->updated_at = date('Y-m-d H:i:s');
            }

            $admin_details->email = $demo_admin;            
            $admin_details->password = \Hash::make('123456');
            
            $demo_user = 'user@streamnow.com';
            $user_details = User::where('email' ,$demo_user)->first();
            
            if(!$user_details) {

                $user_details->name = 'User';
                $user_details->picture ="https://live.appswamy.com/images/default-profile.jpg";
                $user_details->chat_picture = "https://live.appswamy.com/images/default-profile.jpg";
                $user_details->is_verified = 1;
                $user_details->status = 1;
                $user_details->user_type = 1;
                $user_details->push_status = 1;
                $user_details->is_content_creator = 1;
                $user_details->role = 'model';
                $user_details->token = Helper::generate_token();
                $user_details->token_expiry = Helper::generate_token_expiry();
                $user_details->created_at = date('Y-m-d H:i:s');
                $user_details->updated_at = date('Y-m-d H:i:s');
            }

            $user_details->email = $demo_user;            
            $user_details->password = \Hash::make('123456'); 

            if( $user_details->save() && $admin_details->save()) {

                DB::commit();

            } else {

                throw new Exception("Demo Credential CRON - Credential Could not be updated", 101);                
            }
            
         } catch(Exception $e) {

            DB::rollback();

            $error = $e->getMessage();

            Log::info('Demo Credential CRON Error:'.print_r($error , true));

        }       
        
        Log::info('Demo Credential CRON END');

    }

    /**
     * @method video_tapes_auto_clear_cron()
     *
     * @uses To auto-clear videos uploaded
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param  
     *
     * @return 
     */
    public function video_tapes_auto_clear_cron() {

        Log::info('VideoTapes Auto-Clear Cron STARTED');

        try {
            
            $date = date('Y-m-d');

            DB::beginTransaction(); 

            if(VodVideo::where('created_by','!=',ADMIN)->whereDate('created_at','<', $date)->delete())
            {
                DB::commit();

                Log::info('VideoTapes Auto-Cleared');
            } 
                        
         } catch(Exception $e) {

            DB::rollback();

            $error = $e->getMessage();

            Log::info('VideoTapes Auto-Clear Cron Error:'.print_r($error , true));
        }       
        
        Log::info('VideoTapes Auto-Clear Cron END');

    }

    /**
     * @method static_pages_api()
     *
     * @uses used to get the pages
     *
     * @created Vidhya R 
     *
     * @edited Vidhya R
     *
     * @param - 
     *
     * @return JSON Response
     */

    public function static_pages_api(Request $request) {

        $base_query = Page::where('status' , APPROVED)->orderBy('title', 'asc')
                                ->select('id as page_id', 'unique_id', 'heading as title' , 'description','type as page_type', 'status' , 'created_at' , 'updated_at');
                                

        if($request->unique_id) {

            $static_pages = $base_query->where('unique_id' , $request->unique_id)->first();

        } else {

            $static_pages = $base_query->get();

        }

        $response_array = ['success' => true , 'data' => $static_pages ?? []];

        return response()->json($response_array , 200);

    }

    /**
     * @method users_unique_id_update()
     *
     * @uses used to get the pages
     *
     * @created Vidhya R 
     *
     * @edited Vidhya R
     *
     * @param - 
     *
     * @return JSON Response
     */

    public function users_unique_id_update(Request $request) {

        $total_users_updated = 0;

        $users = User::skip($request->skip)->take($request->take)->get();

        foreach ($users as $user) {

            $user->unique_id = routefreestring($user->name) ?? uniqid();

            $user->save();
            
        }

        $response = ['success' => true, 'total_users_updated' => $total_users_updated];

        return response()->json($response, 200);

    }

    public function get_settings_json() {
        
        if(\File::isDirectory(public_path(SETTINGS_JSON))){

        } else {

            \File::makeDirectory(public_path('default-json'), 0777, true, true);

            \App\Helpers\Helper::settings_generate_json();
        }

        $jsonString = file_get_contents(public_path(SETTINGS_JSON));

        $data = json_decode($jsonString, true);

        return $data;
    }

}