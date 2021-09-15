<?php

use App\Helpers\EnvEditorHelper;

use App\Follower;

use App\MobileRegister;

use App\PageCounter;

use App\Settings;

use Carbon\Carbon;

use App\User;

use App\LiveVideo;

use App\LiveVideoPayment;

use App\Redeem;

use App\Admin;

use App\LiveGroupMember;

use App\BlockList;

function tr($key,$confirmation_content_key="") {

    if(Auth::guard('admin')->check()) {
        $locale = config('app.locale');
    } else {
        if (!\Session::has('locale')) {
            $locale = \Session::put('locale', config('app.locale'));
        }else {
            $locale = \Session::get('locale');
        }
    }
    return \Lang::choice('messages.'.$key, 0, Array('confirmation_content_key'=>$confirmation_content_key), $locale);
}

function api_success($key , $other_key = "" , $lang_path = "messages.") {

    if (!\Session::has('locale')) {

        $locale = \Session::put('locale', config('app.locale'));

    } else {

        $locale = \Session::get('locale');

    }
    return \Lang::choice('api-success.'.$key, 0, Array('other_key' => $other_key), $locale);
}

function api_error($key , $other_key = "" , $lang_path = "messages.") {

    if (!\Session::has('locale')) {

        $locale = \Session::put('locale', config('app.locale'));

    } else {

        $locale = \Session::get('locale');

    }
    return \Lang::choice('api-error.'.$key, 0, Array('other_key' => $other_key), $locale);
}


function register_mobile($device_type) {
    if($reg = MobileRegister::where('type' , $device_type)->first()) {
        $reg->count = $reg->count + 1;
        $reg->save();
    }
    
}

function envfile($key) {

    $data = EnvEditorHelper::getEnvValues();

    if($data) {
        return $data[$key];
    }

    return "";
}

function get_admin_mail() {
    return Admin::first();
}

//this function convert string to UTC time zone

function convertTimeToUTCzone($str, $userTimezone, $format = 'Y-m-d H:i:s') {

    try {
        $new_str = new DateTime($str, new DateTimeZone($userTimezone));

        $new_str->setTimeZone(new DateTimeZone('UTC'));
    }
    catch(\Exception $e) {

    }

    return $new_str->format( $format);
}

//this function converts string from UTC time zone to current user timezone

function convertTimeToUSERzone($str, $userTimezone, $format = 'Y-m-d H:i:s') {

    if(empty($str)){
        return '';
    }
    try{
        $new_str = new DateTime($str, new DateTimeZone('UTC') );
        
        $new_str->setTimeZone(new DateTimeZone( $userTimezone ));
    }
    catch(\Exception $e) {
        // Do Nothing
    }


   return isset($new_str)?$new_str->format( $format):'';
    
    
}


function getUserTime($time, $timezone = "Asia/Kolkata", $format = "H:i:s") {

    if ($timezone) {

        $new_str = new DateTime($time, new DateTimeZone('UTC') );

        $new_str->setTimeZone(new DateTimeZone( $timezone ));

        return $new_str->format($format);

    }

}



function getMinutesBetweenTime($startTime, $endTime) {

    $to_time = strtotime($endTime);

    $from_time = strtotime($startTime);

    $diff = abs($to_time - $from_time);

    if ($diff <= 0) {

        $diff = 0;

    } else {

        $diff = round($diff/60);

    }

    return $diff;

}
/**
 * 
 *
 */

function user_timezone($data , $timezone="") {

    if($timezone) {

        // return $data->timezone($timezone)->format('Y-m-d H:i:s');
        $data = convertTimeToUSERzone($data , $timezone);
    }

    return date('d-m-Y H:i' , strtotime($data));
}

function common_date($date , $timezone = "", $format = "d M Y h:i A") {

    if($timezone) {

        $date = convertTimeToUSERzone($date , $timezone);

    }

    return date($format , strtotime($date));
}

function followers($id, $skip = null, $take = null) {
        
    $query = Follower::select('followers.id as id' , 'followers.*', 
                    'users.name as name', 'users.email as email', 'users.picture' , 'users.device_token', 'users.device_type')
            ->leftJoin('users' , 'users.id' ,'=' , 'followers.follower')
            ->where('user_id', $id);

    if ($take != null) {
        $query->skip($skip)->take($take);
    }

    $model = $query->get();
    
    return $model;
}

function followings($id, $skip = null, $take = null) {
    
    $query = Follower::select('followers.id as id' , 'followers.*', 
                    'users.name as name', 'users.email as email', 'users.picture', 'users.device_token', 'users.device_type')
                ->leftJoin('users' , 'users.id' ,'=' , 'followers.user_id')
                ->where('follower', $id);
    if ($take != null) {
        $query->skip($skip)->take($take);
    }
    $model = $query->get();

    return $model;
}


function followers_for_notification($id, $skip = null, $take = null) {
        
    $query = Follower::select('followers.id as id' , 'followers.*', 
                    'users.name as name', 'users.email as email', 'users.picture' , 'users.device_token', 'users.device_type')
            ->leftJoin('users' , 'users.id' ,'=' , 'followers.follower')
            ->where('user_id', $id)
            ->where('users.device_token' , '!=' , "")
            ->whereIn('users.device_type' , [DEVICE_ANDROID,DEVICE_IOS]);

    if ($take != null) {
        $query->skip($skip)->take($take);
    }

    $model = $query->get();
    
    return $model;
}

function get_register_count() {

    $ios_count = MobileRegister::where('type' , 'ios')->value('count') ?? 0;
    
    $android_count = MobileRegister::where('type' , 'android')->value('count') ?? 0;

    $web_count = MobileRegister::where('type' , 'web')->value('count') ?? 0;

    $total = $ios_count + $android_count + $web_count;

    return array('total' => $total , 'ios' => $ios_count , 'android' => $android_count , 'web' => $web_count);
}

function last_days($days){

  $views = PageCounter::orderBy('created_at','asc')->where('created_at', '>', Carbon::now()->subDays($days))->where('page','home');
  $arr = array();
  $arr['count'] = $views->count();
  $arr['get'] = $views->get();

  return $arr;

}
function counter($page = 'home'){

    $count_home = PageCounter::wherePage($page)->where('created_at', '>=', new DateTime('today'));

        if($count_home->count() > 0){
            $update_count = $count_home->first();
            $update_count->count = $update_count->count + 1;
            $update_count->save();
        }else{
            $create_count = new PageCounter;
            $create_count->page = $page;
            $create_count->count = 1;
            $create_count->save();
        }
}

function total_video_revenue() {
    return 100;
}

// While Updating admin Commission , need to update the user Commission Percentage as well

function update_user_commission($admin_commission) {

    if($admin_commission) {

        $commission = abs(100 -  $admin_commission);

        if($commission) {

            Settings::where('key' , 'user_commission')->update(['value' => $commission]);
        }
    }
}




/**
 * Check the default subscription is enabled by admin
 *
 */

function user_type_check($user) {

    $user = User::find($user);

    if($user) {

        // User need subscripe the plan

        if(Setting::get('is_subscription')) {

            $user->user_type = 1;

        } else {
            
            $user->user_type = 0;
        }

        $user->save();

    }

}



function convertAndriodToOtherUrl($video) {

    $filename = $video->user_id.'_'.$video->id;

    $url = "rtmp://".Setting::get('cross_platform_url')."/live/".$filename;

    return $url;

}


function convertLiveUrl($request) {

    $id = $request->video_id;

    $device_type = $request->device_type;

    $browser = $request->browser;

    \Log::info("Live Video Id ".$id);

    $video = LiveVideo::where('id', $id)->first(); 

    if ($video) {

        if($video->is_streaming) {

            if (!$video->status) {


                if ($video->video_url) {

                    $sdp = $video->user_id.'_'.$video->id;

                    $browser = $browser ? strtolower($browser) : get_browser();

                    if (strpos($browser, 'safari') !== false) {
                        
                        $url = "http://".Setting::get('cross_platform_url')."/live/".$sdp."/playlist.m3u8";  

                    } else {

                        $url = "rtmp://".Setting::get('cross_platform_url')."/live/".$sdp;
                    }

                } else {

                    $sdp = $video->user_id.'-'.$video->id.'.sdp';

                    if ($device_type == DEVICE_ANDROID) {

                        $url = "rtsp://".Setting::get('cross_platform_url')."/live/".$sdp;

                    } else if($device_type == DEVICE_IOS) {

                        $url = is_ssl().Setting::get('cross_platform_url')."/live/".$sdp."/playlist.m3u8";

                    } else {

                        $browser = $browser ? strtolower($browser) : get_browser();

                        if (strpos($browser, 'safari') !== false) {
                            
                            $url = "http://".Setting::get('cross_platform_url')."/live/".$sdp."/playlist.m3u8";  

                        } else {

                            $url = "rtmp://".Setting::get('cross_platform_url')."/live/".$sdp;
                        }

                    }
                }

                $response_array = ['success'=> true, 'url'=>$url];

            } else {

                $response_array = ['success'=> false, 'message'=>tr('stream_stopped')];

            }

        } else {

            $response_array = ['success'=> false, 'message'=>tr('no_streaming_video_present')];

        }

    } else {

        $response_array = ['success'=> false, 'message'=>tr('no_live_video_present')];

    }

    return $response_array;
}


function each_video_payment($id) {

    $video = LiveVideoPayment::where('live_video_id', $id)
            ->where('amount', '>', 0)
            ->sum('amount');

    $user_amount = LiveVideoPayment::where('live_video_id', $id)
            ->where('amount', '>', 0)
            ->sum('user_amount');

    $admin_amount = LiveVideoPayment::where('live_video_id', $id)
            ->where('amount', '>', 0)
            ->sum('admin_amount');

    $total = $video ? $video : 0;

    $user = $user_amount ? $user_amount : 0;

    $admin = $admin_amount ? $admin_amount : 0;

    return ['total_amount'=>$total, 'user_amount'=>$user, 'admin_amount'=>$admin];

}


/**
 * Function : add_to_redeem()
 * 
 * @param $id = role ID
 *
 * @param $amount = earnings
 *
 * @description : If the role earned any amount, use this function to update the redeems
 *
 */

function add_to_redeem($id , $amount) {

    \Log::info('Add to Redeem Start');

    if($id && $amount) {

        $data = Redeem::where('user_id' , $id)->first();

        if(!$data) {
            $data = new Redeem;
            $data->user_id = $id;
        }

        $data->total = $data->total + $amount;
        $data->remaining = $data->remaining+$amount;
        $data->save();
   
    }

    \Log::info('Add to Redeem End');
}

// Based on the request type, it will return string value for that request type

function redeem_request_status($status) {
    
    if($status == REDEEM_REQUEST_SENT) {
        $string = tr('REDEEM_REQUEST_SENT');
    } elseif($status == REDEEM_REQUEST_PROCESSING) {
        $string = tr('REDEEM_REQUEST_PROCESSING');
    } elseif($status == REDEEM_REQUEST_PAID) {
        $string = tr('REDEEM_REQUEST_PAID');
    } elseif($status == REDEEM_REQUEST_CANCEL) {
        $string = tr('REDEEM_REQUEST_CANCEL');
    } else {
        $string = tr('REDEEM_REQUEST_SENT');
    }

    return $string;
}

function check_keys() {

    $admin  = Admin::first();

    $key = $admin ? $admin->security_key : '';

    return $key;
}

/**
 * function routefreestring()
 * 
 * @description used for remove the route parameters from the string
 *
 * @created Maheswari
 *
 * @edited Maheswari
 *
 * @param string $string
 *
 * @return Route parameters free string
 */

function routefreestring($string) {

    $search = array(' ', '&', '%', "?",'=','{','}','$');

    $replace = array('-', '-', '-' , '-', '-', '-' , '-','-');

    $string = str_replace($search, $replace, $string);

    return $string;

}
    
function get_video_end($video_url) {
    $url = explode('/',$video_url);
    $result = end($url);
    return $result;
}


/**
 * Function Name : amount_convertion()
 *
 * To change the amount based on percentafe (Percentage/absolute)
 *
 * @created_by - Shobana Chandrasekar
 *
 * @updated_by - - 
 *
 * @param - Percentage and amount
 *
 * @return response of converted amount
 */
function amount_convertion($percentage, $amt) {

    $converted_amt = $amt * ($percentage/100);

    return $converted_amt;
}

/**
 * Function Name : showEntries()
 *
 * To load the entries of the row
 *
 * @created_by Shobana Chandrasekar 
 *
 * @updated_by -- 
 *
 * @return reponse of serial number
 */
function showEntries($request, $i) {

    $s_no = $i;

    // Request Details + s.no

    if (isset($request['page'])) {

        $s_no = (($request['page'] * 10) - 10 ) + $i;

    }

    return $s_no;

}


// Updating admin vod commission ,need to change the user vod commission % as well
function update_user_vod_commission($admin_vod_commission){

    if($admin_vod_commission) {

        $vod_commission = abs(100-$admin_vod_commission);

        if($vod_commission){

            Settings::where('key','user_vod_commission')->update(['value'=>$vod_commission]);
        }
    }
}



function get_user_groups($user_id) {

    $groups = LiveGroupMember::leftJoin('live_groups' , 'live_group_members.live_group_id' , '=' , 'live_groups.id')->where('live_group_members.member_id' , $user_id)->orWhere('live_group_members.owner_id' , $user_id)
    ->where('live_groups.status' , LIVE_GROUP_APPROVED)
    ->select('live_group_id')
    ->groupBy('live_groups.id')
    ->get();

    $ids = [];

    foreach ($groups as $key => $value) {
        $ids[] = $value->live_group_id;
    }

    return $ids;

}


function check_valid_url($file) {

    return 1;

}

/**
 *
 * Function Name: check_follow_status()
 * 
 * @created Vidhya
 *
 * @created Vidhya
 *
 * @param integer $loggged_user_id
 *
 * @param integer $check_user_id
 *
 * @return boolean $follow_status
 *
 */

function check_follow_status($loggged_user_id , $check_user_id) {

    $check_follow = Follower::where('follower', $loggged_user_id)
                                ->where('user_id', $check_user_id)
                                ->first();

    $follow_status = DEFAULT_FALSE;

    if($check_follow) {

        $follow_status = DEFAULT_TRUE;

    }
    
    if ($loggged_user_id == $check_user_id) {

        $follow_status = -1; // Same user

    }

    return $follow_status;

}

/**
 *
 * Function Name: check_blocked_status()
 * 
 * @created Vidhya
 *
 * @created Vidhya
 *
 * @param integer $loggged_user_id
 *
 * @param integer $check_user_id
 *
 * @return boolean $follow_status
 *
 */

function check_blocked_status($loggged_user_id , $check_user_id) {

    // Blocked Users by You

    $user_blocked_by_you = BlockList::where('user_id', $loggged_user_id)
            ->where('block_user_id', $check_user_id)->first();

     // Blocked By Others

    $other_users_blocked_you = BlockList::where('user_id', $check_user_id)
            ->where('block_user_id', $loggged_user_id)->first();

    $is_blocked = YES;

    if (!$user_blocked_by_you && !$other_users_blocked_you) {

        $is_blocked = NO;

    }

    // Both are same user check

    if($loggged_user_id == $check_user_id) {

        $is_blocked = NO;
    }

    return $is_blocked;

}

/**
 *
 * Function Name: is_ssl()
 * 
 * @created Maheswari
 *
 * @created Maheswari
 *
 * @param Get the wowza ssl values from settings table. 
 *
 * @return is_ssl is 1  return https or 0 return http
 *
 */
function is_ssl(){
    
    $is_ssl = Setting::get('wowza_is_ssl') ? "https://" : "http://";

    return $is_ssl;
}


/**
 * @method check_push_notification_configuration()
 *
 * @uses check the push notification configuration
 *
 * @created Bhawya
 *
 * @updated Bhawya
 *
 * @param boolean $is_user
 *
 * @return boolean $push_notification_status
 */

function check_push_notification_configuration() {

    if(Setting::get('user_fcm_sender_id') && Setting::get('user_fcm_server_key')) {
        return YES;
    }

    return NO;
}

/**
 * @method check_push_notification_configuration()
 *
 * @uses check the push notification configuration
 *
 * @created Bhawya
 *
 * @updated Bhawya
 *
 * @param boolean $is_user
 *
 * @return boolean $push_notification_status
 */

function check_push_notification_configuration_new() {

    if(envfile('FCM_SERVER_KEY') && envfile('FCM_SENDER_ID')) {
        return YES;
    }

    return NO;
}

/**
 * @method formatted_amount()
 *
 * @uses used to format the number
 *
 * @created Bhawya
 *
 * @updated
 *
 * @param integer $num
 * 
 * @param string $currency
 *
 * @return string $formatted_amount
 */

function formatted_amount($amount = 0.00, $currency = "") {

    $currency = $currency ?: Setting::get('currency', '$');

    $amount = number_format((float)$amount, 2, '.', '');

    $formatted_amount = $currency."".$amount ?: "0.00";

    return $formatted_amount;
}

/**
 * @method formatted_plan()
 *
 * @uses used to format the number
 *
 * @created Bhawya
 *
 * @updated
 *
 * @param integer $num
 * 
 * @param string $currency
 *
 * @return string $formatted_plan
 */

function formatted_plan($plan = 0, $type = "month") {

    $text = $plan <= 1 ? tr('month') : tr('months');

    return $plan." ".$text;
}

/**
 * @method formatted_live_payment_text()
 *
 * @uses used to format the number
 *
 * @created Bhawya
 *
 * @updated
 *
 * @param integer $num
 * 
 * @param string $currency
 *
 * @return string $formatted_live_payment_text
 */

function formatted_live_payment_text($type = FREE_VIDEO) {

    return $type == FREE_VIDEO ? tr('free_video') : tr('paid_video');
}

function total_days($end_date, $start_date = "") {

    $start_date = $start_date ?? date('Y-m-d H:i:s');

    $start_date = strtotime($start_date);

    $end_date = strtotime($end_date);

    $datediff = $start_date - $end_date;

    return round($datediff / (60 * 60 * 24));
}

/**
 * @method updated_register_count()
 * 
 * @uses To update registerd device count on user delete
 *
 * @created Anjana H
 *
 * @updated Anjana H
 *
 * @param 
 *
 * @return view page
 */
function updated_register_count($device_type) {
    
    if($reg = MobileRegister::where('type' , $device_type)->first()) {
    
        $reg->count = $reg->count - 1;
    
        $reg->save();    
    }
}

function vod_type_of_user($type) {

    $data = [BOTH_USERS => tr('both_users'), NORMAL_USER => tr('normal_users'), PAID_USER => tr('paid_users')];

    return $data[$type] ?? tr('both_users');
}

function vod_type_of_subscription($type) {

    $data = [ONE_TIME_PAYMENT => tr('one_time_payment'), RECURRING_PAYMENT => tr('recurring_payment')];

    return $data[$type] ?? tr('one_time_payment');
}

function is_live_streaming_configured() {

    $is_live_streaming_configured = Setting::get('is_live_streaming_configured', 1); // @todo

    return $is_live_streaming_configured;
}

function members_text($member_count, $type = "member") {

    $text = $member_count <= 1 ? tr('member') : tr('members');

    return $member_count." ".$text;
}

function get_antmedia_playurl($redirect_web_url, $live_video, $live_video_user = []) {

    if(Setting::get('is_antmedia_enabled') == NO) {
        return $redirect_web_url;
    }

    $url = Setting::get('antmedia_base_url');

    $antmedia_url = "LiveApp/play.html?id=".$live_video->stream_key;

    if($live_video_user) {

        $antmedia_url = $live_video_user->device_type == DEVICE_IOS ? "WebRTCAppEE/play.html?id=".$live_video->stream_key : "LiveApp/play.html?id=".$live_video->stream_key;

    }

    return $url.$antmedia_url;

}