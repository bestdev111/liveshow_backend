<?php

namespace App\Repositories;

use Log, Validator, Setting;

use App\Repositories\CommonRepository as CommonRepo;

use App\Helpers\Helper;

use App\User, App\PayPerView, App\VodVideo;

class UserRepository {

    public static function request_validation($data = [] , &$errors = [] , $user) {

        $validator = Validator::make($data,
            array(
                'request_id' => 'required|integer|exists:requests,id,user_id,'.$user->id,
            ),
            array(
                'exists' => 'The :attribute doesn\'t belong to User:'.$user->name
            )
        );

        if($validator->fails()) {

            $errors = implode(',' ,$validator->messages()->all());

            return false;

        }

        return true;

    }

	public static function all() {

		return User::orderBy('created_at' , 'desc')->get();
		
	}

	public static function login($request) {

        $user = User::where('email', '=', $request->email)->first();

        // Validate the user credentials

        if($user->status) {

            if(\Hash::check($request->password, $user->password)) {

                // Setting::get('email_verify_control' , 1) && 

                if($user->is_verified) {

                	// Generate new tokens
                    // $user->token = ;
                    $user->token_expiry = Helper::generate_token_expiry();

                    // Save device details
                    $user->device_token = $request->device_token;
                    $user->device_type = $request->device_type;
                    $user->login_by = $request->login_by;

                    $user->timezone = $request->timezone;

                    $user->save();

                    $data = User::userResponse($user->id)->first();

                    if($data) {
                        $data = $data->toArray();
                    }

                    $response_array = ['success' => true , 'data' => $data];

                    \Auth::loginUsingId($user->id);
                
                } else {
                    
                    Helper::check_email_verification("" , $user, $error);
                    
                    $response_array = array( 'success' => false, 'error' => tr('account_verify'), 'error_code' => 105 );

                }

            } else {
                $response_array = array( 'success' => false, 'error' => tr('invalid_email_password'), 'error_code' => 105 );
            }
        
        } else {
            $response_array = array('success' => false , 'error' => tr('account_disabled'),'error_code' => 144);
        }

        return $response_array;
	}

	public static function forgot_password($data = [] , &$errors = []) {

		$action = User::where('email' , $data->email)->first();

		if($action) {

            if($action->login_by == 'manual') {

    			$new_password = Helper::generate_password();

    	        $action->password = \Hash::make($new_password);

                $email_data['subject'] = tr('forgot_email_title').' '.Setting::get('site_name');

                $email_data['page'] = "emails.user.forgot-password";

                $email_data['user']  = $action;

                $email_data['password'] = $new_password;

                $email_data['email'] = $action->email;

                dispatch(new SendEmailJob($email_data));

    	        $action->save();

                $response_array = ['success' => true , 'message' => tr('forgot_password_success')];

            } else {

                $response_array = ['success' => false , 'message' => tr('only_manual_user')];
            }

		} else {
			$response_array = ['success' => false , 'error' => tr('mail_not_found') , 'error_code' => 101];
		}
        return $response_array;
	}

	public static function store($request) {

		$user = new User;

        $user->name = $request->has('name') ? $request->name : "";

        
		$user->email = $request->has('email') ? $request->email : "";
		$user->password = $request->has('password') ? \Hash::make($request->password) : "";
        
		$user->gender = $request->has('gender') ? $request->gender : "male";
		$user->mobile = $request->has('mobile') ? $request->mobile : "";
        $user->address = $request->has('address') ? $request->address : "";
        $user->description = $request->has('description') ? $request->description : "";

		// $user->token = Helper::generate_token();
        $user->token_expiry = Helper::generate_token_expiry();

        $check_device_exist = User::where('device_token', $request->device_token)->first();

        if($check_device_exist){
            $check_device_exist->device_token = "";
            $check_device_exist->save();
        }

        if($request->has('timezone')) {
            $user->timezone = $request->timezone;
        }

        // $user->device_token = $request->has('device_token') ? $request->device_token : "";
        $user->device_type = $request->has('device_type') ? $request->device_type : "";
        $user->login_by = $request->has('login_by') ? $request->login_by : "";
		$user->social_unique_id = $request->has('social_unique_id') ? $request->social_unique_id : "";

        $user->picture = Helper::web_url().'/images/default-profile.jpg';
        $user->chat_picture = Helper::web_url().'/images/default-profile.jpg';

        $user->payment_mode = $request->payment_mode ? $request->payment_mode : 'cod';

        // Upload picture
        if($request->login_by == "manual") {
            if($request->hasFile('picture')) {
                $user->picture = Helper::upload_avatar('uploads/users',$request->file('picture'), $user);
            }
        } else {
            if($request->has('picture')) {
                $user->picture = $request->picture;
            }

        }
        $user->login_by = $request->login_by ?  $request->login_by : "manual";

        $user->status = 1;
        $user->payment_mode = 'cod';

        if(!Setting::get('email_verify_control')) {
            $user->is_verified = 1;
        }

        $user->save();

        $name = $request->has('name') ? str_replace(' ', '-', $request->name) : "";
        
        $user->unique_id = uniqid($name);

       // $user->login_status = "user";
        
        $user->register_type = "user";

        $user->save();

        $response = [];

        if($user) {

            
            $user->token = AppJwt::create(['id' => $user->id, 'email' => $user->email, 'role' => "model"]);

            $user->save();

            $response = User::userResponse($user->id)->first();

            if($response) {
                $response = $response->toArray();
            }

            register_mobile('web');

            $subject = tr('new_user_signup').' '.Setting::get('site_name');
            $page = "emails.user.welcome";
            $email = $user->email;

            \Log::info("Mail Send Start In User Create");
            
            // Helper::send_email($page,$subject,$email,$user);

            $email_data['subject'] = tr('new_user_signup').' '.Setting::get('site_name');

            $email_data['page'] = "emails.user.welcome";

            $email_data['email'] = $user->email;

            $email_data['data'] = $user;

            dispatch(new \App\Jobs\SendEmailJob($email_data));
        }

        // Send welcome email to the new user:

        return $response;
	}

	public static function update($request , $user_id) {

        if($request->id) {
            $user_id = $request->id;
        }

		$user = User::find($user_id);

        $response = [];

        if($user) {

            if($request->has('name')) {
                $user->name = $request->name;
            }

            if($request->has('email')) {
                $user->email = $request->email;
            }

            if($request->has('description')) {
                $user->description = $request->description;
            }

            if($request->has('gender')) {
                $user->gender = $request->gender;
            }

            if($request->has('mobile')) {
                $user->mobile = $request->mobile;
            }

            if($request->has('timezone')) {
                $user->timezone = $request->timezone;
            }

            // Upload picture
            if ($request->hasFile('picture')) {
                Helper::delete_avatar('uploads/users',$user->picture); // Delete the old pic
                $user->picture = Helper::upload_avatar('uploads/users',$request->file('picture'), $user);
            }

            // Upload picture
            if ($request->hasFile('cover')) {
                Helper::delete_avatar('uploads/users',$user->cover); // Delete the old pic
                $user->cover = Helper::upload_avatar('uploads/users',$request->file('cover'), 0);
            }

            $user->login_by = $request->login_by ?  $request->login_by : $user->login_by;
            $user->save();

            $response = $user->userResponse($user->id)->first()->toArray();
        }

        return $response;

	}

	public static function delete($data = []) {

	}

	public static function find($data = []) {

	}

	public static function findBy($field , $value) {

	}

	public static function paginate($take , $skip) {

	}

    /**
     * Function Name : pay_per_views_status_check
     *
     * To check the status of the pay per view in each video
     *
     * @created_by - Shobana Chandrasekar
     * 
     * @updated_by - - 
     *
     * @param object $request - Video related details, user related details
     *
     * @return response of success/failure response of datas
     */
    public static function pay_per_views_status_check($user_id, $user_type, $video_data) {

        // Check video details present or not

        if ($video_data) {

            // Check the video having ppv or not

            if ($video_data->is_pay_per_view) {

                $is_ppv_applied_for_user = DEFAULT_FALSE; // To check further steps , the user is applicable or not

                // Check Type of User, 1 - Normal User, 2 - Paid User, 3 - Both users

                switch ($video_data->type_of_user) {

                    case NORMAL_USER:
                        
                        if (!$user_type) {

                            $is_ppv_applied_for_user = DEFAULT_TRUE;
                        }

                        break;

                    case PAID_USER:
                        
                        if ($user_type) {

                            $is_ppv_applied_for_user = DEFAULT_TRUE;
                        }
                        
                        break;
                    
                    default:

                        // By default it will taks as Both Users

                        $is_ppv_applied_for_user = DEFAULT_TRUE;

                        break;
                }

                if ($is_ppv_applied_for_user) {

                    // Check the user already paid or not

                    $ppv_model = PayPerView::where('status', PAID_STATUS)
                        ->where('user_id', $user_id)
                        ->where('video_id', $video_data->vod_id)
                        ->orderBy('id','desc')
                        ->first();

                    $watch_video_free = NOT_YET_WATCHED;

                    if ($ppv_model) {

                        // Check the type of payment , based on that user will watch the video 

                        switch ($video_data->type_of_subscription) {

                            case ONE_TIME_PAYMENT:
                                
                                $watch_video_free = WATCHED;
                                
                                break;

                            case RECURRING_PAYMENT:

                                // If the video is recurring payment, then check the user already watched the paid video or not 
                                
                                if (!$ppv_model->is_watched) {

                                    $watch_video_free = WATCHED;
                                }
                                
                                break;
                            
                            default:

                                // By default it will taks as true

                                $watch_video_free = WATCHED;

                                break;
                        }

                        if ($watch_video_free) {

                            $response_array = ['success'=>true, 'message'=>Helper::get_message(128), 'code'=>128];

                        } else {

                            $response_array = ['success'=>false, 'message'=>Helper::get_message(129), 'code'=>129];

                        }

                    } else {

                        // 129 - User pay and watch the video

                        $response_array = ['success'=>false, 'message'=>Helper::get_message(129), 'code'=>129];
                    }

                } else {

                    $response_array = ['success'=>true, 'message'=>Helper::get_message(128), 'code'=>128];

                }

            } else {

                // 128 - User can watch the video
                
                $response_array = ['success'=>true, 'message'=>Helper::get_message(127), 'code'=>128];

            }

        } else {

            $response_array = ['success'=>false, 'error_messages'=>Helper::get_error_message(906), 
                'error_code'=>906];

        }

        return $response_array;
    
    }


    /**
     * @uses to store the PPV payment failure
     *
     * @param $user_id
     *
     * @param $admin_video_id
     *
     * @param $payment_id
     *
     * @param $reason
     *
     * @param $payment_id = After payment - if any configuration failture or timeout
     *
     * @return boolean response
     */

    public static function ppv_payment_failure_save($user_id = 0 , $admin_video_id = 0 , $reason = "" , $payment_id = "") {

        /*********** DON't REMOVE LOGS **************/

        // Log::info("1- Subscription ID".$subscription_id);

        // Log::info("2- USER ID".$user_id);
        
        // Log::info("3- MESSAGE ID".$reason);

        // Check the user_id and subscription id not null

        /************ AFTER user paid, if any configuration failture  or timeout *******/

        if($payment_id) {

            $ppv_payment_details = PayPerView::where('payment_id',$payment_id)->first();

            $ppv_payment_details->reason = "After_Payment"." - ".$reason;

            $ppv_payment_details->save();

            return true;

        }

        /************ Before user payment, if any configuration failture or TimeOut *******/

        if(!$user_id || !$admin_video_id) {

            Log::info('Payment failure save - USER ID and Subscription ID not found');

            return false;

        }

        $ppv_user_payment_details = PayPerView::where('user_id' , $user_id)->where('video_id' , $admin_video_id)->where('amount',0)->first();

        if(empty($ppv_user_payment_details)) {

            $ppv_user_payment_details = new PayPerView;

        }

        $ppv_user_payment_details->expiry_date = date('Y-m-d H:i:s');

        $ppv_user_payment_details->payment_id  = "Payment-Failed";

        $ppv_user_payment_details->user_id = $user_id;

        $ppv_user_payment_details->video_id = $admin_video_id;

        $ppv_user_payment_details->reason = "BEFORE-".$reason;

        // @todo 

        

        $ppv_user_payment_details->save();

        return true;
        

    }
    /**
     * @uses to store the payment with commission split 
     *
     * @param $admin_video_id
     *
     * @param $payperview_id
     *
     * @param $moderator_id
     * 
     * @return boolean response
     */

    public static function ppv_commission_split($admin_video_id = "" , $payperview_id = "" , $moderator_id = "") {

        if(!$admin_video_id || !$payperview_id) {

            return false;
        }

        /***************************************************
         *
         * commission details need to update in following sections 
         *
         * admin_videos table - how much earnings for particular video
         *
         * pay_per_views - On Payment how much commission has calculated 
         *
         * Moderator - If video uploaded_by moderator means need add commission amount to their redeems
         *
         ***************************************************/

        // Get the details

        $admin_video_details = VodVideo::find($admin_video_id);

        if(!$admin_video_details) {

            Log::info('ppv_commission_split - AdminVideo Not Found');

            return false;
        }

        $ppv_details = PayPerView::find($payperview_id);

        if(!$ppv_details) {

            Log::info('ppv_commission_split - PayPerView Not Found');

            return false;

        }

        $total = $ppv_details->amount;

        Log::info('ppv_commission - Total'.$total);

/*        $admin_amount = $total;

        $user_amount = 0;*/

        // Do commission split for moderator videos, otherwise the amount will go only admin 

       // if(is_numeric($admin_video_details->uploaded_by)) {

            // Commission split 
            
            $admin_commission = Setting::get('admin_vod_commission')/100;

            $admin_amount = $total * $admin_commission;

            $user_amount = $total - $admin_amount;

       // }
        // Update video earnings

        Log::info('ppv_commission - Admin amount'.$admin_amount);

        Log::info('ppv_commission - User amount'.$user_amount);

        $admin_video_details->admin_amount = $admin_video_details->admin_amount + $admin_amount;

        $admin_video_details->user_amount = $admin_video_details->user_amount + $user_amount;

        $admin_video_details->save();

        // Update PPV Details

        if($ppv_details = PayPerView::find($payperview_id)) {

            $ppv_details->admin_amount = $admin_amount;

            $ppv_details->user_amount = $user_amount;

            $ppv_details->save();

           // $ppv_details->status = DEFAULT_TRUE;
        
        }

        // Check the video uploaded by moderator or admin (uploaded_by = admin , uploaded_by = moderator ID)

        /*if(is_numeric($admin_video_details->uploaded_by)) {

            add_to_redeem($admin_video_details->uploaded_by , $user_amount , $admin_amount);

        } else {

            Log::info("No Redeems - ");
        }*/
        add_to_redeem($admin_video_details->user_id , $user_amount , $admin_amount);

        return true;

    }


    /**
     * @method redeem_requests_list_response()
     * 
     * @uses User wallet payments common list response
     *
     * @created Vithya R
     * 
     * @updated
     *
     * @param
     * 
     * @return boolean
     *
     */

    public static function redeem_requests_list_response($request) {

        $redeem_requests = \App\RedeemRequest::where('user_id', $request->id)
                                    ->CommonResponse()
                                    ->orderBy('redeem_requests.id', 'desc')
                                    ->skip($request->skip)
                                    ->take($request->take)
                                    ->get();
        foreach ($redeem_requests as $key => $redeem_request) {

            $redeem_request->redeem_status = redeem_request_status($redeem_request->status);

            $redeem_request->cancel_btn_status = in_array($redeem_request->status, [REDEEM_REQUEST_SENT, REDEEM_REQUEST_PROCESSING]) ? YES : NO;
        }

        return $redeem_requests;
    
    }

}