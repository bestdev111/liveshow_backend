<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Validator, Log, Hash, Setting, DB, Exception, File;

use App\Repositories\CommonRepository as CommonRepo;

use App\Repositories\UserRepository as UserRepo;

use App\Repositories\LiveVideoRepository as LiveVideoRepo;

use App\Repositories\PaymentRepository as PaymentRepo;

use App\Helpers\Helper;

use App\User, App\Card, App\Livevideo;

use App\Follower, App\BlockList;

use App\Event, App\LiveVideoPayment, App\ChatMessage, App\Viewer;

use App\LiveGroup, App\LiveGroupMember;


class LiveVideoApiController extends Controller
{
    protected $loginUser;

    protected $skip, $take, $timezone, $currency, $device_type;

    public function __construct(Request $request) {

        Log::info(url()->current());

        Log::info("Request Data".print_r($request->all(), true));

        $this->skip = $request->skip ?: 0;

        $this->take = $request->take ?: (Setting::get('api_take_count') ?: TAKE_COUNT);

        $this->currency = Setting::get('currency', '$');

        $this->loginUser = User::CommonResponse()->find($request->id);

        $this->timezone = $this->loginUser->timezone ?? "America/New_York";

        $this->device_type = $this->loginUser->device_type ?? DEVICE_WEB;

        Helper::regenerate_token_expiry($this->loginUser);

    }

    /** 
     * @method home()
     *
     * @uses get the current live streaming videos
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param
     * 
     * @return JSON response
     *
     */

    public function home(Request $request) {
        
        try {
            
            counter();
            
            $base_query = LiveVideo::homeResponse();
            
            // this function has all common check conditions for videos
            $base_query = LiveVideoRepo::live_videos_common_query($request, $base_query);
            
            $live_videos = $base_query->skip($this->skip)->take($this->take)
            ->orderBy('live_videos.id', 'desc')
            ->get();
            $live_videos = LiveVideoRepo::live_videos_list_response($live_videos, $request);
            return $this->sendResponse($message = '', $code = '', $live_videos);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method live_videos_public()
     *
     * @uses get the current live streaming videos
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param
     * 
     * @return JSON response
     *
     */

    public function live_videos_public(Request $request) {

        try {

            $base_query = LiveVideo::homeResponse();

            $request->request->add(['type' => TYPE_PUBLIC]);

            // this function has all common check conditions for videos
            $base_query = LiveVideoRepo::live_videos_common_query($request, $base_query);

            $live_videos = $base_query->skip($this->skip)->take($this->take)
                                ->orderBy('live_videos.id', 'desc')
                                ->get();

            $live_videos = LiveVideoRepo::live_videos_list_response($live_videos, $request);

            return $this->sendResponse($message = '', $code = '', $live_videos);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method live_videos_private()
     *
     * @uses get the current live streaming videos
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param
     * 
     * @return JSON response
     *
     */

    public function live_videos_private(Request $request) {

        try {

            $base_query = LiveVideo::homeResponse();

            $request->request->add(['type' => TYPE_PRIVATE]);

            // this function has all common check conditions for videos
            $base_query = LiveVideoRepo::live_videos_common_query($request, $base_query);

            $live_videos = $base_query->skip($this->skip)->take($this->take)
                                ->orderBy('live_videos.id', 'desc')
                                ->get();

            $live_videos = LiveVideoRepo::live_videos_list_response($live_videos, $request);

            return $this->sendResponse($message = '', $code = '', $live_videos);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method live_videos_view()
     *
     * @uses get the current live streaming videos
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param
     * 
     * @return JSON response
     *
     */

    public function live_videos_view(Request $request) {

        try {

            // Validation start

            $rules = ['live_video_id' => 'required|exists:live_videos,id'];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);
            
            // Validation end

            $base_query = LiveVideo::homeResponse();

            // this function has all common check conditions for videos
            $base_query = LiveVideoRepo::live_videos_common_query($request, $base_query);

            $live_video_details = $base_query->first();

            if(!$live_video_details) {

                throw new Exception(api_error(161), 161);
                
            }

            $live_video_user = User::find($live_video_details->user_id);

            $live_video_details->share_link = Setting::get('ANGULAR_URL');

            $live_video_details->is_user_needs_to_pay = Helper::live_videos_check_payment($live_video_details, $request->id); 

            $live_video_details->redirect_web_url = get_antmedia_playurl($redirect_web_url = "", $live_video_details, $live_video_user);

            $request->request->add(['broadcast_type' => $live_video_details->broadcast_type, 'virtual_id' => $live_video_details->virtual_id, 'live_video_id' => $live_video_details->live_video_id]);

            $live_video_details->mobile_live_streaming_url = Helper::get_mobile_live_streaming_url($request);

            return $this->sendResponse($message = '', $code = '', $live_video_details);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method live_videos_search()
     *
     * @uses get the current live streaming videos
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param
     * 
     * @return JSON response
     *
     */

    public function live_videos_search(Request $request) {

        try {

            $base_query = LiveVideo::homeResponse();

            // this function has all common check conditions for videos
            $base_query = LiveVideoRepo::live_videos_common_query($request, $base_query);

            // search query

            $base_query = $base_query->where('title', 'like', "%".$request->key."%");

            $live_videos = $base_query->skip($this->skip)->take($this->take)
                                ->orderBy('live_videos.id', 'desc')
                                ->get();

            $live_videos = LiveVideoRepo::live_videos_list_response($live_videos, $request);

            return $this->sendResponse($message = '', $code = '', $live_videos);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /**
     * @method live_videos_chat()
     *
     * @uses used to get the messages for selected live video
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     *
     * @return response of details
     */
    public function live_videos_chat(Request $request) {

        try {

            // Validation start

            $rules = [
                    'live_video_id' => 'required|exists:live_videos,id', 
                ];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);

            // Validation end

            $chat_messages = ChatMessage::where('live_video_id', $request->live_video_id)
                            ->skip($this->skip)->take($this->take)
                            ->orderBy('chat_messages.id' , 'desc')
                            ->get();

            foreach ($chat_messages as $key => $chat_message_details) {

                $user_details = $chat_message_details->type == 'uv' ? $chat_message_details->getUser : $chat_message_details->getViewUser;

                $chat_message_details->user_name = $user_details->name ?? "user-deleted";

                $chat_message_details->user_picture = $user_details->picture ?? asset('placeholder.jpg');

                $chat_message_details->updated = common_date($chat_message_details->updated_at, $this->timezone);

                unset($chat_message_details->getViewUser);

                unset($chat_message_details->getUser);
                
            }

            // $chat_messages = $chat_messages->reverse()->values();

            return $this->sendResponse($message = "", $code = "", $chat_messages);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /** 
     * @method live_videos_check_coupon_code()
     *
     * @uses get the current live streaming videos
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param
     * 
     * @return JSON response
     *
     */

    public function live_videos_check_coupon_code(Request $request) {

        try {

            // Validation start

            $rules = [
                    'live_video_id' => 'required|exists:live_videos,id',
                    'coupon_code' => 'nullable|exists:coupons,coupon_code',
                    ];

            $custom_errors = ['live_video_id' => api_error(150)];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);
            
            // Validation end

            // Check the live video is streaming

            $live_video_details = LiveVideo::where('id',  $request->live_video_id)
                                    // ->CurrentLive()
                                    ->first();

            if(!$live_video_details) {

                throw new Exception(api_error(161), 161);
                
            }

            $live_video_payment = LiveVideoPayment::where('live_video_viewer_id', $request->id)->where('live_video_id', $request->live_video_id)->where('status', DEFAULT_TRUE)->count();

            // check the live video payment status || whether user already paid

            if($live_video_details->payment_status == NO || $live_video_payment) {

                throw new Exception(api_error(167), 167);
                
            }

            // Coupon code availablity and calculator

            $coupon_response = PaymentRepo::coupon_code_check_availablity($request)->getData();

            if($coupon_response->success == false) {
                throw new Exception($coupon_response->error, $coupon_response->error_code);
            }

            $coupon_code_details = $coupon_response->data;

            $calculator_response = PaymentRepo::coupon_code_calcualtor($coupon_code_details, $live_video_details->amount)->getData();

            if($coupon_response->success == false) {
                
                throw new Exception($coupon_response->error, $coupon_response->error_code);
            }

            $data = $calculator_response->data;

            $data->live_video_id = $request->live_video_id;

            return $this->sendResponse($message="", $code="", $data);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method live_videos_payment_by_card()
     *
     * @uses get the current live streaming videos
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param
     * 
     * @return JSON response
     *
     */

    public function live_videos_payment_by_card(Request $request) {

        try {

            DB::beginTransaction();

            // Validation start

            $rules = [
                    'live_video_id' => 'required|exists:live_videos,id',
                    'coupon_code' => 'nullable|exists:coupons,coupon_code',
                    ];

            $custom_errors = ['live_video_id' => api_error(150)];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);
            
            // Validation end

            // Check the live video is streaming

            $live_video_details = LiveVideo::where('id',  $request->live_video_id)
                                    // ->CurrentLive()
                                    ->first();

            if(!$live_video_details) {

                throw new Exception(api_error(161), 161);
                
            }

            $live_video_payment = LiveVideoPayment::where('live_video_viewer_id', $request->id)->where('live_video_id', $request->live_video_id)->where('status', DEFAULT_TRUE)->count();

            // check the live video payment status || whether user already paid

            if($live_video_details->payment_status == NO || $live_video_payment) {

                $code = 136;

                goto successReponse;
                
            }

            $request->request->add(['payment_mode' => CARD]);

            $total = $user_pay_amount = $live_video_details->amount ?? 0.00;

            /** COUPON CODE STRAT */

            $coupon_amount = 0.00;  $is_coupon_code_applied = NO; $coupon_reason = "";

            if($request->coupon_code) {

                // Coupon code availablity and calculator

                $coupon_response = PaymentRepo::coupon_code_check_availablity($request)->getData();

                if($coupon_response->success == false) {

                    $coupon_reason = $coupon_response->error;

                } else {

                    $coupon_code_details = $coupon_response->data;

                    $calculator_response = PaymentRepo::coupon_code_calcualtor($coupon_code_details, $total)->getData();

                    if($coupon_response->success == false) {
                        $coupon_reason = $coupon_response->error;
                    }

                    $coupon_amount = $calculator_response->data->coupon_amount ?? 0.00;

                    $user_pay_amount = $calculator_response->data->user_pay_amount ?? 0.00;
                }

            } 

            /** COUPON CODE END */

            $request->request->add([
                'total' => $total, 
                'coupon_code' => $request->coupon_code,
                'coupon_amount' => $coupon_amount, 
                'is_coupon_code_applied' => $is_coupon_code_applied, 
                'coupon_reason' => $coupon_reason,
                'user_pay_amount' => $user_pay_amount,
                'paid_amount' => $user_pay_amount,
                'payment_id' => 'FREE-'.rand()
            ]);

            if($user_pay_amount > 0) {

                // Check the user have the cards

                $card_details = Card::where('user_id', $request->id)->where('is_default', YES)->first();

                // If the user doesn't have cards means the payment will switch to COD

                if(!$card_details) {

                    throw new Exception(api_error(163), 163); 

                }

                $request->request->add(['customer_id' => $card_details->customer_id]);
                
                $card_payment_response = PaymentRepo::live_videos_payment_by_stripe($request, $live_video_details)->getData();

                if($card_payment_response->success == false) {

                    throw new Exception($card_payment_response->error, $card_payment_response->error_code);
                    
                }

                $card_payment_data = $card_payment_response->data;

                $request->request->add(['paid_amount' => $card_payment_data->paid_amount, 'payment_id' => $card_payment_data->payment_id, 'paid_status' => $card_payment_data->paid_status]);

            }

            $payment_response = PaymentRepo::live_videos_payment_save($request, $live_video_details)->getData();

            if($payment_response->success) {
                
                DB::commit();

                $code = 137;

            } else {

                throw new Exception($payment_response->error, $payment_response->error_code);
            }          

            successReponse:

            $data['live_video_id'] = $request->live_video_id;

            $data['payment_mode'] = CARD;

            return $this->sendResponse(api_success($code), $code, $data);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method live_videos_payment_by_paypal()
     *
     * @uses get the current live streaming videos
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param
     * 
     * @return JSON response
     *
     */

    public function live_videos_payment_by_paypal(Request $request) {

        try {

            DB::beginTransaction();

            // Validation start

            $rules = [
                    'live_video_id' => 'required|exists:live_videos,id',
                    'coupon_code' => 'nullable|exists:coupons,coupon_code',
                    'payment_id' => 'required',
                    ];

            $custom_errors = ['live_video_id' => api_error(150)];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);
            
            // Validation end

            // Check the live video is streaming

            $live_video_details = LiveVideo::where('id',  $request->live_video_id)
                                    // ->CurrentLive()
                                    ->first();

            if(!$live_video_details) {

                throw new Exception(api_error(161), 161);
                
            }

            $live_video_payment = LiveVideoPayment::where('live_video_viewer_id', $request->id)->where('live_video_id', $request->live_video_id)->where('status', DEFAULT_TRUE)->count();

            // check the live video payment status || whether user already paid

            if($live_video_details->payment_status == NO || $live_video_payment) {

                $code = 136;

                goto successReponse;
                
            }

            $request->request->add(['payment_mode' => PAYPAL]);

            $total = $user_pay_amount = $live_video_details->amount ?? 0.00;

            /** COUPON CODE STRAT */

            $coupon_amount = 0.00;  $is_coupon_code_applied = NO; $coupon_reason = "";

            if($request->coupon_code) {

                // Coupon code availablity and calculator

                $coupon_response = PaymentRepo::coupon_code_check_availablity($request)->getData();

                if($coupon_response->success == false) {

                    $coupon_reason = $coupon_response->error;

                } else {

                    $coupon_code_details = $coupon_response->data;

                    $calculator_response = PaymentRepo::coupon_code_calcualtor($coupon_code_details, $total)->getData();

                    if($coupon_response->success == false) {
                        $coupon_reason = $coupon_response->error;
                    }

                    $coupon_amount = $calculator_response->data->coupon_amount ?? 0.00;

                    $user_pay_amount = $calculator_response->data->user_pay_amount ?? 0.00;
                }

            } 

            /** COUPON CODE END */

            $request->request->add([
                'total' => $total, 
                'coupon_code' => $request->coupon_code,
                'coupon_amount' => $coupon_amount, 
                'is_coupon_code_applied' => $is_coupon_code_applied, 
                'coupon_reason' => $coupon_reason,
                'user_pay_amount' => $user_pay_amount,
                'paid_amount' => $user_pay_amount,
            ]);

            $payment_response = PaymentRepo::live_videos_payment_save($request, $live_video_details)->getData();

            if($payment_response->success) {
                
                DB::commit();

                $code = 137;

            } else {

                throw new Exception($payment_response->error, $payment_response->error_code);
                
            }            

            successReponse:

            $data['live_video_id'] = $request->live_video_id;

            $data['payment_mode'] = PAYPAL;

            return $this->sendResponse(api_success($code), $code, $data);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method live_videos_chat_save()
     *
     * @uses get the current live streaming videos
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param
     * 
     * @return JSON response
     *
     */

    public function live_videos_chat_save(Request $request) {

        try {

            $query = CustomLiveVideo::LiveVideoResponse()->orderBy('custom_live_videos.created_at', 'desc');

            $custom_live_videos = $query->skip($this->skip)->take($this->take)->get();

            foreach ($custom_live_videos as $key => $custom_live_video) {
                $custom_live_video->share_link = "";
            }

            return $this->sendResponse($message = '', $code = '', $custom_live_videos);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method live_videos_payment_history()
     *
     * @uses get the current live streaming videos
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param
     * 
     * @return JSON response
     *
     */

    public function live_videos_payment_history(Request $request) {

        try {

            $live_video_payments = LiveVideoPayment::where('live_video_viewer_id', $request->id)
                                    ->skip($this->skip)->take($this->take)
                                    ->orderBy('live_video_payments.id', 'desc')
                                    ->get();

            foreach ($live_video_payments as $key => $live_video_payment) {

                $live_video_details = $live_video_payment->getVideo ?? [];

                $live_video_payment->title = $live_video_details->title ?? "-";

                $live_video_payment->description = $live_video_details->description ?? "-";

                $live_video_payment->snapshot = $live_video_details->snapshot ?? asset('default-image.jpg');

                $user_details = $live_video_payment->getUser ?? [];

                $live_video_payment->user_name = $user_details->name ?? "user-deleted";

                $live_video_payment->user_picture = $user_details->picture ?? asset('placeholder.jpg');

                unset($live_video_payment->getUser);

                unset($live_video_payment->getVideo);
            }

            return $this->sendResponse($message = '', $code = '', $live_video_payments);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method live_videos_broadcast_start()
     * @method live_events_schedule_start()
     *
     * @uses get the current live streaming videos
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param
     * 
     * @return JSON response
     *
     */

    public function live_videos_broadcast_start(Request $request) {

        try {

            DB::beginTransaction();

            // Validation start

            $rules = [
                    'title' => 'required',
                    'description' => 'required|max:255',
                    'payment_status'=>'required|numeric',
                    'amount' => $request->payment_status ? 'required|numeric|min:0.01|max:100000' : '',
                    'type' => 'required|in:'.TYPE_PRIVATE.','.TYPE_PUBLIC,
                    'live_group_id' => $request->live_group_id > 0 ? 'integer|exists:live_groups,id' : "",
                    ];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);
            
            // Validation end

            // Check the whether live streaming configured or not

            $is_live_streaming_configured = is_live_streaming_configured();

            if($is_live_streaming_configured == NO) {

                throw new Exception(api_error(172), 172);
            }

            $user_details = $this->loginUser;

            // check the user paid and content creator

            if($user_details->user_type != SUBSCRIBED_USER) {

                throw new Exception(api_error(132), 132);
            }

            if($user_details->is_content_creator == VIEWER_STATUS) {

                throw new Exception(api_error(173), 173);
            }

            // Check the user have any ongoing streaming

            $check_ongoing_streaming = LiveVideo::where('user_id', $request->id)->where('status', VIDEO_STREAMING_ONGOING)->count();

            if($check_ongoing_streaming) {
                throw new Exception(api_error(174), 174);
            }

            // Check the live group 
            
            if($request->live_group_id) {

                // Check the group details

                $group_details = LiveGroup::where('live_groups.id', $request->live_group_id)
                            ->where('live_groups.status', LIVE_GROUP_APPROVED)
                            ->leftJoin('live_group_members', 'live_group_members.live_group_id', '=', 'live_groups.id')
                            ->where('live_groups.user_id', $request->id)
                            ->orWhere('live_group_members.member_id', $request->id)
                            ->first();

                if(!$group_details) {

                    throw new Exception(api_error(175), 175);
                    
                }

            }

            $live_video_details = new LiveVideo;

            $live_video_details->user_id = $request->id;

            $live_video_details->title = $request->title;

            $live_video_details->description = $request->description ?? "";

            $live_video_details->snapshot = Setting::get('live_streaming_placeholder_img');

            $live_video_details->type = $request->type ?? TYPE_PUBLIC;
            
            $live_video_details->broadcast_type = $request->broadcast_type ?? BROADCAST_TYPE_BROADCAST;

            $live_video_details->payment_status = $request->payment_status ?? FREE_VIDEO;

            $live_video_details->amount = $request->amount ?? 0.00;

            $live_video_details->live_group_id = $request->live_group_id ?? 0;

            $live_video_details->status = VIDEO_STREAMING_ONGOING;

            $live_video_details->is_streaming = IS_STREAMING_YES;

            $live_video_details->virtual_id = md5(time());

            $live_video_details->unique_id = $live_video_details->title ?? "";

            $live_video_details->browser_name = $request->browser ?? '';

            $live_video_details->start_time = getUserTime(date('H:i:s'), $this->timezone, "H:i:s");

            $live_video_details->stream_key = routefreestring(strtolower($request->title.rand(1,10000).rand(1,10000) ?: rand(1,10000).rand(1,10000)));

            $live_video_details->save();

            DB::commit();

            $this->dispatch(new \App\Jobs\LiveVideoNotificationToFollower($request->id, $live_video_details->id, $request->live_group_id));

            $data = LiveVideo::where('live_videos.id', $live_video_details->id)->homeResponse()->first();

            return $this->sendResponse(api_success(141), $code = 141, $data);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    public function live_events_schedule_start(Request $request) {
        
        try {
            
            DB::beginTransaction();
            
            $rules = [
                'name' => 'required|max:255',
                'email' => 'required|max:255',
                'date' => 'required',
                'url' => 'required',
                'description' => 'max:255'
            ];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);
            
            $user_details = $this->loginUser;

            // if(!$user_details) {

            //     throw new Exception(api_error(1002), 1002);
                
            // }
            
            $request->url = substr($request->url, 5);
            
            $events = new Event;
            $events->name = $request->name;
            $events->email = $request->email;
            $events->date = $request->date;
            // $events->url = $request->url;
            $events->description = $request->description;

            if ($request->hasFile('url') != "") {
                Helper::storage_delete_file($events->url, USER_PATH); // Delete the old pic
                
                Helper::delete_avatar(USER_CHAT_PATH, $events->chat_picture); // Delete the old pic
                
                $events->url = Helper::storage_upload_file($request->file('url'), USER_PATH);

                Log::debug($events->url);
            }

            if ($events->save()) {
                

                $data = DB::table('events')->latest()->take(6)->get();
                DB::commit();
                $return = [];
                
                $return = $data;
    
                return $this->sendResponse(api_success(202), $code = 202, $return[0] );
            }
            throw new Exception(api_error(128), 128);
        } catch(Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    public function live_events_schedule_get() {
        
        try {
            
            DB::beginTransaction();
            
            $data = DB::table('events')->latest()->take(6)->get();
            
            $return = [];
            array_push($return, $data);
            
            Log::debug('Here is ScheduleAPI function!');
            
            return $this->sendResponse(api_success(202), $code = 202, $return[0] );

        } catch(Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }
    public function live_events_schedule_all() {
        
        try {
            
            DB::beginTransaction();
            
            $data = DB::table('events')->get();
            
            $return = [];
            array_push($return, $data);
            
            return $this->sendResponse(api_success(202), $code = 202, $return[0] );

        } catch(Exception $e) {
            DB::rollback();
            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method live_videos_viewer_update()
     *
     * @uses get the current live streaming videos
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param
     * 
     * @return JSON response
     *
     */

    public function live_videos_viewer_update(Request $request) {

        try {

            DB::beginTransaction();

            // Validation start

            $rules = ['live_video_id' => 'required|exists:live_videos,id'];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);
            
            // Validation end

            $live_video_details = LiveVideo::where('live_videos.id', $request->live_video_id)->first();

            if(!$live_video_details) {

                throw new Exception(api_error(150), 150);
                
            }

            if($live_video_details->is_streaming == IS_STREAMING_NO || $live_video_details->status == VIDEO_STREAMING_STOPPED) {

                throw new Exception(api_error(171), 171);
                
            }

            if ($live_video_details->user_id == $request->id) {
                
                throw new Exception(api_error(171), 171);

            }

            $viewer_details = Viewer::where('video_id', $request->live_video_id)->where('user_id', $request->id)->first() ?? new Viewer;

            $viewer_details->user_id = $request->id;

            $viewer_details->video_id = $request->live_video_id;

            $viewer_details->count += 1;

            $viewer_details->save();

            $live_video_details->viewer_cnt += 1;

            $live_video_details->save();

            DB::commit();

            $data = ['live_video_id' => $request->live_video_id, 'viewer_cnt' => $live_video_details->viewer_cnt];

            return $this->sendResponse(api_success(140), $code = 140, $data);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method live_videos_snapshot_save()
     *
     * @uses get the current live streaming videos
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param
     * 
     * @return JSON response
     *
     */

    public function live_videos_snapshot_save(Request $request) {

        try {

            DB::beginTransaction();

            // Validation start

            $rules = ['live_video_id' => 'required|exists:live_videos,id', 'snapshot' => 'required'];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);
            
            // Validation end

            $live_video_details = LiveVideo::where('live_videos.id', $request->live_video_id)->first();

            if(!$live_video_details) {

                throw new Exception(api_error(150), 150);
                
            }

            if($live_video_details->is_streaming == IS_STREAMING_NO || $live_video_details->status == VIDEO_STREAMING_STOPPED) {

                throw new Exception(api_error(171), 171);
                
            }

            if ($request->device_type == DEVICE_IOS) {

                $picture = $request->file('snapshot');
                
                $ext = $picture->getClientOriginalExtension();

                $picture->move(public_path().'/uploads/rooms/', $request->live_video_id . "." . $ext);

                $live_video_details->snapshot = url('/').'/uploads/rooms/'.$request->live_video_id . '.png';

            } else {

                $data = explode(',', $request->get('snapshot'));

                file_put_contents(join(DIRECTORY_SEPARATOR, [public_path(), 'uploads', 'rooms', $request->live_video_id . '.png']), base64_decode($data[1]));

                $live_video_details->snapshot = url('/').'/uploads/rooms/'.$request->live_video_id . '.png';
            }  

            $live_video_details->save();

            // @todo Wowza stop 

            DB::commit();

            $data = ['live_video_id' => $request->live_video_id];

            return $this->sendResponse(api_success(139), $code = 139, $data);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method live_videos_broadcast_stop()
     *
     * @uses get the current live streaming videos
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param
     * 
     * @return JSON response
     *
     */

    public function live_videos_broadcast_stop(Request $request) {

        try {
            
            DB::beginTransaction();

            // Validation start
            $rules = ['live_video_id' => 'required|exists:live_videos,id'];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);
            
            // Validation end

            $live_video_details = LiveVideo::where('live_videos.id', $request->live_video_id)->first();

            if(!$live_video_details) {

                throw new Exception(api_error(150), 150);
                
            }
            
            if($live_video_details->is_streaming == IS_STREAMING_NO || $live_video_details->status == VIDEO_STREAMING_STOPPED) {

                throw new Exception(api_error(171), 171);
                
            }
            
            $live_video_details->status = VIDEO_STREAMING_STOPPED;

            $live_video_details->save();

            $live_video_details->end_time = common_date(date('H:i:s'), $this->timezone, 'H:i:s');
            
            $live_video_details->no_of_minutes = getMinutesBetweenTime($live_video_details->start_time, $live_video_details->end_time);

            $live_video_details->save();

            // @todo Wowza stop 

            DB::commit();

            $data = ['live_video_id' => $request->live_video_id];

            return $this->sendResponse(api_success(138), $code = 138, $data);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method live_videos_check_streaming()
     *
     * @uses get the current live streaming videos
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param
     * 
     * @return JSON response
     *
     */

    public function live_videos_check_streaming(Request $request) {

        try {

            // Validation start

            $rules = ['live_video_id' => 'required|exists:live_videos,id'];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);
            
            // Validation end

            $live_video_details = LiveVideo::where('live_videos.id', $request->live_video_id)->first();

            if(!$live_video_details) {

                throw new Exception(api_error(150), 150);
                
            }

            if($live_video_details->is_streaming == IS_STREAMING_NO) {

                throw new Exception(api_error(170), 170);
            
            }

            if($live_video_details->status == VIDEO_STREAMING_STOPPED) {

                throw new Exception(api_error(169), 169);
                
            }

            $data = ['viewer_cnt' => $live_video_details->viewer_cnt];

            return $this->sendResponse($message = '', $code = '', $data);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method live_videos_erase_old_streamings()
     *
     * @uses get the current live streaming videos
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param
     * 
     * @return JSON response
     *
     */

    public function live_videos_erase_old_streamings(Request $request) {

        try {

            DB::beginTransaction();

            LiveVideo::where('user_id', $request->id)->where('status', VIDEO_STREAMING_ONGOING)->where('is_streaming', IS_STREAMING_NO)->delete();

            $live_videos = LiveVideo::where('user_id', $request->id)->where('status', VIDEO_STREAMING_ONGOING)->where('is_streaming', IS_STREAMING_YES)->get();

            foreach($live_videos as $key => $live_video) {

                $live_video->status = DEFAULT_TRUE;

                $live_video->end_time = getUserTime(date('H:i:s'), $this->timezone, 'H:i:s');

                $live_video->no_of_minutes = getMinutesBetweenTime($live_video->start_time, $live_video->end_time);

                $live_video->save();

            }

            DB::commit();

            return $this->sendResponse(api_success(142), $code = 142, $data = []);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }


    /** 
     * @method live_videos_owner_list()
     *
     * @uses get the current live streaming videos
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param
     * 
     * @return JSON response
     *
     */

    public function live_videos_owner_list(Request $request) {

        try {

            $base_query = LiveVideo::listResponse()->where('live_videos.user_id', $request->id);

            $live_videos = $base_query->skip($this->skip)->take($this->take)
                                ->orderBy('live_videos.id', 'desc')
                                ->get();

            return $this->sendResponse($message = '', $code = '', $live_videos);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method live_videos_owner_view()
     *
     * @uses get the current live streaming videos
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param
     * 
     * @return JSON response
     *
     */

    public function live_videos_owner_view(Request $request) {

        try {

            // Validation start

            $rules = [
                    'live_video_id' => 'required|exists:live_videos,id'
                    ];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);
            
            // Validation end

            $live_video_details = LiveVideo::homeResponse()->where('live_videos.id', $request->live_video_id)->where('live_videos.user_id', $request->id)->first();

            if(!$live_video_details) {

                throw new Exception(api_error(150), 150);
                
            }

            return $this->sendResponse($message = '', $code = '', $live_video_details);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method live_videos_suggestions()
     *
     * @uses suggestions for the users
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param
     * 
     * @return JSON response
     *
     */

    public function live_videos_suggestions(Request $request) {

        try {

            $base_query = LiveVideo::homeResponse();

            // this function has all common check conditions for videos
            $base_query = LiveVideoRepo::live_videos_common_query($request, $base_query);

            $base_query = $base_query->skip($this->skip)->take($this->take)->orderBy(DB::raw('RAND()'));

            $live_videos = $base_query->get();

            $live_videos = LiveVideoRepo::live_videos_list_response($live_videos, $request);

            return $this->sendResponse($message = '', $code = '', $live_videos);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method live_videos_suggestions()
     *
     * @uses suggestions for the users
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param
     * 
     * @return JSON response
     *
     */

    public function live_videos_popular(Request $request) {

        try {

            $base_query = LiveVideo::homeResponse();

            // this function has all common check conditions for videos
            $base_query = LiveVideoRepo::live_videos_common_query($request, $base_query);

            $base_query = $base_query->skip($this->skip)->take($this->take)->orderBy('live_videos.viewer_cnt', 'desc');
            
            $live_videos = $base_query->get();

            $live_videos = LiveVideoRepo::live_videos_list_response($live_videos, $request);

            return $this->sendResponse($message = '', $code = '', $live_videos);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }


    /**
     * @method live_videos_groups_list()
     *
     * @uses used to list all the groups owned or joined groups
     *
     * @created vithya R 
     *
     * @updated vithya R
     *
     * @param object id, token and type
     *
     * @return json response of the user
     */
    public function live_videos_groups_list(Request $request) {

        try {

            $groups_query = LiveGroup::where('live_groups.status', LIVE_GROUP_APPROVED);

            $groups_query = $groups_query->where('live_groups.user_id' , $request->id)
                        ->leftJoin('live_group_members' , 'live_groups.id' , '=', 'live_group_members.live_group_id' )
                        ->orWhere('live_group_members.member_id', $request->id);

            $groups = $groups_query->baseResponse()->groupBy('live_groups.id')->get();

            $data['groups'] = $groups;

            $data['total_groups'] = count($groups);

            return $this->sendResponse($message = "", $code = "", $data);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

}
