<?php

namespace App\Http\Controllers;

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\ExecutePayment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\Transaction;
use PayPal\Exception\PayPalConnectionException;

use App\Repositories\UserRepository as UserRepo;


use Setting;
use Log;
use Session;
use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\User;
use Auth;
use App\UserSubscription;
use App\Subscription;
use App\LiveVideo;
use App\LiveVideoPayment;
use App\Coupon;
use App\UserCoupon;

use App\VodVideo;
use App\PayPerView;
 
class PaypalController extends Controller {
   
    private $_api_context;
    protected $UserAPI;
 
    public function __construct(UserApiController $API) {
       
        $this->middleware('PaypalCheck');

        $this->UserAPI = $API;

        // setup PayPal api context

        $paypal_conf = config('paypal');

        $paypal_conf['client_id'] = envfile('PAYPAL_ID') ?  envfile('PAYPAL_ID') : $paypal_conf['client_id'];
        $paypal_conf['secret'] = envfile('PAYPAL_SECRET') ?  envfile('PAYPAL_SECRET') : $paypal_conf['secret'];
        $paypal_conf['settings']['mode'] = envfile('PAYPAL_MODE') ?  envfile('PAYPAL_MODE') : $paypal_conf['settings']['mode'];

        Log::info("PAYPAL CONFIGURATION".print_r($paypal_conf,true));
        
        $this->_api_context = new ApiContext(new OAuthTokenCredential($paypal_conf['client_id'], $paypal_conf['secret']));

        $this->_api_context->setConfig($paypal_conf['settings']);
   
    }


    public function pay(Request $request) {

        $subscription = Subscription::find($request->id);

        $user = User::find($request->user_id);

        if($subscription && $user) {

            $total =  $subscription ? $subscription->amount : "1.00" ;

            $coupon_amount = 0;

            $coupon_reason = '';

            $is_coupon_applied = COUPON_NOT_APPLIED;

            if ($request->coupon_code) {

                $coupon = Coupon::where('coupon_code', $request->coupon_code)->first();

                if ($coupon) {

                    $is_coupon_applied = DEFAULT_TRUE;

                    if ($coupon->status == COUPON_INACTIVE) {

                        $coupon_reason = tr('coupon_code_declined');

                    } else {

                        $check_coupon = $this->UserAPI->check_coupon_applicable_to_user($user, $coupon)->getData();

                        if ($check_coupon->success) {

                            $amount_convertion = $coupon->amount;

                            if ($coupon->amount_type == PERCENTAGE) {

                                $amount_convertion = amount_convertion($coupon->amount, $subscription->amount);

                            }

                            if ($amount_convertion < $subscription->amount) {

                                $total = $subscription->amount - $amount_convertion;

                                $coupon_amount = $amount_convertion;

                            }

                            // Create user applied coupon

                            if($check_coupon->code == 2002) {

                                $user_coupon = UserCoupon::where('user_id', $user->id)
                                        ->where('coupon_code', $request->coupon_code)
                                        ->first();

                                // If user coupon not exists, create a new row

                                if ($user_coupon) {

                                    if ($user_coupon->no_of_times_used < $coupon->per_users_limit) {

                                        $user_coupon->no_of_times_used += 1;

                                        $user_coupon->save();

                                    }

                                }

                            } else {

                                $user_coupon = new UserCoupon;

                                $user_coupon->user_id = $user->id;

                                $user_coupon->coupon_code = $request->coupon_code;

                                $user_coupon->no_of_times_used = 1;

                                $user_coupon->save();

                            }

                        } else {

                            $coupon_reason = $check_coupon->error_messages;

                        }

                    }

                } else {

                    $coupon_reason = tr('coupon_code_not_exists');

                }

            }

    		$item = new Item();

    		$item->setName(Setting::get('site_name')) // item name
    				   ->setCurrency('USD')
    			   ->setQuantity('1')
                   ->setPrice($total);
    	 
            $payer = new Payer();
            
            $payer->setPaymentMethod('paypal');

            // add item to list
            $item_list = new ItemList();
            $item_list->setItems(array($item));
            $total = $total;
            $details = new Details();
            $details->setShipping('0.00')
                ->setTax('0.00')
                ->setSubtotal($total);


            $amount = new Amount();
            $amount->setCurrency('USD')
                ->setTotal($total)
            	->setDetails($details);

            $transaction = new Transaction();
            $transaction->setAmount($amount)
                ->setItemList($item_list)
                ->setDescription('Payment for the Request');

            $redirect_urls = new RedirectUrls();
            $redirect_urls->setReturnUrl(url('/user/payment/status'))
                        ->setCancelUrl(Setting::get('ANGULAR_URL').'payment-failure');

            $payment = new Payment();
            $payment->setIntent('Sale')
                ->setPayer($payer)
                ->setRedirectUrls($redirect_urls)
                ->setTransactions(array($transaction));

            try {
                $payment->create($this->_api_context);

            } catch (\PayPal\Exception\PayPalConnectionException $ex) {

                if (\Config::get('app.debug')) {

                    echo "Exception: " . $ex->getMessage() . PHP_EOL;

                    echo "Payment" . $payment."<br />";

                    $err_data = json_decode($ex->getData(), true);

                    echo "Error" . print_r($err_data);

                    exit;

                } else {
                    
                    die('Some error occur, sorry for inconvenient');
                }
            }

            foreach($payment->getLinks() as $link) {
                if($link->getRel() == 'approval_url') {
                    $redirect_url = $link->getHref();
                    break;
                }
            }

            // add payment ID to session

            Session::put('paypal_payment_id', $payment->getId());

            if(isset($redirect_url)) {

                $previous_payment = UserSubscription::where('user_id' , $request->user_id)
                ->orderBy('id', 'desc')
                ->where('status', DEFAULT_TRUE)
                ->first();

                $user_payment = new UserSubscription();

                if ($previous_payment) {

                     if (strtotime($previous_payment->expiry_date) >= strtotime(date('Y-m-d H:i:s'))) {

                         $user_payment->expiry_date = date('Y-m-d H:i:s', strtotime("+{$subscription->plan} months", strtotime($previous_payment->expiry_date)));

                    } else {

                        $user_payment->expiry_date = date('Y-m-d H:i:s',strtotime("+{$subscription->plan} months"));

                    }

                } else {

                    $user_payment->expiry_date = date('Y-m-d H:i:s',strtotime("+{$subscription->plan} months"));
                }

                $user_payment->payment_id  = $payment->getId();
                $user_payment->user_id = $request->user_id;
                $user_payment->subscription_id = $request->id;

                $user_payment->payment_mode = PAYPAL;

                // Coupon details

                $user_payment->is_coupon_applied = $is_coupon_applied;

                $user_payment->coupon_code = $request->coupon_code  ? $request->coupon_code  :'';

                $user_payment->coupon_amount = $coupon_amount;

                $user_payment->subscription_amount = $subscription->amount;

                $user_payment->coupon_reason = $is_coupon_applied == COUPON_APPLIED ? '' : $coupon_reason;

                Log::info("User Payment ".print_r($user_payment, true));

                $user_payment->save();

                Log::info("User Payment After saved ".print_r($user_payment, true));

                $response_array = array('success' => true); 

                return redirect()->away($redirect_url);

            }

            return response()->json(Helper::null_safe($response_array) , 200);

        } else {

            return redirect()->away(Setting::get('ANGULAR_URL').'payment-failure');

        }
                    
    }
    

    public function getPaymentStatus(Request $request) {

        // Get the payment ID before session clear
        $payment_id = Session::get('paypal_payment_id');
        
        // clear the session payment ID
     
        if (empty($request->PayerID) || empty($request->token)) {
        	
		  return back()->with('flash_error',tr('payment_failed'));

		} 
            
     
        $payment = Payment::get($payment_id, $this->_api_context);
     
        // PaymentExecution object includes information necessary
        // to execute a PayPal account payment.
        // The payer_id is added to the request query parameters
        // when the user is redirected from paypal back to your site
        
        $execution = new PaymentExecution();
        $execution->setPayerId($request->PayerID);
     
        //Execute the payment
        $result = $payment->execute($execution, $this->_api_context);
     
       // echo '<pre>';print_r($result);echo '</pre>';exit; // DEBUG RESULT, remove it later
     
        if ($result->getState() == 'approved') { // payment made

            $payment = UserSubscription::where('payment_id',$payment_id)->first();

            $subscription = Subscription::find($payment->subscription_id);

            $total =  $subscription ? $subscription->amount : "1.00" ;

            $payment->status = PAID_STATUS;

            $payment->amount = $payment->subscription_amount - $payment->coupon_amount;

            $payment->save();


            if ($payment) {

                $user = User::find($payment->user_id);

                $user->amount_paid += $total;

                $user->expiry_date = $payment->expiry_date;

                $user->no_of_days = 0;

                $user->user_type = DEFAULT_TRUE;

                $now = time(); // or your date as well

                $end_date = strtotime($user->expiry_date);

                $datediff =  $end_date - $now;

                $user->no_of_days = ($user->expiry_date) ? floor($datediff / (60 * 60 * 24)) + 1 : 0;

                $user->save();

            }

            Session::forget('paypal_payment_id');
            
            $response_array = array('success' => true , 'message' => tr('payment_success') ); 

            $responses = response()->json($response_array);

            $response = $responses->getData();

            // return back()->with('flash_success' , 'Payment Successful');

            // return redirect()->away("http://localhost/live-streaming-base/#/video-form");

            return redirect()->away(Setting::get('ANGULAR_URL')."payment-success?subscription_id=".$payment->id);
       
        } else {

            return back()->with('flash_error' ,tr('payment_approved_contact_admin'));
        }
            
           
    }

    /** 
     *
     *
     *
     */

    public function payPerVideo(Request $request) {

        // Check the id and user_id if not empty

        if($request->user_id && $request->id) {

            $paying_user_details = User::find($request->user_id);

            Log::info("LiveVideo ID".$request->id);

            Log::info("LiveVideo VIEWER ID".$request->user_id);

            Log::info("VIEWER ID Details".print_r($paying_user_details , true));

            // Check the live video and user details are exists

            $subscription = LiveVideo::where('id' ,$request->id)->where('is_streaming' , 1)->where('status',0)->where('payment_status' , 1)->first();

            // Check the live video exists and streaming

            if($subscription && $paying_user_details) {

                $total =  $subscription ? $subscription->amount : "1.00" ;

                $coupon_amount = 0;

                $coupon_reason = '';

                $is_coupon_applied = COUPON_NOT_APPLIED;

                if ($request->coupon_code) {

                    $coupon = Coupon::where('coupon_code', $request->coupon_code)->first();

                    if ($coupon) {
                        
                        if ($coupon->status == COUPON_INACTIVE) {

                            $coupon_reason = tr('coupon_inactive_reason');

                        } else {

                            $check_coupon = $this->UserAPI->check_coupon_applicable_to_user($paying_user_details, $coupon)->getData();

                            if ($check_coupon->success) {

                                $is_coupon_applied = COUPON_APPLIED;

                                $amount_convertion = $coupon->amount;

                                if ($coupon->amount_type == PERCENTAGE) {

                                    $amount_convertion = amount_convertion($coupon->amount, $subscription->amount);

                                }


                                if ($amount_convertion < $subscription->amount) {

                                    $total = $subscription->amount - $amount_convertion;

                                    $coupon_amount = $amount_convertion;

                                } else {

                                    // throw new Exception(Helper::get_error_message(156),156);

                                    $total = 0;

                                    $coupon_amount = $amount_convertion;
                                    
                                }

                                // Create user applied coupon

                                if($check_coupon->code == 2002) {

                                    $user_coupon = UserCoupon::where('user_id', $paying_user_details->id)
                                            ->where('coupon_code', $request->coupon_code)
                                            ->first();

                                    // If user coupon not exists, create a new row

                                    if ($user_coupon) {

                                        if ($user_coupon->no_of_times_used < $coupon->per_users_limit) {

                                            $user_coupon->no_of_times_used += 1;

                                            $user_coupon->save();

                                        }

                                    }

                                } else {

                                    $user_coupon = new UserCoupon;

                                    $user_coupon->user_id = $paying_user_details->id;

                                    $user_coupon->coupon_code = $request->coupon_code;

                                    $user_coupon->no_of_times_used = 1;

                                    $user_coupon->save();

                                }

                            } else {

                                $coupon_reason = $check_coupon->error_messages;
                                
                            }
                        }

                    } else {

                        $coupon_reason = tr('coupon_delete_reason');
                    }
                
                }

                $item = new Item();

                $item->setName(Setting::get('site_name')) // item name
                           ->setCurrency('USD')
                       ->setQuantity('1')
                       ->setPrice($total);
             
                $payer = new Payer();
                
                $payer->setPaymentMethod('paypal');

                // add item to list
                $item_list = new ItemList();

                $item_list->setItems(array($item));

                $total = $total;

                $details = new Details();

                $details->setShipping('0.00')
                    ->setTax('0.00')
                    ->setSubtotal($total);

                $amount = new Amount();
                $amount->setCurrency('USD')
                    ->setTotal($total)
                    ->setDetails($details);

                $transaction = new Transaction();
                $transaction->setAmount($amount)
                    ->setItemList($item_list)
                    ->setDescription('Payment for the Request');

                $redirect_urls = new RedirectUrls();

                $redirect_urls->setReturnUrl(url('/user/payment_video'))
                            ->setCancelUrl(Setting::get('ANGULAR_URL').'payment-failure?id='.$request->user_id);

                $payment = new Payment();

                $payment->setIntent('Sale')
                    ->setPayer($payer)
                    ->setRedirectUrls($redirect_urls)
                    ->setTransactions(array($transaction));

                try {

                    $payment->create($this->_api_context);

                } catch (\PayPal\Exception\PayPalConnectionException $ex) {

                    if (\Config::get('app.debug')) {
                        
                        echo "Exception: " . $ex->getMessage() . PHP_EOL;

                        echo "Payment" . $payment."<br />";

                        $err_data = json_decode($ex->getData(), true);

                        echo "Error" . print_r($err_data);

                        exit;

                    } else {

                        die('Some error occur, sorry for inconvenient');

                    }
               
                }

                foreach($payment->getLinks() as $link) {

                    if($link->getRel() == 'approval_url') {

                        $redirect_url = $link->getHref();

                        break;
                    }
                
                }

                // add payment ID to session

                Session::put('paypal_payment_id', $payment->getId());

                if(isset($redirect_url)) {

                    $user_payment = new LiveVideoPayment;

                    $check_live_video_payment = LiveVideoPayment::where('live_video_viewer_id' , $request->user_id)->where('live_video_id' , $request->id)->first();

                    if($check_live_video_payment) {
                        $user_payment = $check_live_video_payment;
                    }

                    $user_payment->payment_id  = $payment->getId();
                
                    $user_payment->live_video_viewer_id = $request->user_id;
                
                    $user_payment->live_video_id = $request->id;
                
                    $user_payment->user_id = $subscription->user_id;

                    $user_payment->status = PAY_LIVE_VIDEO_INITIAL;

                    $user_payment->payment_mode = PAYPAL;

                    Log::info("User Payment ".print_r($user_payment, true));

                    $user_payment->coupon_amount = $coupon_amount;

                    $user_payment->coupon_code = $request->coupon_code ? $request->coupon_code : "";

                    $user_payment->live_video_amount = $subscription->amount;

                    $user_payment->is_coupon_applied = $is_coupon_applied;

                    $user_payment->coupon_reason = $is_coupon_applied ? $coupon_reason : '';

                    $user_payment->save();

                    Log::info("User Payment After saved ".print_r($user_payment, true));

                    $response_array = array('success' => true); 

                    return redirect()->away($redirect_url);
               
                }

                return response()->json(Helper::null_safe($response_array) , 200);

            } else {

                Log::info("subscription details not found....!!!!");

                return redirect()->away(Setting::get('ANGULAR_URL')."payment-failure");

            }

        } else {

            Log::info("ID and USER ID Not FOUND");
            
            return redirect()->away(Setting::get('ANGULAR_URL')."payment-failure");
        }
                    
    }
    

    public function getVideoPaymentStatus(Request $request) {

        // Get the payment ID before session clear

        $payment_id = Session::get('paypal_payment_id');
        
        // clear the session payment ID
     
        if (empty($request->PayerID) || empty($request->token)) {

            Log::info("Payment Failed!! In getVideoPaymentStatus API");

            return redirect()->away(Setting::get('ANGULAR_URL').'payment-failure');
            
        } 
        
        $payment = Payment::get($payment_id, $this->_api_context);
     
        // PaymentExecution object includes information necessary
        // to execute a PayPal account payment.
        // The payer_id is added to the request query parameters
        // when the user is redirected from paypal back to your site
        
        $execution = new PaymentExecution();

        $execution->setPayerId($request->PayerID);
     
        //Execute the payment

        $result = $payment->execute($execution, $this->_api_context);
          
        if ($result->getState() == 'approved') { // payment made

            if($live_video_payment = LiveVideoPayment::where('payment_id',$payment_id)->first()) {

                $total =  $live_video_payment ? (($live_video_payment->getVideo) ? $live_video_payment->getVideo->amount : "1.00" ) : "1.00";

                $live_video_payment->status = PAY_LIVE_VIDEO_COMPLETED;

                $payment->amount = $payment->live_video_amount - $payment->coupon_amount;

                // Commission Spilit 

                $admin_commission = Setting::get('admin_commission') ? Setting::get('admin_commission')/100 : 0;

                $admin_amount = $total * $admin_commission;

                $user_amount = $total - $admin_amount;

                $live_video_payment->admin_amount = $admin_amount;

                $live_video_payment->user_amount = $user_amount;

                $live_video_payment->save();

                // Commission Spilit Completed

                if($user = User::find($live_video_payment->user_id)) {

                    $user->total_admin_amount = $user->total_admin_amount + $admin_amount;

                    $user->total_user_amount = $user->total_user_amount + $user_amount;

                    $user->remaining_amount = $user->remaining_amount + $user_amount;

                    $user->total = $user->total + $total;

                    $user->save();
                    
                    add_to_redeem($user->id , $user_amount);
                }

                Session::forget('paypal_payment_id');
                
                $response_array = array('success' => true , 'message' => tr('ppv_payment_success') ); 

                $responses = response()->json($response_array);

                $response = $responses->getData();

                return redirect()->away(Setting::get('ANGULAR_URL')."payment-success?video_id=".$live_video_payment->live_video_id);
       
            } else {

                Log::info("LiveVideoPayment RECORD NOT EXISTING. Please check the payments table");

                return redirect()->away(Setting::get('ANGULAR_URL')."payment-failure");
            }
       
        } else {

            Log::info("Payment is not approved. Please contact admin");

            return redirect()->away(Setting::get('ANGULAR_URL')."payment-failure");
        }
            
           
    }


    /**
     * @uses Get the payment for PPV from user
     *
     * @param id = VIDEO ID
     *
     * @param user_id 
     *
     * @return redirect to success/faiture pages, depends on the payment status
     * 
     * @author shobanacs
     *
     * @edited : vidhyar2612
     */
   
    public function videoSubscriptionPay(Request $request) {

        // Get the PPV total amount based on the selected video

        $video = VodVideo::where('id', $request->id)->first();

        $userModel = User::find($request->user_id);

        if($video && $userModel){
            
            return redirect()->away(Setting::get('ANGULAR_URL')."payment-failure");
        }

        $total = $video->amount == 0 ? 0.1 : $video->amount;

        $coupon_amount = 0;

        $coupon_reason = '';

        $is_coupon_applied = COUPON_NOT_APPLIED;

        if ($request->coupon_code) {

            $coupon = Coupon::where('coupon_code', $request->coupon_code)->first();

            if ($coupon) {
                
                if ($coupon->status == COUPON_INACTIVE) {

                    $coupon_reason = tr('coupon_inactive_reason');

                } else {

                    $check_coupon = $this->UserAPI->check_coupon_applicable_to_user($userModel, $coupon)->getData();

                    if ($check_coupon->success) {

                        $is_coupon_applied = COUPON_APPLIED;

                        $amount_convertion = $coupon->amount;

                        if ($coupon->amount_type == PERCENTAGE) {

                            $amount_convertion = amount_convertion($coupon->amount, $video->amount);

                        }

                        if ($amount_convertion < $video->amount  && $amount_convertion > 0) {

                            $total = $video->amount - $amount_convertion;

                            $coupon_amount = $amount_convertion;

                        } else {

                            // throw new Exception(Helper::get_error_message(156),156);

                            $total = 0;

                            $coupon_amount = $amount_convertion;
                            
                        }

                        // Create user applied coupon

                        if($check_coupon->code == 2002) {

                            $user_coupon = UserCoupon::where('user_id', $userModel->id)
                                    ->where('coupon_code', $request->coupon_code)
                                    ->first();

                            // If user coupon not exists, create a new row

                            if ($user_coupon) {

                                if ($user_coupon->no_of_times_used < $coupon->per_users_limit) {

                                    $user_coupon->no_of_times_used += 1;

                                    $user_coupon->save();

                                }

                            }

                        } else {

                            $user_coupon = new UserCoupon;

                            $user_coupon->user_id = $userModel->id;

                            $user_coupon->coupon_code = $request->coupon_code;

                            $user_coupon->no_of_times_used = 1;

                            $user_coupon->save();

                        }

                    } else {

                        $coupon_reason = $check_coupon->error_messages;
                        
                    }

                }

            } else {

                $coupon_reason = tr('coupon_delete_reason');
            }
        
        }


        $item = new Item();

        $item->setName(Setting::get('site_name')) // item name
                   ->setCurrency('USD')
               ->setQuantity('1')
               ->setPrice($total);
     
        $payer = new Payer();
        
        $payer->setPaymentMethod('paypal');

        // add item to list
        $item_list = new ItemList();
        $item_list->setItems(array($item));
        $total = $total;
        $details = new Details();
        $details->setShipping('0.00')
            ->setTax('0.00')
            ->setSubtotal($total);


        $amount = new Amount();
        $amount->setCurrency('USD')
            ->setTotal($total)
            ->setDetails($details);

        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($item_list)
            ->setDescription('Payment for the Request');

        $redirect_urls = new RedirectUrls();
        $redirect_urls->setReturnUrl(url('/user/vod-status'))
                    ->setCancelUrl(Setting::get('ANGULAR_URL')."payment-failure");

        $payment = new Payment();
        $payment->setIntent('Sale')
            ->setPayer($payer)
            ->setRedirectUrls($redirect_urls)
            ->setTransactions(array($transaction));

        try {

            $payment->create($this->_api_context);

        } catch (\PayPal\Exception\PayPalConnectionException $ex) {

            if (\Config::get('app.debug')) {

                // echo "Exception: " . $ex->getMessage() . PHP_EOL;
                // echo "Payment" . $payment."<br />";

                // $err_data = json_decode($ex->getData(), true);
                // echo "Error" . print_r($err_data);
                // exit;

                 // Log::info("Exception: " . $ex->getMessage() . PHP_EOL);

                $error_data = json_decode($ex->getData(), true);

                $error_message = $ex->getMessage() . PHP_EOL;

                // $error_message = isset($error_data['error']) ? $error_data['error']: "".".".isset($error_data['error_description']) ? $error_data['error_description'] : "";

                Log::info("Pay API catch METHOD");

                UserRepo::ppv_payment_failure_save($request->user_id, $request->id, $error_message);

                return redirect()->away(Setting::get('ANGULAR_URL')."payment-failure");

            } else {

                $error_data = "Some error occur, sorry for inconvenient";

                UserRepo::ppv_payment_failure_save($request->user_id, $request->id, $error_message);

                return redirect()->away(Setting::get('ANGULAR_URL')."payment-failure");

            }
        }

        foreach($payment->getLinks() as $link) {

            if($link->getRel() == 'approval_url') {

                $redirect_url = $link->getHref();

                break;
            }
        }

        // Add payment ID to session to use after payment redirection

        Session::put('paypal_payment_id', $payment->getId());

        if(isset($redirect_url)) {

            $user_payment = PayPerView::where('user_id' , $request->user_id)->where('video_id' , $request->id)->where('amount',0)->first();

            if(empty($user_payment)) {

                $user_payment = new PayPerView;

            }

            $user_payment->expiry_date = date('Y-m-d H:i:s');

            $user_payment->payment_id  = $payment->getId();

            $user_payment->user_id = $request->user_id;

            $user_payment->video_id = $request->id;

            $user_payment->payment_mode = PAYPAL;

            $user_payment->coupon_amount = $coupon_amount;

            $user_payment->coupon_code = $request->coupon_code ? $request->coupon_code : "";

            $user_payment->ppv_amount = $video->amount;

            $user_payment->is_coupon_applied = $is_coupon_applied;

            $user_payment->coupon_reason = $is_coupon_applied ? $coupon_reason : '';

            $user_payment->save();

            return redirect()->away($redirect_url);

        }

        return redirect()->away(Setting::get('ANGULAR_URL'));
                    
    }

    /**
     * @uses to store user payment details from the paypal response
     *
     * @param paypal ID
     *
     * @param paypal Token
     *
     * @return redirect to angular pages, depends on the 
     * 
     * @author shobanacs
     *
     * @edited : vidhyar2612
     */

    public function getVODPaymentStatus(Request $request) {

        // Get the payment ID before session clear

        $payment_id = Session::get('paypal_payment_id');
        
        // clear the session payment ID
     
        if (empty($request->PayerID) || empty($request->token)) {
            
            Log::info("PPV - PayerID or Pay Token empty");
            
            return redirect()->away(Setting::get('ANGULAR_URL')."payment-failure");

        } 

        try { 

            $payment = Payment::get($payment_id, $this->_api_context);

            // PaymentExecution object includes information necessary
            // to execute a PayPal account payment.
            // The payer_id is added to the request query parameters
            // when the user is redirected from paypal back to your site
            
            $execution = new PaymentExecution();

            $execution->setPayerId($request->PayerID);
         
            //Execute the payment

            $result = $payment->execute($execution, $this->_api_context);

        } catch(\PayPal\Exception\PayPalConnectionException $ex){

            $error_data = json_decode($ex->getData(), true);

            $error_message = $ex->getMessage() . PHP_EOL;

            // $error_message = isset($error_data['error']) ? $error_data['error']: "".".".isset($error_data['error_description']) ? $error_data['error_description'] : "";

            UserRepo::ppv_payment_failure_save("", "", $error_message , $payment_id);

            Session::forget('paypal_payment_id');

            return redirect()->away(Setting::get('ANGULAR_URL')."payment-failure");

        }
                      
       // echo '<pre>';print_r($result);echo '</pre>';exit; // DEBUG RESULT, remove it later
     
        if ($result->getState() == 'approved') { // payment made

            $payment = PayPerView::where('payment_id',$payment_id)->first();

            $video = $payment->vodVideo;

            if(!$payment) {

                $error_message = "PPV details not found!!!";

                UserRepo::ppv_payment_failure_save("", "", $error_message , $payment_id);

                Session::forget('paypal_payment_id');

                return redirect()->away(Setting::get('ANGULAR_URL')."payment-failure");

            }

            $payment->amount = $payment->ppv_amount - $payment->coupon_amount;

            if ($video->type_of_user == NORMAL_USER) {

                $payment->type_of_user = tr('normal_users');

            } else if($video->type_of_user == PAID_USER) {

                $payment->type_of_user = tr('paid_users');

            } else if($video->type_of_user == BOTH_USERS) {

                $payment->type_of_user = tr('both_users');
            }


            if ($video->type_of_subscription == ONE_TIME_PAYMENT) {

                $payment->type_of_subscription = tr('one_time_payment');

            } else if($video->type_of_subscription == RECURRING_PAYMENT) {

                $payment->type_of_subscription = tr('recurring_payment');

            }

            $payment->status = PAID_STATUS;

            $payment->is_watched = NOT_YET_WATCHED;

            $payment->save();

            if($payment->amount > 0) { 

                // Do Commission spilit  and redeems for moderator

                Log::info("ppv_commission_spilit started");

                UserRepo::ppv_commission_split($video->id , $payment->id , "");

                Log::info("ppv_commission_spilit END");            
                
            }

            Session::forget('paypal_payment_id');
            
            return redirect()->away(Setting::get('ANGULAR_URL')."payment-success?vod_id=".$video->unique_id);
       
        } else {

            $error_message = tr('payment_not_approved_contact_admin');

            UserRepo::ppv_payment_failure_save("", "", $error_message , $payment_id);

            Session::forget('paypal_payment_id');

            return redirect()->away(Setting::get('ANGULAR_URL')."payment-failure");
        }
            
           
    }    
}
