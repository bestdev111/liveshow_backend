<?php

namespace App\Repositories;

use Illuminate\Http\Request;

use App\Helpers\Helper;

use Validator, Hash, Log, Setting, DB, Exception;

use App\PayPerView, App\VodVideo, App\User;

class VodRepository {

    /**
     * @method vod_videos_save()
     *
     * To save the uploadeed video by the content creator
     *
     * @created -
     *
     * @updated -
     *
     * @param object $request - VOD details
     *
     * @return response of jsonsuccess/ failure mesage 
     */
    public static function vod_videos_save(Request $request) {
        
        try {

            DB::beginTransaction();

            // validation start

            $rules = [
                    'title' => 'required|max:128',
                    'description' => '',
                    'image' => $request->video_id ? 'nullable|mimes:jpeg,jpg,bmp,png' : 'required|mimes:jpeg,jpg,bmp,png',
                    'video'=> $request->video_id ? 'nullable|mimetypes:video/mp4' : 'required|mimetypes:video/mp4',
                    'video_id' => 'nullable|exists:vod_videos,unique_id',
                    'user_id' => 'required|exists:users,id',
                    'publish_time' => $request->publish_type == PUBLISH_LATER ? 'required' : 'nullable',
                    'publish_type' => 'in:'.PUBLISH_LATER.','.PUBLISH_NOW
            ];

            $custom_errors = [
                'video.mimetypes' => 'Invalid Format'
            ];
            
            Helper::custom_validator($request->all(), $rules, $custom_errors);
            
            // validation end
            
            $user_details = User::find($request->user_id);

            $model = $request->video_id ? VodVideo::where('unique_id', $request->video_id)->first() :  new VodVideo;

            $model->title = $request->title;

            $model->description = $request->description;

            $model->unique_id = $model->title;
            
            if($request->hasFile('image')) {

                if ($model->id) {

                    Helper::storage_delete_file($model->image, VOD_VIDEO_IMAGE_PATH); // Delete the old pic
                } 

                $model->image = Helper::storage_upload_file($request->file('image'), VOD_VIDEO_IMAGE_PATH);

            }

            if($request->hasFile('video')) {

                if ($model->id) {

                    Helper::storage_delete_file($model->video, VOD_VIDEO_VIDEO_PATH); 
                } 

                $model->video = Helper::storage_upload_file($request->file('video'), VOD_VIDEO_VIDEO_PATH);

            }  

            if(!$request->video_id) {

                $model->status = VOD_APPROVED_BY_USER;

                $model->admin_status = VOD_APPROVED_BY_ADMIN;

                $model->created_by = $request->created_by ?? ADMIN;

            }      

            $model->user_id = $request->user_id;

            // Check the publish type based on that convert the time to timezone

            if($request->publish_type == PUBLISH_LATER) {


                $timezone = $user_details->timezone ?? 'Asia/Kolkata';

                $user_current_time = common_date(date('Y-m-d H:i:s'), $timezone, 'Y-m-d H:i:s');

                if(strtotime($request->publish_time) <= strtotime($user_current_time)) {

                    throw new Exception(Helper::error_message(166), 166);

                }

                $model->publish_time = date('Y-m-d H:i:s', strtotime($request->publish_time));

                // Based on publishing time the status will change

                $model->publish_status = VIDEO_NOT_YET_PUBLISHED;

            } else {

                $model->publish_time = date('Y-m-d H:i:s');

                $model->publish_status = VIDEO_PUBLISHED;

            }

            if($model->save()) {

            } else {

                throw new Exception(tr('video_not_upload_proper'));
                
            }

            DB::commit();

            $model = VodVideo::vodResponse()->find($model->id);

             $message = $request->video_id !='' ? tr('live_custom_video_update_success') : tr('video_uploaded_success');
            
            $response_array = ['success' => true, 'message' => $message, 'data'=>$model];

            return response()->json($response_array);

        } catch (Exception $e) {

            DB::rollback();

            $response_array = ['success'=>false, 'error_messages'=>$e->getMessage(), 'error_code'=>$e->getCode()];

            return response()->json($response_array);

        }

    }

    /**
     * @method vod_videos_status()
     *
     * To changes the video status as approve/decline by using this functonion
     *
     * @created -
     *
     * @updated -
     *
     * @param object $request - user id, token , status
     *
     * @return response of success/failure message
     */
    public static function vod_videos_status(Request $request) {

        try {

            DB::beginTransaction();

            $validator = Validator::make($request->all(),
                array(
                    'video_id'=>'required|exists:vod_videos,id,user_id,'.$request->user_id
                ));

            if ($validator->fails()) {

                // Error messages added in response for debugging
                
                $errors = implode(',',$validator->messages()->all());

                throw new Exception($errors, 101);


            } else {

                $model = VodVideo::find($request->video_id);

                if ($request->decline_by == CREATOR) {

                    $model->status = $model->status ? VOD_DECLINED_BY_USER : VOD_APPROVED_BY_USER;

                    $status = $model->status;

                } else {

                    $model->admin_status = $model->admin_status ? VOD_DECLINED_BY_ADMIN : VOD_APPROVED_BY_ADMIN;

                    $status = $model->admin_status;

                }

                if ($model->save()) {

                   

                } else {

                    throw new Exception(tr('vod_failure_status'));
                    
                }
            }

            DB::commit();

            $response_array = ['success'=>true, 'message'=>($status) ? tr('video_approve_success') : tr('video_decline_success')];

            return response()->json($response_array);

        } catch (Exception $e) {

            DB::rollback();

            $response_array = ['success'=>false, 'error_messages'=>$e->getMessage(), 'error_code'=>$e->getCode()];

            return response()->json($response_array);

        }

    }

    /**
     * @method vod_videos_delete
     *
     * To delete vod video based on the video id
     *
     * @created -
     *
     * @updated -
     *
     * @param object $request - User id, token & Video id
     *
     * @return response of json success/failure message
     */
    public static function vod_videos_delete(Request $request) {

        try {

            DB::beginTransaction();

            $validator = Validator::make($request->all(),
                array(
                    'video_id'=>'required|exists:vod_videos,id,user_id,'.$request->user_id,
                ));

            if ($validator->fails()) {

                // Error messages added in response for debugging
                
                $errors = implode(',',$validator->messages()->all());

                throw new Exception($errors, 101);


            } else {

                $model = VodVideo::find($request->video_id);

                if ($model->delete()) {

                } else {

                    throw new Exception(tr('vod_delete_failure'));
                    
                }

            }

            DB::commit();

            $response_array = ['success'=>true, 'message'=>tr('vod_delete_success')];

            return response()->json($response_array);

        } catch (Exception $e) {

            DB::rollback();

            $response_array = ['success'=>false, 'error_messages'=>$e->getMessage(), 'error_code'=>$e->getCode()];

            return response()->json($response_array);

        }
    
    }


    /**
     * @method vod_videos_set_ppv()
     *
     * To set pay per view in VOD video based on video id
     *
     * @created -
     *
     * @updated -
     *
     * @param object $request - User id, token, video id, ppv details
     *
     * @return response of json success/failure message
     */
    public static function vod_videos_set_ppv(Request $request) {

        try {

            DB::beginTransaction();

            $validator = Validator::make($request->all(),
                array(
                    'video_id'=>'required|exists:vod_videos,id,user_id,'.$request->user_id.',status,'.DEFAULT_TRUE,
                    'amount'=>'required|numeric|min:0.1|max:100000',
                    'type_of_subscription'=>'required|in:'.ONE_TIME_PAYMENT.','.RECURRING_PAYMENT,
                    // 'type_of_user'=>'in:'.NORMAL_USER.','.PAID_USER.','.BOTH_USERS,
                ), array(

                    'exists'=>'The selected video not available',
                ));

            if ($validator->fails()) {

                // Error messages added in response for debugging
                
                $errors = implode(',',$validator->messages()->all());

                throw new Exception($errors, 101);

            } else {

                $model = VodVideo::find($request->video_id);

                $model->amount = $request->amount ?? 0.00;

                $model->type_of_user = $request->type_of_user ?? BOTH_USERS;

                $model->type_of_subscription = $request->type_of_subscription ?? ONE_TIME_PAYMENT;

                $model->is_pay_per_view = PPV_ENABLED;

                if ($model->save()) {

                } else {

                    throw new Exception(tr('ppv_couldnt_set'));
                    
                }

            }

            DB::commit();

            $data['video_id'] = $model->id;
            
            $data['is_pay_per_view'] = $model->is_pay_per_view;

            $response_array = ['success'=>true, 'message' => tr('ppv_set_success'), 'data' => $data];

            return response()->json($response_array);

        } catch (Exception $e) {

            DB::rollback();

            $response_array = ['success'=>false, 'error_messages'=>$e->getMessage(), 'error_code'=>$e->getCode()];

            return response()->json($response_array);

        }

    }


    /**
     * @method vod_videos_remove_ppv()
     *
     * To remove pay per view in VOD video based on video id
     *
     * @created -
     *
     * @updated -
     *
     * @param object $request - User id, token, video id, ppv details
     *
     * @return response of json success/failure message
     */
    public static function vod_videos_remove_ppv(Request $request) {

        try {


            DB::beginTransaction();

            $validator = Validator::make($request->all(),
                array(
                    'video_id'=>'required|exists:vod_videos,id,user_id,'.$request->user_id.',status,'.DEFAULT_TRUE,
                ), array(

                    'exists'=>'The selected video not available',
                ));

            if ($validator->fails()) {

                // Error messages added in response for debugging
                
                $errors = implode(',',$validator->messages()->all());

                throw new Exception($errors, 101);


            } else {

                $model = VodVideo::find($request->video_id);

                $model->amount = 0 ;

                $model->type_of_user = 0;

                $model->type_of_subscription = 0;

                $model->is_pay_per_view = PPV_DISABLED;

                if ($model->save()) {

                } else {

                    throw new Exception(tr('ppv_couldnt_remove'));
                    
                }

            }

            DB::commit();

            $response_array = ['success'=>true, 'message' => tr('ppv_remove_success')];

            return response()->json($response_array);

        } catch (Exception $e) {

            DB::rollback();

            $response_array = ['success'=>false, 'error_messages'=>$e->getMessage(), 'error_code'=>$e->getCode()];

            return response()->json($response_array);

        }

    }

    /**
     * @method vod_videos_publish()
     *
     * To Publish the video for user
     *
     * @created - -
     *
     * @updated - -  
     *
     * @param object $request : Video details with user details
     *
     * @return Flash Message
     */
    public static function vod_videos_publish(Request $request) {

        try {

            $validator = Validator::make($request->all(),
                array(
                    'video_id'=>'required|exists:vod_videos,id,user_id,'.$request->user_id
                ));

            if ($validator->fails()) {

                // Error messages added in response for debugging
                
                $errors = implode(',',$validator->messages()->all());

                throw new Exception($errors, 101);


            } else {

                $model = VodVideo::find($request->video_id);

                $user = User::find($request->user_id);

                $model->publish_status = VIDEO_PUBLISHED;

                $timezone = $user ? $user->timezone : 'Asia/Kolkata';

                $current_date_time = date('Y-m-d H:i:s');

                $converted_current_datetime = convertTimeToUSERzone($current_date_time, $timezone);

                // Check the publish type based on that convert the time to timezone

                $model->publish_time = $converted_current_datetime;

                if ($model->save()) {


                } else {

                    throw new Exception(tr('vod_published_video_failure'));
                    
                }

            }

            $response_array = ['success'=>true , 'message'=>tr('vod_published_video_success')];

            return response()->json($response_array);

        } catch (Exception $e) {

            $response_array = ['success'=>false, 'error_messages'=>$e->getMessage(), 'error_code'=>$e->getcode()];

            return response()->json($response_array);

        }
   
    }


    /**
     * @method
     *
     * @uses
     *
     * @created
     *
     * @updated
     *
     * @param
     *
     * @return
     */
    
    public static function vod_ppv_payment_by_stripe($customer_id, $total) {

        try {

            // Check stripe configuration
        
            $stripe_secret_key = Setting::get('stripe_secret_key');

            if(!$stripe_secret_key) {

                throw new Exception(Helper::error_message(107), 107);

            } 

            \Stripe\Stripe::setApiKey($stripe_secret_key);
           
            $currency_code = Setting::get('currency_code', 'USD') ?: "USD";

            $total = intval(round($total * 100));

            $charge_array = ['amount' => $total, 'currency' => $currency_code, 'customer' => $customer_id,
                            ];

            $stripe_payment_response =  \Stripe\Charge::create($charge_array);

            $data = new \stdClass;

            $data->payment_id = $stripe_payment_response->id ?? 'CARD-'.rand();

            $data->paid_amount = $stripe_payment_response->amount/100 ?? $total;

            $data->paid_status = $stripe_payment_response->paid ?? true;

            $response_array = ['success' => true, 'data' => $data];

            return response()->json($response_array, 200);

        }  catch(Stripe_CardError | Stripe_InvalidRequestError | Stripe_AuthenticationError | Stripe_ApiConnectionError | Stripe_Error $e) {         

            $response = ['success' => false, 'error' => $e->getMessage(), 'error_code' => $e->getCode()];

            return response()->json($response, 200);

        } catch(Exception $e) {

            $response_array = ['success' => false, 'error' => $e->getMessage()];

            return response()->json($response_array, 200);

        }

    }

}