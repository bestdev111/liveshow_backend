<?php

namespace App\Repositories;

use App\Helpers\Helper;

use App\Repositories\UserRepository as UserRepo;

use Log, Validator, Setting, Exception, DB;

use App\User, App\LiveVideo, App\LiveVideoPayment;

use App\Coupon, App\UserCoupon;

use App\UserSubscription, App\PayPerView;

class PaymentRepository {

    /**
     * @method live_videos_coupon_code_check()
     *
     * @uses used to format the live videos response
     *
     * @created vithya R
     * 
     * @updated vithya R
     *
     * @param object $live_videos, object $request
     *
     * @return object $live_videos
     */

    public static function coupon_code_check_availablity($request) {

        try {

            $coupon_code_details = Coupon::where('coupon_code', $request->coupon_code)->where('status', APPROVED)->where('expiry_date', '>', date('Y-m-d H:i:s'))->first();

            if(!$coupon_code_details) {

                throw new Exception(api_error(164), 164);
            }

            $total_usage_of_coupon = UserCoupon::where('coupon_code', $coupon_code_details->coupon_code)->sum('no_of_times_used') ?? 0;

            if($total_usage_of_coupon >= $coupon_code_details->no_of_users_limit) {

                throw new Exception(api_error(165), 165);

            }

            $user_coupon = UserCoupon::where('user_id', $request->id)->where('coupon_code', $coupon_code_details->coupon_code)->first();

            if ($user_coupon) {

                if ($user_coupon->no_of_times_used > $coupon_code_details->per_users_limit) {

                    throw new Exception(api_error(166), 166);
                }

            }

            $response_array = ['success' => true, 'data' => $coupon_code_details];

            return response()->json($response_array, 200);

        } catch(Exception $e) {

            $response_array = ['success' => false, 'error' => $e->getMessage(), 'error_code' => $e->getCode()];

            return response()->json($response_array, 200);

        }
    }

    /**
     * @method coupon_code_calcualtor()
     *
     * @uses based input amount, reduce the coupon amount
     *
     * @created vithya R
     *
     * @updated vithya R
     *
     * @param object $request
     *
     * @return JSON Response
     */
    
    public static function coupon_code_calcualtor($coupon_code_details, $input_total) {

        $coupon_amount = $coupon_code_details->amount ?? 0.00;

        if($coupon_code_details->amount_type == PERCENTAGE) {

            $coupon_amount = amount_convertion($coupon_code_details->amount, $input_total);

        }

        $data = new \stdClass;

        $data->coupon_code = $coupon_code_details->coupon_code ?? "";

        $data->amount_type = $coupon_code_details->amount_type ?? "";

        $data->coupon_amount = $coupon_amount ?? 0.00;

        $data->coupon_amount_formatted = formatted_amount($coupon_amount ?? 0.00);

        $data->total = $input_total ?? 0.00;

        $data->total_formatted = formatted_amount($input_total ?? 0.00);

        $user_pay_amount = 0;

        if ($input_total > $coupon_amount && $coupon_amount > 0) {

            $user_pay_amount = $input_total - $coupon_amount;

            $user_pay_amount = $user_pay_amount < 0 ? 0 : $user_pay_amount;

        }

        $data->user_pay_amount = $user_pay_amount ?? 0.00;

        $data->user_pay_amount_formatted = formatted_amount($user_pay_amount ?? 0.00);

        $response = ['success' => true, 'message' => 'Done', 'data' => $data];

        return response()->json($response, 200);
    }

    /**
     * @method live_videos_payment_save()
     *
     * @uses used to format the live videos response
     *
     * @created vithya R
     * 
     * @updated vithya R
     *
     * @param object $live_videos, object $request
     *
     * @return object $live_videos
     */

    public static function live_videos_payment_save($request, $live_video_details) {

        try {

            $live_video_payment = new LiveVideoPayment;

            $live_video_payment->live_video_id = $request->live_video_id;

            $live_video_payment->user_id = $live_video_details->user_id;

            $live_video_payment->live_video_viewer_id = $request->id;

            $live_video_payment->payment_id = $request->payment_id;

            $live_video_payment->payment_mode = $request->payment_mode;

            $live_video_payment->amount = $request->paid_amount ?? 0.00;

            $live_video_payment->live_video_amount = $live_video_details->amount ?? NO;

            $live_video_payment->currency = Setting::get('currency', '$');

            $live_video_payment->is_coupon_applied = $request->is_coupon_applied ?? NO;

            $live_video_payment->coupon_amount = $request->coupon_amount ?? NO;

            $live_video_payment->coupon_code = $request->coupon_code ?? "";

            $live_video_payment->coupon_reason = $request->coupon_reason ?? "";

            $live_video_payment->status = PAID_STATUS;

            // Commission Spilit 

            $admin_commission = Setting::get('admin_commission')/100;

            $admin_amount = $request->paid_amount * $admin_commission;

            $user_amount = $request->paid_amount - $admin_amount;

            $live_video_payment->admin_amount = $admin_amount;

            $live_video_payment->user_amount = $user_amount;

            $live_video_payment->save();

            // update the earnings
            self::user_earnings_add($live_video_details->user_id, $live_video_payment->admin_amount, $live_video_payment->user_amount);

            $response_array = ['success' => true, 'message' => 'paid', 'data' => ['live_video_id' => $request->live_video_id]];

            return response()->json($response_array, 200);

        } catch(Exception $e) {

            $response_array = ['success' => false, 'error' => $e->getMessage(), 'error_code' => $e->getCode()];

            return response()->json($response_array, 200);

        }
    
    }

    /**
     * @method live_videos_payment_by_stripe()
     *
     * @uses pay for live videos using stripe
     *
     * @created vithya R
     * 
     * @updated vithya R
     *
     * @param object $live_videos, object $request
     *
     * @return object $live_videos
     */

    public static function live_videos_payment_by_stripe($request, $live_video_details) {

        try {

            // Check stripe configuration
        
            $stripe_secret_key = Setting::get('stripe_secret_key');

            if(!$stripe_secret_key) {

                throw new Exception(api_error(107), 107);

            } 

            \Stripe\Stripe::setApiKey($stripe_secret_key);
           
            $currency_code = Setting::get('currency_code', 'USD') ?: "USD";

            $total = intval(round($request->user_pay_amount * 100));

            $charge_array = [
                                'amount' => $total,
                                'currency' => $currency_code,
                                'customer' => $request->customer_id,
                            ];

            // @todo check the rentroom flow for payment

            $stripe_payment_response =  \Stripe\Charge::create($charge_array);

            $payment_data = [
                                'payment_id' => $stripe_payment_response->id ?? 'CARD-'.rand(),
                                'paid_amount' => $stripe_payment_response->amount/100 ?? $total,

                                'paid_status' => $stripe_payment_response->paid ?? true
                            ];

            // $request->request->add($payment_data);

            // $response_array = self::live_videos_payment_save($request, $live_video_details);

            $response_array = ['success' => true, 'message' => 'done', 'data' => $payment_data];

            return response()->json($response_array, 200);

        } catch(Exception $e) {

            $response_array = ['success' => false, 'error' => $e->getMessage(), 'error_code' => $e->getCode()];

            return response()->json($response_array, 200);

        }

    }

    /**
     * @method user_earnings_add()
     *
     * @uses add amount to user
     *
     * @created vithya R
     *
     * @updated vithya R
     *
     * @param integer $user_id, float $admin_amount, $user_amount
     *
     * @return - 
     */
    
    public static function user_earnings_add($user_id, $admin_amount, $user_amount) {

        if($user_details = User::find($user_id)) {

            $user_details->total_admin_amount += $admin_amount;

            $user_details->total_user_amount += $user_amount;

            $user_details->remaining_amount += $user_amount;

            $user_details->total += ($admin_amount+$user_amount);

            $user_details->save();

            add_to_redeem($user_details->id , $user_amount);
        
        }
    
    }

    /**
     * @method users_account_upgrade()
     *
     * @uses add amount to user
     *
     * @created vithya R
     *
     * @updated vithya R
     *
     * @param integer $user_id, float $admin_amount, $user_amount
     *
     * @return - 
     */
    
    public static function users_account_upgrade($user_id, $paid_amount = 0.00, $subscription_amount, $expiry_date) {

        if($user_details = User::find($user_id)) {

            $user_details->user_type = SUBSCRIBED_USER;

            $user_details->one_time_subscription = $subscription_amount <= 0 ? YES : NO;

            $user_details->amount_paid += $paid_amount ?? 0.00;

            $user_details->expiry_date = $expiry_date;

            $user_details->no_of_days = total_days($expiry_date);

            $user_details->save();
        
        }
    
    }

    /**
     * @method subscriptions_payment_save()
     *
     * @uses used to save user subscription payment details
     *
     * @created vithya R
     * 
     * @updated vithya R
     *
     * @param object $subscription_details, object $request
     *
     * @return object $subscription_details
     */

    public static function subscriptions_payment_save($request, $subscription_details) {

        try {

            $previous_payment = UserSubscription::where('user_id' , $request->id)
                                            ->where('status', PAID_STATUS)
                                            ->orderBy('created_at', 'desc')
                                            ->first();

            $user_subscription_details = new UserSubscription;

            $user_subscription_details->expiry_date = date('Y-m-d H:i:s',strtotime("+{$subscription_details->plan} months"));

            if($previous_payment) {

                if (strtotime($previous_payment->expiry_date) >= strtotime(date('Y-m-d H:i:s'))) {
                    $user_subscription_details->expiry_date = date('Y-m-d H:i:s', strtotime("+{$subscription_details->plan} months", strtotime($previous_payment->expiry_date)));
                }
            }

            $user_subscription_details->subscription_id = $request->subscription_id;

            $user_subscription_details->user_id = $request->id;

            $user_subscription_details->payment_id = $request->payment_id ?? "NO-".rand();

            $user_subscription_details->status = PAID_STATUS;

            $user_subscription_details->subscription_amount = $subscription_details->amount ?? 0.00;

            $user_subscription_details->amount = $request->paid_amount ?? 0.00;

            $user_subscription_details->payment_mode = $request->payment_mode ?? CARD;

            $user_subscription_details->is_coupon_applied = $request->is_coupon_code_applied ?? NO;

            $user_subscription_details->coupon_code = $request->coupon_code ?? '';

            $user_subscription_details->coupon_amount = $request->coupon_amount ?? 0.00;

            $user_subscription_details->coupon_reason = $request->coupon_reason ?? '';


            $user_subscription_details->save();

            // update the earnings
            self::users_account_upgrade($request->id, $request->paid_amount, $subscription_details->amount, $user_subscription_details->expiry_date);

            $response_array = ['success' => true, 'message' => 'paid', 'data' => ['user_type' => SUBSCRIBED_USER, 'payment_id' => $request->payment_id]];

            return response()->json($response_array, 200);

        } catch(Exception $e) {

            $response_array = ['success' => false, 'error' => $e->getMessage(), 'error_code' => $e->getCode()];

            return response()->json($response_array, 200);

        }
    
    }

    /**
     * @method subscriptions_payment_by_stripe()
     *
     * @uses pay for live videos using stripe
     *
     * @created vithya R
     * 
     * @updated vithya R
     *
     * @param object $subscription_details, object $request
     *
     * @return object $subscription_details
     */

    public static function subscriptions_payment_by_stripe($request, $subscription_details) {

        try {

            // Check stripe configuration
        
            $stripe_secret_key = Setting::get('stripe_secret_key');

            if(!$stripe_secret_key) {

                throw new Exception(api_error(107), 107);

            } 

            \Stripe\Stripe::setApiKey($stripe_secret_key);
           
            $currency_code = Setting::get('currency_code', 'USD') ?: "USD";

            $total = intval(round($request->user_pay_amount * 100));

            $charge_array = [
                                'amount' => $total,
                                'currency' => $currency_code,
                                'customer' => $request->customer_id,
                            ];


            $stripe_payment_response =  \Stripe\Charge::create($charge_array);

            $payment_data = [
                                'payment_id' => $stripe_payment_response->id ?? 'CARD-'.rand(),
                                'paid_amount' => $stripe_payment_response->amount/100 ?? $total,

                                'paid_status' => $stripe_payment_response->paid ?? true
                            ];

            $response_array = ['success' => true, 'message' => 'done', 'data' => $payment_data];

            return response()->json($response_array, 200);

        } catch(Exception $e) {

            $response_array = ['success' => false, 'error' => $e->getMessage(), 'error_code' => $e->getCode()];

            return response()->json($response_array, 200);

        }

    }

    /**
     * @method vod_videos_payment_save()
     *
     * @uses used to format the live videos response
     *
     * @created vithya R
     * 
     * @updated vithya R
     *
     * @param object $live_videos, object $request
     *
     * @return object $live_videos
     */

    public static function vod_videos_payment_save($request, $vod_video_details) {

        try {

            $ppv_details = new PayPerView;

            $ppv_details->video_id = $request->vod_video_id;

            $ppv_details->user_id = $request->id;

            $ppv_details->payment_id = $request->payment_id;

            $ppv_details->payment_mode = $request->payment_mode;

            $ppv_details->amount = $request->paid_amount ?? 0.00;

            $ppv_details->ppv_amount = $vod_video_details->amount ?? 0.00;

            $ppv_details->status = PAID_STATUS;

            $ppv_details->is_watched = NOT_YET_WATCHED;

            $ppv_details->ppv_date = date('Y-m-d H:i:s');
            
            $ppv_details->type_of_user = vod_type_of_user($vod_video_details->type_of_user);
            $ppv_details->type_of_subscription = vod_type_of_subscription($vod_video_details->type_of_subscription);

            $ppv_details->is_coupon_applied = $request->is_coupon_applied ?? NO;

            $ppv_details->coupon_amount = $request->coupon_amount ?? NO;

            $ppv_details->coupon_code = $request->coupon_code ?? "";

            $ppv_details->coupon_reason = $request->coupon_reason ?? "";

            $ppv_details->status = PAID_STATUS;

            $ppv_details->save();

            UserRepo::ppv_commission_split($vod_video_details->id , $ppv_details->id , "");

            $response_array = ['success' => true, 'message' => 'paid', 'data' => ['live_video_id' => $request->live_video_id]];

            return response()->json($response_array, 200);

        } catch(Exception $e) {

            $response_array = ['success' => false, 'error' => $e->getMessage(), 'error_code' => $e->getCode()];

            return response()->json($response_array, 200);

        }
    
    }

    /**
     * @method vod_videos_payment_by_stripe()
     *
     * @uses pay for live videos using stripe
     *
     * @created vithya R
     * 
     * @updated vithya R
     *
     * @param object $live_videos, object $request
     *
     * @return object $live_videos
     */

    public static function vod_videos_payment_by_stripe($request, $vod_video_details) {

        try {

            // Check stripe configuration
        
            $stripe_secret_key = Setting::get('stripe_secret_key');

            if(!$stripe_secret_key) {

                throw new Exception(api_error(107), 107);

            } 

            \Stripe\Stripe::setApiKey($stripe_secret_key);
           
            $currency_code = Setting::get('currency_code', 'USD') ?: "USD";

            $total = intval(round($request->user_pay_amount * 100));

            $charge_array = [
                                'amount' => $total,
                                'currency' => $currency_code,
                                'customer' => $request->customer_id,
                            ];

            // @todo check the rentroom flow for payment

            $stripe_payment_response =  \Stripe\Charge::create($charge_array);

            $payment_data = [
                                'payment_id' => $stripe_payment_response->id ?? 'CARD-'.rand(),
                                'paid_amount' => $stripe_payment_response->amount/100 ?? $total,

                                'paid_status' => $stripe_payment_response->paid ?? true
                            ];

            $response_array = ['success' => true, 'message' => 'done', 'data' => $payment_data];

            return response()->json($response_array, 200);

        } catch(Exception $e) {

            $response_array = ['success' => false, 'error' => $e->getMessage(), 'error_code' => $e->getCode()];

            return response()->json($response_array, 200);

        }

    }

}