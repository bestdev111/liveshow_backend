<?php

namespace App\Repositories;

use App\Helpers\Helper;

use Log, Validator, Setting, Exception, DB;

use App\User, App\LiveVideo, App\LiveGroup;

class LiveVideoRepository {

    /**
     * @method live_videos_common_query()
     *
     * @uses used to check the conditions to fetch the live videos
     *
     * @created vithya R
     * 
     * @updated vithya R
     *
     * @param object $base_query, object $request
     *
     * @return object $base_query
     */

    public static function live_videos_common_query($request, $base_query) {

        if($request->id) {

            // omit the live videos - blocked by you, who blocked you & your live videos

            $block_user_ids = Helper::get_bloked_users($request->id);

            // groups based videos

            if($block_user_ids) {

                $base_query = $base_query->whereNotIn('live_videos.user_id', $block_user_ids);
            }

            // Get logged in users groups 

            $group_ids = get_user_groups($request->id);

            array_push($group_ids, 0);

            $base_query->whereIn('live_videos.live_group_id', $group_ids);

        } else {

            // group videos can accessable by the group members only. Not guest use
            $base_query = $base_query->where('live_videos.live_group_id', '=', 0)->where('type', TYPE_PUBLIC);
        }

        if($request->type) {

            if($request->type == TYPE_PRIVATE) {

                $following_user_ids = \App\Follower::where('follower', $request->id)->get()->pluck('user_id')->toArray();

                $following_user_ids = $following_user_ids ?: [0];

                $base_query = $base_query->whereIn('live_videos.user_id', $following_user_ids);
            }

            $base_query = $base_query->where('live_videos.type', $request->type);
        }

        return $base_query;

    }

    /**
     * @method live_videos_list_response()
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

    public static function live_videos_list_response($live_videos, $request) {

        $following_user_ids = \App\Follower::where('follower', $request->id)->get()->pluck('user_id')->toArray();

        foreach ($live_videos as $key => $live_video) {

            $live_video->share_link = Setting::get('ANGULAR_URL');

            $live_video->is_user_needs_to_pay = Helper::live_videos_check_payment($live_video, $request->id); 

            $request->request->add(['broadcast_type' => $live_video->broadcast_type, 'virtual_id' => $live_video->virtual_id, 'live_video_id' => $live_video->live_video_id]);

            $live_video->mobile_live_streaming_url = Helper::get_mobile_live_streaming_url($request);

            $live_video_user = User::find($live_video->user_id);

            $live_video->redirect_web_url = get_antmedia_playurl($redirect_web_url = "", $live_video, $live_video_user);

            if($following_user_ids && $live_video->type == TYPE_PRIVATE) {

                if(!in_array($live_video->user_id, $following_user_ids)) {
                    
                    unset($live_video);

                }
            }

        }

        return $live_videos;
    
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

    public static function live_videos_payment_save($live_videos, $request) {
    
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

    public static function live_videos_payment_by_stripe($customer_id, $total) {

        try {

            // Check stripe configuration
        
            $stripe_secret_key = Setting::get('stripe_secret_key');

            if(!$stripe_secret_key) {

                throw new Exception(Helper::error_message(107), 107);

            } 

            \Stripe\Stripe::setApiKey($stripe_secret_key);
           
            $currency_code = Setting::get('currency_code', 'USD') ?: "USD";

            $total = intval(round($total * 100));

            $charge_array = [
                                "amount" => $total,
                                "currency" => $currency_code,
                                "customer" => $customer_id,
                            ];

            // @todo check the rentroom flow for payment

            $stripe_payment_response =  \Stripe\Charge::create($charge_array);

            $data = new \stdClass;

            $data->payment_id = $stripe_payment_response->id ?? 'CARD-'.rand();

            $data->paid_amount = $stripe_payment_response->amount/100 ?? $total;

            $data->paid_status = $stripe_payment_response->paid ?? true;

            $response_array = ['success' => true, 'data' => $data];

            return response()->json($response_array, 200);

        } catch(Exception $e) {

            $response_array = ['success' => false, 'error' => $e->getMessage()];

            return response()->json($response_array, 200);

        }

    }

}