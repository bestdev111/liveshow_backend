<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Repositories\CommonRepository as CommonRepo;

use App\Repositories\UserRepository as UserRepo;

use App\Repositories\VodRepository as VideoRepo;

use App\Repositories\StreamerGalleryRepository as StreamerGalleryRepo;

use App\Jobs\sendPushNotification;

use App\Http\Requests;

use Validator;

use Log;

use App\User;

use App\Viewer;

use Hash;

use Setting;

use App\Page;

use App\Helpers\Helper;

use App\LiveVideo;

use App\BlockList;

use DB;

use App\Follower;

use App\Subscription;

use App\ChatMessage;

use App\LiveVideoPayment;

use App\UserSubscription;

use Exception;

use DateTime;

use DateTimeZone;

use App\Redeem;

use App\RedeemRequest;

use App\Card;

use App\Admin;

use File;

use App\VodVideo;

use App\PayPerView;

use App\Coupon;

use App\UserCoupon;

use App\Settings;

use App\LiveGroup;

use App\LiveGroupMember;

use App\CustomLiveVideo;

use App\UserNotification;

use App\NotificationTemplate;

use App\Jobs\SendEmailJob;


class UserApiController extends Controller
{
    
    protected $loginUser;

    protected $timezone, $device_type;

    public function __construct(Request $request) {

        Log::info(url()->current());

        Log::info("Request Data".print_r($request->all(), true));

        $this->middleware('UserApiVal' , array('except' => [
                'register' , 
                'login' , 
                'forgot_password', 
                'privacy' , 
                'terms' , 
                'search_video', 
                'getUrl', 
                'close_streaming',
                'peerProfile',
                'user_details',
                'searchUser',
                'popular_videos',
                'checkVideoStreaming',
                'followers_list',
                'followings_list',
                'single_video',
                'daily_view_count',
                'logout',
                'admin',
                'vod_videos_list',
                'vod_videos_view',
                'vod_videos_search',
                'pages_list',
                'pages_view',
                'check_social',
                'site_settings',
                'user_view',
                $request->device_type  == DEVICE_WEB ? 'home' : '',
                $request->device_type  == DEVICE_WEB ? 'popular_videos' : '',
                $request->device_type == DEVICE_WEB ? '' : 'live_video_snapshot', 
                'custom_live_videos_search',
            ]
        ));



        // Only content validator can use the following details

        $this->middleware('ContentCreatorVal' , array('only' => [
                'close_streaming',
                'subscriptions',
                'streaming_status',
                'save_live_video',
                'pay_now',
                'subscribedPlans',
                'subscription_invoice',
                'redeems',
                'redeem_request_list',
                'send_redeem_request',
                'redeem_request_cancel',
                'plan_detail',
                'stripe_payment',
                'autorenewal_cancel',
                'autorenewal_enable',
                'vod_videos_save',
                'vod_videos_delete',
                'vod_videos_status',
                'vod_videos_set_ppv',
                'vod_videos_remove_ppv',
                'vod_videos_publish',
                'ppv_revenue',
                'streamer_galleries_save',
                'live_groups_index',
                //'live_groups_view',
                'live_groups_save',
                'live_groups_delete',
                'live_groups_members_add'
            ]
        ));

        $this->loginUser = User::find($request->id);

        $this->timezone = $this->loginUser->timezone ?? "America/New_York";

        $this->device_type = $this->loginUser->device_type ?? DEVICE_WEB;

    }


    /**
     * Function Name : register()
     *
     * To Register a new user 
     *
     * @param object $request - User Details
     *
     * @return user details
     */
    public function register(Request $request) {

        try {
            
            DB::beginTransaction();
            
            $response_array = array();

            $basicValidator = Validator::make(
                $request->all(),
                array(
                    'device_type' => 'required|in:'.DEVICE_ANDROID.','.DEVICE_IOS.','.DEVICE_WEB,

                    'device_token' => 'required',

                    'login_by' => 'required|in:manual,facebook,google,apple',

                    'login_type' => 'in:'.CREATOR.','.VIEWER
                )
            );

            if($basicValidator->fails()) {

                $error_messages = implode(',', $basicValidator->messages()->all());

                throw new Exception($error_messages, 101);                

            } else {

                $login_by = $request->login_by;

                $allowedSocialLogin = array('facebook','google','apple');

                if(in_array($login_by,$allowedSocialLogin)){

                    $socialValidator = Validator::make(
                                $request->all(),
                                array(
                                    'social_unique_id' => 'required',
                                    'name' => 'required|max:255',
                                    'email' => 'required|email|max:255',
                                    'mobile' => 'nullable|digits_between:6,13',
                                    'picture' => '',
                                    'gender' => 'in:male,female,others',
                                )
                            );

                    if($socialValidator->fails()) {

                        $error_messages = implode(',', $socialValidator->messages()->all());

                        throw new Exception($error_messages, 101);
                        
                    } 

                } else {

                    $manualValidator = Validator::make(
                        $request->all(),
                        array(
                            'name' => 'required|max:255',
                            'email' => 'required|email|max:255',
                            'password' => 'required|min:6|confirmed',
                            'picture' => 'mimes:jpeg,jpg,bmp,png',
                        )
                    );

                    $emailValidator = Validator::make(
                        $request->all(),
                        array(
                            'email' => 'unique:users,email',
                        )
                    );

                    if($manualValidator->fails()) {

                        $error_messages = implode(',', $manualValidator->messages()->all());

                        throw new Exception($error_messages, 101);
                        

                    } elseif($emailValidator->fails()) {

                        $error_messages = implode(',', $emailValidator->messages()->all());

                        throw new Exception($error_messages, 101);

                    } 

                }

                $user = User::where('email' , $request->email)->first();

                $new_user = DEFAULT_FALSE;

                $request->login_type = $request->login_type ? $request->login_type : VIEWER;

                $is_content_creator = $request->login_type == CREATOR ? CREATOR_STATUS : VIEWER_STATUS;

                // Creating the user
                if(!$user) {

                    $new_user = DEFAULT_TRUE;

                    $user = new User;

                    $user->picture = asset('images/default-profile.jpg');

                    $user->chat_picture = asset('images/default-profile.jpg');

                    $user->cover = asset('images/cover.jpg');

                    $user->push_status = $user->status = 1;

                    // Check the default subscription and save the user type 

                    user_type_check($user->id);

                    register_mobile($request->device_type);

                    $user->is_content_creator = $is_content_creator;

                }

                $user->name = $request->name;

                $user->email = $request->email;

                $user->mobile = $request->has('mobile') ? $request->mobile : '';

                if($request->has('password'))

                    $user->password = Hash::make($request->password);

                $user->gender = $request->has('gender') ? $request->gender : "male";

                $check_device_exist = User::where('device_token', $request->device_token)->first();

                if($check_device_exist) {

                    $check_device_exist->device_token = "";

                    $check_device_exist->save();

                }

                $user->device_token = $request->has('device_token') ? $request->device_token : "";

                $user->device_type = $request->has('device_type') ? $request->device_type : "";

                $user->login_by = $request->has('login_by') ? $request->login_by : "";

                $user->social_unique_id = $request->has('social_unique_id') ? $request->social_unique_id : '';

                if($new_user){

                    $user->picture = asset('placeholder.png');
    
                }
                
                // Upload picture
                
                if($request->login_by == "manual") {

                    if($request->hasFile('picture')) {

                        $user->picture = Helper::upload_avatar('uploads/users',$request->file('picture'), $user);

                    }

                } else {
                    

                    if($new_user && $request->has('picture')) {

                        $user->picture = $request->picture ?:asset('placeholder.png');

                        $user->chat_picture = $request->picture;
    
                    }

                    

                        /*
                         * Check the logged user as viewer or content creator
                         *
                         * If the user registered as content creator and trying to login with Viewer means we need restrict the login.
                         *
                         * For viewer - vice versa
                         */

                       

                        // If he is loggin properly we will redirect iinto his profile

                        if ($user->is_content_creator == $is_content_creator) {


                        } else {

                            $message = tr('you_dont_have_account');

                            // User is content creator but he logging as viewer means will through error

                            if ($user->is_content_creator == CREATOR_STATUS && !$is_content_creator) {

                                $message = tr('registered_as_content_creator');
                            }

                            // User is viewer but he logging as content creator means will through error

                            if ($user->is_content_creator == VIEWER_STATUS && $is_content_creator) {

                                $message = tr('registered_as_viewer');

                            }

                           throw new Exception($message);
                            
                        }

                }
   
                if($request->has('timezone')) {

                    $user->timezone = $request->timezone;

                } else {

                    $user->timezone = 'Asia/Kolkata';

                }

                $user->paypal_email = "";

                $user->token_expiry = Helper::generate_token_expiry();

                $user->unique_id = $user->name;

                $user->login_status = 0;

                
                if ($user->save()) {

                    if($new_user && $user->login_by == 'manual') {
                           
                        $email_data['name'] = $user->name;

                        $email_data['subject'] = tr('user_welcome_title').' '.Setting::get('site_name');
    
                        $email_data['page'] = "emails.user.welcome";
        
                        $email_data['data'] = $user;
        
                        $email_data['email'] = $user->email;
                      
                        $this->dispatch(new SendEmailJob($email_data));

                    }

                    if(in_array($login_by,$allowedSocialLogin)){

                        $user->is_verified = 1;

                        $user->login_status = 0;

                        $user->save();

                    }

                    if($user->is_verified) { 

                        $user->login_status = 1;

                        $user->save();
 
                        // Response with registered user details:

                        $message = $user->name." ".tr('welcome_title',Setting::get('site_name'));


                        $response_array = array(
                            'success' => true,
                            'message'=>$message,
                            'id' => $user->id,
                            'name' => $user->name,
                            'mobile' => $user->mobile,
                            'gender' => $user->gender,
                            'email' => $user->email,
                            'picture' => $user->picture,
                            'cover'=>$user->cover,
                            'chat_picture' => $user->chat_picture ? $user->chat_picture : $user->picture,
                            'token' => $user->token,
                            'token_expiry' => $user->token_expiry,
                            'login_by' => $user->login_by,
                            'user_type' => $user->user_type ? $user->user_type : 0,
                            'social_unique_id' => $user->social_unique_id,
                            'push_status' => $user->push_status ? $user->push_status : 0,
                            'verification_control'=>Setting::get('email_verify_control'),
                            'is_verified'=>$user->is_verified,
                            'one_time_subscription'=>$user->one_time_subscription,
			                'status'=>$user->status,
                            'payment_subscription' => Setting::get('ios_payment_subscription_status'),
                            'is_content_creator'=>(int) $user->is_content_creator
                        );

                        $response_array = Helper::null_safe($response_array);

                    } else {

                        $response_array = array( 'success' => false, 'error_messages' => tr('verify_your_account'), 'error_code'=>9001);

                    }

                } else {

                    throw new Exception(tr('user_details_not_saved'));
                    
                }

            }

            DB::commit();

            return response()->json($response_array, 200);

        } catch (Exception $e) {

            DB::rollback();

            $message = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages'=>$message, 'error_code'=>$code];

            return response()->json($response_array);
        }
    
    }

    /**
     * Function Name : login()
     *
     * To authenticate the user is exists or not
     *
     * @param Object $request - User Details
     *
     * @return user details
     */
    public function login(Request $request) {

        try {
            
            DB::beginTransaction();
            
            $request->request->add([

                'login_type'=>$request->login_type ? $request->login_type : VIEWER
            ]);

            $basicValidator = Validator::make(
                $request->all(),
                array(
                    'device_token' => 'required',
                    'device_type' => 'required|in:'.DEVICE_ANDROID.','.DEVICE_IOS.','.DEVICE_WEB,
                    'login_by' => 'in:manual,facebook,google,apple',
                    'login_type'=>'required|in:'.CREATOR.','.VIEWER
                )
            );

            if($basicValidator->fails()){
                
                $errors = implode(',',$basicValidator->messages()->all());
                
                throw new Exception($errors, 101);
                
            } else {

                $manualValidator = Validator::make(
                    $request->all(),
                    array(
                        'email' => 'required|email|exists:users,email',
                        'password' => 'required',
                    )
                );

                if ($manualValidator->fails()) {

                    $errors = implode(',',$manualValidator->messages()->all());

                    throw new Exception($errors, 101);
                
                } else {

                    // Validate the user credentials

                    $request->login_type = $request->login_type ? $request->login_type : VIEWER;


                    $user = User::where('email', '=', $request->email)->first();

                    $email_active = DEFAULT_TRUE;

                    if($user) {

                        // For demo users - no need to check the login status

                        $demo_users = Setting::get('demo_users') ?: 'user@streamnow.com,test@streamnow.com';

                        $demo_users = explode(',', $demo_users); 

                        if (in_array($user->email, $demo_users)) {

                            $user->login_status = 0;

                            $user->save();

                        }

                        if (!$user->login_status || $user->token_expiry <= time()) {

                            /*
                             * Check the logged user as viewer or content creator
                             *
                             * If the user registered as content creator and trying to login with Viewer means we need restrict the login.
                             *
                             * For viewer - vice versa
                             */

                            $is_content_creator = $request->login_type == CREATOR ? CREATOR_STATUS : VIEWER_STATUS;

                            // If he is loggin properly we will redirect iinto his profile

                            if ($user->is_content_creator == $is_content_creator) {


                            } else {

                                $message = tr('you_dont_have_account');

                                // User is content creator but he logging as viewer means will through error

                                if ($user->is_content_creator == CREATOR_STATUS && !$is_content_creator) {

                                    $message = tr('registered_as_content_creator');
                                }

                                // User is viewer but he logging as content creator means will through error

                                if ($user->is_content_creator == VIEWER_STATUS && $is_content_creator) {

                                    $message = tr('registered_as_viewer');

                                }

                                throw new Exception($message);
                                
                            }

                            if (Setting::get('email_verify_control') ) {

                                if (!$user->is_verified) {

                                    Helper::check_email_verification("" , $user, $error);

                                    $email_active = DEFAULT_FALSE;

                                }

                            }
                         
                            if (!$user->status) {

                               throw new Exception(tr('admin_not_approved'));

                            }

                            if($user->is_verified) {

                                if(Hash::check($request->password, $user->password)){

                                } else {

                                    throw new Exception(Helper::error_message(105), 105);
                                    
                                }

                            } else {
                                
                                throw new Exception(Helper::error_message(109), 109);
                            }

                        } else {

                            throw new Exception(tr('already_logged_in'));

                        }

                    } else {

                        throw new Exception(tr('user_not_found'));
                        
                    }
                
                }

                if ($email_active) {


                    $user->token_expiry = Helper::generate_token_expiry();

                    // Save device details
                    $user->device_token = $request->device_token;

                    $user->device_type = $request->device_type;

                    $user->login_by = $request->login_by;

                    $user->timezone = $request->timezone ? $request->timezone : 'Asia/Kolkata';

                    $user->login_status = DEFAULT_TRUE;

                    if ($user->save()) {

                        
                        $response_array = array(
                            'success' => true,
                            'message' => Helper::get_message(103),
                            'id' => $user->id,
                            'name' => $user->name,
                            'mobile' => $user->mobile,
                            'email' => $user->email,
                            'gender' => $user->gender,
                            'picture' => $user->picture,
                            'chat_picture' => $user->chat_picture ? $user->chat_picture : $user->picture,
                            'cover'=>$user->cover,
                            'token' => $user->token,
                            'token_expiry' => $user->token_expiry,
                            'login_by' => $user->login_by,
                            'user_type' => $user->user_type ? $user->user_type : 0,
                            'social_unique_id' => $user->social_unique_id,
                            'push_status' => $user->push_status ? $user->push_status : 0,
			                'status'=>$user->status,
                            'one_time_subscription'=>$user->one_time_subscription,
                            'payment_subscription' => Setting::get('ios_payment_subscription_status'),
                            'is_content_creator'=>(int) $user->is_content_creator,
                        );

                        $response_array = Helper::null_safe($response_array);

                    } else {

                        throw new Exception(tr('user_details_not_saved'));

                    }
                } else {

                    $response_array = array( 'success' => false, 'error_messages' => Helper::error_message(111), 'error_code' => 111 );
                }
            }

            DB::commit();

            return response()->json($response_array, 200);

        } catch(Exception $e) {

            $message = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages'=>$message, 'error_code'=>$code];

            return response()->json($response_array);

        }
    
    }


    /**
     * Function Name : forgot_password(Request $request)
     *
     * If user forgot their password , they can make use of it.
     *
     * @param Email Id $request - Given User mail id
     *
     * @return Success/failure message
     */
    public function forgot_password(Request $request) {

        try {
          
            DB::beginTransaction();

            $validator = Validator::make( $request->all(),[
                    'email' => 'required|email|exists:users,email',
                ],
                [
                    'exists' => tr('email_id_not_found')
                ]
            );
            
            if($validator->fails()) {

                $errors = implode(',', $validator->messages()->all());

                throw new Exception($errors, 101);

            } else {
                
                $action = User::where('email' , $request->email)->first();

                if($action) {

                    // Check the status for user approved and decline.
                    // Created Maheswari

                    if($action->is_verified) {
                        
                        if($action->status){

                            if($action->login_by == 'manual') {

                                $new_password = Helper::generate_password();

                                $action->password = \Hash::make($new_password);
                             
                                $email_data['subject'] = tr('forgot_email_title' , Setting::get('site_name'));

                                $email_data['email']  = $action ->email;
                    
                                $email_data['password'] = $new_password;

                                $email_data['user']  = $action;
                    
                                $email_data['page'] = "emails.user.forgot-password";
                    
                                $this->dispatch(new \App\Jobs\SendEmailJob($email_data));

                                if(!$action->save()) {

                                    throw new Exception(api_error(103));
                    
                                }
                                $response_array = ['success' => true , 'message' => tr('forgot_password_success')];



                            } else {

                                throw new Exception(tr('only_manual_user'));

                            }
                        } else{

                            throw new Exception(tr('user_account_declined'));

                        }

                    } else{

                        throw new Exception(tr('email_not_verify_approve'));

                    }

                } else {

                    throw new Exception(tr('mail_not_found'), 101);
                    
                }

            }

            DB::commit();

            return response()->json($response_array);

        } catch(Exception $e) {

            $message = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages'=>$message, 'error_code'=>$code];

            return response()->json($response_array);

        }
    
    }


    public function daily_view_count(Request $request) {

        counter();

        $response_array = ['success' => true];

        return response()->json($response_array, 200);

    }

    /**
     * Function Name : change_password()
     *
     * To change the password of the particular signed in user
     *
     * @param Object $request - User Details
     *
     * @return success/failure Message
     */
    public function change_password(Request $request) {

        try {   

            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                    'password' => 'required|confirmed',
                    'old_password' => 'required',
            ]);

            if($validator->fails()) {
                
                $error_messages = implode(',',$validator->messages()->all());

                throw new Exception($error_messages, 101);
           
            } else {

                $user = User::find($request->id);

                if(Hash::check($request->old_password,$user->password)) {

                    $user->password = \Hash::make($request->password);
                    
                    if($user->save()) {

                        $response_array = Helper::null_safe([
                            'success' => true , 
                            'message' => Helper::get_message(102), 
                            'data'=>['id'=>$user->id, 'token'=>$user->token]]);

                        // Once password changed, logout will execute

                        $this->logout($request);

                    } else {

                        throw new Exception(tr('user_details_not_saved'));

                    }

                } else {

                    throw new Exception(Helper::error_message(131), 131);
                }

            }

            DB::commit();

            $response = response()->json($response_array,200);

            return $response;

        } catch (Exception $e) {

            DB::rollback();

            $message = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages'=>$message, 'error_code'=>$code];

            return response()->json($response_array);

        }

    }

    /**
     * Function Name : user_details()
     *
     * To display user details based on user id
     *
     * @param Object $request - User Details
     *
     * @return success/failure Message
     */
    public function user_details(Request $request) {

        $user = User::find($request->id);

        if ($user) {

            // Intialized the variables

            $subscription = "";

            $no_of_viewers_count =  $no_of_paid_videos = $total_no_of_videos = 0;

            // If the user is content creator, then load the subscription and live video payments details

            if ($user->is_content_creator) {

                $subscription = UserSubscription::where('user_id', $request->id)
                            ->select('subscriptions.plan as plan' , 'user_subscriptions.*', 
                                'subscriptions.popular_status as popular_status')
                            ->leftJoin('subscriptions' , 'user_subscriptions.subscription_id' ,'=' , 'subscriptions.id')
                            ->orderBy('created_at', 'desc')->first();
                           
                $no_of_viewers_count = LiveVideoPayment::where('user_id', $request->id)->count();

                $no_of_paid_videos = LiveVideoPayment::where('user_id', $request->id)
                                        ->groupBy('live_video_id')->count();

                $total_no_of_videos = LiveVideo::where('user_id', $request->id)->count();

            }

            $follower_list = [];

            $followers = Follower::select('user_id as id',
                            'users.name as name', 
                            'users.email as email', 
                            'users.picture',
                            'users.description' ,
                            'followers.follower as follower_id' ,
                            'followers.created_at as created_at'
                           )
                    ->leftJoin('users' , 'users.id' ,'=' , 'followers.follower')
                    ->where('user_id', $request->id)
                    ->skip(0)
                    ->take(Setting::get('admin_take_count', 12))
                    ->orderBy('created_at', 'desc')
                    ->get();

            $no_of_followers_cnt = Follower::where('user_id', $request->id)                    
                                ->orderBy('created_at', 'desc')
                                ->count();

            foreach ($followers as $key => $value) {

                $model = Follower::where('follower', $value->id)

                        ->where('user_id', $value->follower_id)->first();

                $follower_is_follow = DEFAULT_FALSE;

                if($model) {

                    $follower_is_follow = DEFAULT_TRUE;

                }
                
                if ($request->id == $value->follower_id) {

                    $follower_is_follow = -1;

                }

                $no_of_followers = Follower::where('user_id', $value->follower_id)->count();


                $follower_list[] = [
                        'id'=>$value->id, 
                        'name'=>$value->name,
                        'email'=>$value->email,
                        'picture'=>$value->picture,
                        'description'=>$value->description,
                        'follower_id'=>$value->follower_id,
                        'status'=>$follower_is_follow,
                        'no_of_followers'=>$no_of_followers ? $no_of_followers : 0,
                        ];
            
            }

            $followings_list = [];

            $no_of_followings = Follower::where('follower', $request->id)->count();

            $followings = Follower::select('followers.follower as id',
                            'users.name as name', 
                            'users.email as email', 
                            'users.picture',
                            'users.description',
                            'users.id as follower_id' ,
                            'followers.created_at as created_at' 
                           )
                    ->leftJoin('users' , 'users.id' ,'=' , 'followers.user_id')
                    ->where('follower', $request->id)
                    ->skip(0)
                    ->take(Setting::get('admin_take_count', 12))
                    ->orderBy('created_at', 'desc')
                    ->get();


            foreach ($followings as $key => $value) {

                $model = Follower::where('follower', $request->id)
                    ->where('user_id', $value->follower_id)->first();

                $followings_is_follow = DEFAULT_FALSE;

                if($model) {

                    $followings_is_follow = DEFAULT_TRUE;

                }

                if ($request->id == $value->follower_id) {

                    $followings_is_follow = -1;

                }
                
                $no_of_followers = Follower::where('user_id', $value->id)->count();

                $followings_list[] = [
                    'id'=>$value->id, 
                    'name'=>$value->name,
                    'email'=>$value->email,
                    'picture'=>$value->picture,
                    'description'=>$value->description,
                    'follower_id'=>$value->follower_id,
                    'status'=>$followings_is_follow,
                    'no_of_followers'=>$no_of_followers ? $no_of_followers : 0,
                ];
            
            }

            $total_paid_videos_count = LiveVideoPayment::where('live_video_viewer_id', $user->id)
                ->where('status', DEFAULT_TRUE)
                ->count();

            $redeem = "";

            // Only for content creator, need to display redeem option

            if ($user->is_content_creator) {

                $redeem = Redeem::where('user_id' , $user->id)
                                ->select('id','total' , 'paid' , 'remaining' , 'status')
                                ->first();

            }

           // dd($redeem);

            $response_array = array(
                'success' => true,
                'id' => $user->id,
                'name' => $user->name,
                'mobile' => $user->mobile,
                'gender' => $user->gender,
                'email' => $user->email,
                'picture' => $user->picture,
                'chat_picture' => $user->chat_picture ? $user->chat_picture : $user->picture,
                'description'=>$user->description,
                'token' => $user->token,
                'token_expiry' => $user->token_expiry,
                'login_by' => $user->login_by,
                'social_unique_id' => $user->social_unique_id,
                'no_of_followers'=> $no_of_followers_cnt,
                'no_of_followings'=> $no_of_followings,
                'followers'=>$follower_list,
                'followings'=>$followings_list,
                'cover' => $user->cover,
                'subscription'=>$subscription ? $subscription : '',
                'no_of_viewers_count'=> $no_of_viewers_count ? $no_of_viewers_count : "0",
                'no_of_paid_videos'=> $no_of_paid_videos ? $no_of_paid_videos : "0",
                'total'=>(string) $user->total,
                'total_admin_amount'=>(string) $user->total_admin_amount,
                'total_user_amount'=>(string) $redeem ? $redeem->total : "0",
                'paid_amount'=>(string) $redeem ? $redeem->paid : "0",
                'remaining_amount'=>(string) $redeem ? $redeem->remaining : "0",
                'no_of_days_left'=> (string) $user->no_of_days ? $user->no_of_days : "0",
                'total_videos'=> $total_no_of_videos ? $total_no_of_videos : 0,
                'one_time_subscription'=> $user->one_time_subscription ? $user->one_time_subscription : "0",
                'amount_paid'=>(string) $user->amount_paid,
                'currency'=>Setting::get('currency'),
                'total_paid_videos_count'=>(string) $total_paid_videos_count ? $total_paid_videos_count : "0",
                'paypal_email'=>$user->paypal_email,
                'user_type'=>$user->user_type,
                'status'=>$user->status,
                'is_content_creator'=>(int) $user->is_content_creator,
                'gallery_description'=>$user->gallery_description
            );

            // $response = response()->json(Helper::null_safe($response_array), 200);

            // Commented by vidhya - for iOS integer issue
            $response = response()->json($response_array, 200);

            return $response;

        } else {

            $response_array = ['success'=>false, 'error_messages'=>tr('user_details_not_found')];

            return response()->json($response_array);
        }

        
    }

    /**
     * Function Name : update_profile()
     *
     * To update user details based on user id
     *
     * @param object $request - User Details
     *
     * @return user details
     */
    public function update_profile(Request $request) {

        try {

            DB::beginTransaction();
        
            $validator = Validator::make(
                $request->all(),
                array(
                    'name' => 'required|max:255',
                    'description' => 'max:255',
                    'email' => 'email|unique:users,email,'.$request->id.'|max:255',
                    'mobile' => 'digits_between:6,13',
                    'picture' => 'mimes:jpeg,bmp,png',
                    'cover' => 'mimes:jpeg,bmp,png',
                    'gender' => 'in:male,female,others',
                    'device_token' => '',
                ));

            if ($validator->fails()) {
                // Error messages added in response for debugging
                $error_messages = implode(',',$validator->messages()->all());

                throw new Exception($error_messages, 101);
                
            } else {

                $user = User::find($request->id);

                if($user) {
                    
                    $user->name = $request->name ? $request->name : $user->name;
                    
                    if($request->has('email')) {

                        $user->email = $request->email;

                    }

                    $user->mobile = $request->mobile ? $request->mobile : $user->mobile;

                    $user->gender = $request->gender ? $request->gender : $user->gender;

                    $user->address = $request->address ? $request->address : $user->address;

                    $user->description = $request->description ? $request->description : $user->description;

                    $user->paypal_email = $request->paypal_email ? $request->paypal_email : ($user->paypal_email ? $user->paypal_email : '');

                    // Upload picture chat picture will save inside upload avatar function, using this third parameter
                    
                    if ($request->hasFile('picture') != "") {

                        Helper::delete_avatar('uploads/users', $user->picture); // Delete the old pic

                        Helper::delete_avatar('uploads/user_chat_img', $user->chat_picture); // Delete the old pic

                        $user->picture = Helper::upload_avatar('uploads/users',$request->file('picture'), $user);
                    }

                    // Upload picture

                    if ($request->hasFile('cover')) {

                        Helper::delete_avatar('uploads/users',$user->cover); // Delete the old pic

                        $user->cover = Helper::upload_avatar('uploads/users',$request->file('cover'), 0);
                    }



                    if ($user->save()) {


                    } else {

                        throw new Exception(tr('user_details_not_saved'));
                        
                    }
                } else {

                    throw new Exception('user_not_found');
                    
                }

                $response_array = array(
                    'success' => true,
                    'id' => $user->id,
                    'name' => $user->name,
                    'mobile' => $user->mobile,
                    'gender' => $user->gender,
                    'email' => $user->email,
                    'picture' => $user->picture,
                    'chat_picture' => $user->chat_picture,
                    'cover' => $user->cover,
                    'token' => $user->token,
                    'token_expiry' => $user->token_expiry,
                    'login_by' => $user->login_by,
                    'social_unique_id' => $user->social_unique_id,
                    'description'=>$user->description,
                    'user_type'=>$user->user_type,
                    'one_time_subscription'=>$user->one_time_subscription,
                    'paypal_email'=>$user->paypal_email,
                    'message'=>tr('profile_update_success')
                );

                $response_array = Helper::null_safe($response_array);
            
            }

            DB::commit();

            $response = response()->json($response_array, 200);

            return $response;

        } catch(Exception $e) {

            DB::rollback();

            $message = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages'=>$message, 'error_code'=>$code];

            return response()->json($response_array);
        }
    
    }

    /**
     * Function Name : delete_account()
     *
     * To delete user account if he dont want the profile
     *
     * @param string $request - Request Password
     *
     * @return user details
     */
    public function delete_account(Request $request) {

        try {

            DB::beginTransaction();

            $validator = Validator::make(
                $request->all(),
                array(
                    'password' => '',
                ));

            if ($validator->fails()) {

                $error_messages = implode(',',$validator->messages()->all());

                throw new Exception($error_messages, 101);
                
            } else {

                $user = User::find($request->id);

                if($user) {

                    if($user->login_by != 'manual') {

                        $user->delete();

                    } else {

                        if(Hash::check($request->password, $user->password)) {

                            $user->delete();

                        } else {

                            throw new Exception(Helper::error_message(108), 108);
                            
                        }

                    }

                    $response_array = array('success' => true , 'message' => tr('user_account_delete_success'));
                    
                } else {

                    throw new Exception(tr('user_not_found'));
                    
                }

            }

            DB::commit();

            return response()->json($response_array,200);

        } catch(Exception $e) {

            DB::rollback();

            $message = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages'=>$message, 'error_code'=>$code];

            return response()->json($response_array);

        }

    }

    /**
     * Function Name : settings()
     *
     * To enable the notification in mobile
     *
     * @param integer $request - Push Status (0 or 1)
     *
     * @return json response
     */
    public function settings(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'status' => 'required',
            )
        );

        if ($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            $response_array = array('success' => false, 'error_messages' => Helper::error_message(101), 'error_code' => 101, 'error_messages'=>$error_messages);

        } else {

            $user = User::find($request->id);

            $user->push_status = $request->status;

            $user->save();

            if($request->status) {

                $message = tr('push_notification_enable');

            } else {

                $message = tr('push_notification_disable');
            }

            $response_array = array('success' => true, 'message' => $message , 
                    'push_status' => $user->push_status, 'data'=>['id'=>$user->id, 'token'=>$user->token]);
        }

        $response = response()->json($response_array, 200);

        return $response;
    
    }

    /**
     * Function Name : privacy()
     *
     * To display the privacy page in mobile app
     *
     * @param empty $request - As of now empty
     *
     * @return response of privacy page
     */ 
    public function privacy(Request $request) {

        $page_data['type'] = $page_data['heading'] = $page_data['content'] = "";

        $page = Page::where('type', 'privacy')->first();

        if($page) {

            $page_data['type'] = "privacy";
            $page_data['heading'] = $page->heading;
            $page_data ['content'] = $page->description;
        }

        $response_array = array('success' => true , 'page' => $page_data);

        return response()->json($response_array,200);

    }


    /**
     * Function Name : terms()
     *
     * To display the terms page in mobile app
     *
     * @param empty $request - As of now empty
     *
     * @return response of terms page
     */ 
    public function terms(Request $request) {

        $page_data['type'] = $page_data['heading'] = $page_data['content'] = "";

        $page = Page::where('type', 'terms')->first();

        if($page) {

            $page_data['type'] = "Terms";

            $page_data['heading'] = $page->heading;

            $page_data ['content'] = $page->description;
        }

        $response_array = array('success' => true , 'page' => $page_data);

        return response()->json($response_array,200);

    }


    /**
     * Function Name : home()
     *
     * To display live videos (Public & private) in home page
     *
     * @param object $request  - User Details
     *
     * @return response of videos list, followers, suggestions
     */
    public function home(Request $request) {

        counter();

        $is_content_creator = VIEWER_STATUS;

        $blockUserIds = [$request->id];

        $query = LiveVideo::where('is_streaming', DEFAULT_TRUE)
                ->where('live_videos.status', DEFAULT_FALSE)
                ->videoResponse()
                ->leftJoin('users' , 'users.id' ,'=' , 'live_videos.user_id');

        $data = [];

        if ($request->id) {

            $user = User::find($request->id);

            // Get logged in users groups 

            $group_ids = get_user_groups($request->id);

            array_push($group_ids, 0);

            $query->whereIn('live_group_id' , $group_ids);

            if ($user) {

                $is_content_creator = $user->is_content_creator;

                if ($user->user_type == DEFAULT_TRUE) {

                    $now = time(); // or your date as well

                    $end_date = strtotime($user->expiry_date);

                    $datediff =  $end_date - $now;

                    $user->no_of_days = ($user->expiry_date) ? floor($datediff / (60 * 60 * 24)) + 1 : 0;

                    if ($user->no_of_days <= 0) {

                        $user->user_type = DEFAULT_FALSE;
                    }

                    $user->save();
                }

            }


            // Blocked By You
            $blockedUsers = BlockList::whereRaw("user_id = {$request->id} or block_user_id = {$request->id}")
                    ->get();

            foreach ($blockedUsers as $key => $block) {
                $blockUserIds[] = $block->block_user_id;
            }

            foreach ($blockedUsers as $key => $other) {
                $blockUserIds[] = $other->user_id;
            }

             // Load Followers

            $followerUserIds = Follower::where('follower', $request->id)->get()->pluck('user_id')->toArray();

            $query->where('type', TYPE_PRIVATE)
                    ->whereIn('live_videos.user_id' , $followerUserIds)
                    ->whereNotIn('live_videos.user_id', $blockUserIds);
            $data = $query->orderBy('live_videos.created_at', 'desc')
                ->skip($request->skip)
                ->take(Setting::get('admin_take_count' ,12))->get();

        }

    

        $values = [];

        foreach ($data as $key => $value) {

            $model = Follower::where('follower', $request->id)->where('user_id', $value->id)->first();

            $is_follow = DEFAULT_FALSE;

            if($model) {

                $is_follow = DEFAULT_TRUE;

            }

            $videopayment = LiveVideoPayment::where('live_video_id', $value->video_id)
                ->where('live_video_viewer_id', $request->id)
                ->where('status',DEFAULT_TRUE)->first();

            $values[] = [
                "id"=> $value->id,
                "name"=> $value->name,
                "email"=> $value->email,
                "user_picture"=> $value->chat_picture,
                "video_id"=> $value->video_id,
                "title"=> $value->title,
                "type"=> $value->type,
                'payment_status' => $value->payment_status ? $value->payment_status : 0,
                "description"=> $value->description,
                "amount"=> $value->amount,
                "snapshot"=> $value->snapshot,
                "viewers"=> $value->viewers ? $value->viewers : 0,
                "no_of_minutes"=> $value->no_of_minutes,
                "date"=> $value->date,
                'is_follow'=>$is_follow,
                'currency'=> Setting::get('currency'),
                "share_link"=>Setting::get('ANGULAR_URL').'live-video/'.$value->video_id,
                'is_paid'=>"",
                'video_stopped_status'=>$value->video_stopped_status,
                'video_payment_status'=> $videopayment ? DEFAULT_TRUE : DEFAULT_FALSE,
                "live_group_id"=> $value->live_group_id ? $value->live_group_id : 0,
                'live_group_name'=>$value->live_group_name,
            ];
        }

        $public_query = LiveVideo::where('is_streaming', DEFAULT_TRUE)
                ->where('live_videos.status', DEFAULT_FALSE)
                ->videoResponse()
                ->leftJoin('users' , 'users.id' ,'=' , 'live_videos.user_id')
                ->where('type', TYPE_PUBLIC)
                ->orderBy('live_videos.created_at', 'desc')
                ->whereNotIn('live_videos.user_id', $blockUserIds)
                ->skip($request->skip)
                ->take(Setting::get('admin_take_count' ,12));

        if($request->id) {

            $public_query->whereIn('live_group_id' , $group_ids);

        }

        $public = $public_query->get();

        foreach ($public as $key => $value) {

            $model = Follower::where('follower', $request->id)->where('user_id', $value->id)->first();

            $is_follow = DEFAULT_FALSE;

            if($model) {

                $is_follow = DEFAULT_TRUE;

            }

            $videopayment = LiveVideoPayment::where('live_video_id', $value->video_id)
                ->where('live_video_viewer_id', $request->id)
                ->where('status',DEFAULT_TRUE)->first();

            $values[] = [
                "id"=> $value->id,
                "name"=> $value->name,
                "email"=> $value->email,
                "user_picture"=> $value->chat_picture,
                "video_id"=> $value->video_id,
                "title"=> $value->title,
                "type"=> $value->type,
                'payment_status' => $value->payment_status ? $value->payment_status : 0,
                "description"=> $value->description,
                "amount"=> $value->amount,
                "snapshot"=> $value->snapshot,
                "viewers"=> $value->viewers ? $value->viewers : 0,
                "no_of_minutes"=> $value->no_of_minutes,
                "date"=> $value->date,
                'is_follow'=>$is_follow,
                'currency'=> Setting::get('currency'),
                "share_link"=>Setting::get('ANGULAR_URL').'live-video/'.$value->video_id,
                'is_paid'=>"",
                'video_stopped_status'=>$value->video_stopped_status,
                'video_payment_status'=> $videopayment ? DEFAULT_TRUE : DEFAULT_FALSE,
                "live_group_id"=> $value->live_group_id ? $value->live_group_id : 0,
                'live_group_name'=>$value->live_group_name,
            ];
        } 

        if ($request->device_type != DEVICE_WEB) {

            if(count($values) !=  0) {

                $suggestion_list = [];

                if ($request->id) {

                    $suggestion_query = User::whereNotIn('id', $blockUserIds)
                                        ->whereNotIn('id', $followerUserIds)
                                        ->where('status', DEFAULT_TRUE)
                                        ->skip($request->skip)
                                        ->take(6)
                                        ->orderBy('created_at', 'desc');

                    if ($is_content_creator == VIEWER_STATUS) {

                        $suggestion_query->where('is_content_creator', CREATOR_STATUS);

                    }

                    $suggestions = $suggestion_query->get();
                
                    foreach ($suggestions as $key => $suggestion) {

                        // $no_of_followers = Follower::where('user_id', $suggestion->id)->count();

                        $suggestion_list[] = ['id'=>$request->id, 'follower_id'=>$suggestion->id, 'name'=> $suggestion->name, 'description'=>$suggestion->description, 'picture'=> $suggestion->chat_picture];

                            // 'no_of_followers'=>$no_of_followers ? $no_of_followers : 0];

                    }
                }

                if(count($values) < Setting::get('admin_take_count' ,12) && ($request->skip == 0)) {


                    $suggestion = ['id'=>'suggestion', 'data'=>$suggestion_list]; // For mobile use

                    array_push($values,$suggestion);

                }

                if(count($values) == Setting::get('admin_take_count' ,12) && ($request->skip >= 0)) {


                    $suggestion = ['id'=>'suggestion', 'data'=>$suggestion_list]; // For mobile use

                    array_push($values,$suggestion);

                }

            }

        }
        
        $response_array = ['success'=>true, 'data'=>$values];

        return response()->json($response_array, 200);
  
    }

    /**
     * Function Name : popular_videos()
     *
     * To load videos based on type
     *
     * @param object $request  - Video Type ( public or private)
     *
     * @return response of videos list
     */
    public function popular_videos(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'type'=>'required|in:'.TYPE_PRIVATE.','.TYPE_PUBLIC,
                'skip'=>'required|numeric',
            ));

        if ($validator->fails()) {
            // Error messages added in response for debugging
            $errors = implode(',',$validator->messages()->all());

            $response_array = ['success' => false,'error_messages' => $errors,'error_code' => 101];

        } else {

                if ($request->id) {

                    $user = User::find($request->id);

                    if ($user) {

                        if ($user->user_type == DEFAULT_TRUE) {

                            $now = time(); // or your date as well

                            $end_date = strtotime($user->expiry_date);

                            $datediff =  $end_date - $now;

                            $user->no_of_days = ($user->expiry_date) ? floor($datediff / (60 * 60 * 24)) + 1 : 0;

                            if ($user->no_of_days <= 0) {

                                $user->user_type = DEFAULT_FALSE;
                            }

                            $user->save();
                        }

                    }

                }

                $query = LiveVideo::where('is_streaming', DEFAULT_TRUE)
                        ->where('live_videos.status', DEFAULT_FALSE)
                        ->videoResponse()
                        ->leftJoin('users' , 'users.id' ,'=' , 'live_videos.user_id')
                        ->where('type', $request->type)
                        ->orderBy('live_videos.created_at', 'desc')
                        ->skip($request->skip)
                        ->take(Setting::get('admin_take_count' ,12));

                if ($request->has('video_id')) {

                    $query->where('live_videos.id', '!=', $request->video_id);
                }

                $blockUserIds = [];

                if ($request->id) {

                    // Get logged in users groups 

                    $group_ids = get_user_groups($request->id);

                    array_push($group_ids, 0);

                    $query->whereIn('live_group_id' , $group_ids);

                    // Blocked Users by You
                    $blockedUsersByYou = BlockList::where('user_id', $request->id)->get()->pluck('block_user_id')->toArray();

                     // Blocked By Others
                    $blockedUsersByOthers = BlockList::where('block_user_id', $request->id)
                            ->get()->pluck('user_id')->toArray();

                    if ($blockedUsersByOthers) {

                        $blockUserIds = array_merge($blockUserIds, $blockedUsersByOthers);

                    }

                    if ($blockedUsersByYou) {

                        $blockUserIds = array_merge($blockUserIds, $blockedUsersByYou);

                    }

                    array_push($blockUserIds, $request->id);

                    $query->whereNotIn('live_videos.user_id', $blockUserIds);

                    if ( $request->type == TYPE_PRIVATE) {

                        // Load Followers
                        $myfollowers = Follower::where('follower', $request->id)->get();

                        $userIds = [];

                        foreach ($myfollowers as $key => $value) {

                            $userIds[] = $value->user_id;
                        }

                        array_push($userIds, $request->id);

                        $query->whereIn('live_videos.user_id' , $userIds);

                    }
                            

                }

                $model = $query->get();


                $values = [];


                foreach ($model as $key => $value) {

                    $model = Follower::where('follower', $request->id)->where('user_id', $value->id)->first();

                    $is_follow = DEFAULT_FALSE;

                    if($model) {

                        $is_follow = DEFAULT_TRUE;

                    }

                    $videopayment = LiveVideoPayment::where('live_video_id', $value->video_id)
                        ->where('live_video_viewer_id', $request->id)
                        ->where('status',DEFAULT_TRUE)->first();

                    $values[] = [
                        "id"=> $value->id,
                        "name"=> $value->name,
                        "email"=> $value->email,
                        "user_picture"=> $value->chat_picture,
                        "video_id"=> $value->video_id,
                        "title"=> $value->title,
                        "type"=> $value->type,
                        'payment_status' => $value->payment_status ? $value->payment_status : 0,
                        "description"=> $value->description,
                        "amount"=> $value->amount,
                        "snapshot"=> $value->snapshot,
                        "viewers"=> $value->viewers,
                        "no_of_minutes"=> $value->no_of_minutes,
                        "date"=> $value->date,
                        'is_follow'=>$is_follow,
                        'currency'=> Setting::get('currency'),
                        "share_link"=>Setting::get('ANGULAR_URL').'live-video/'.$value->video_id,
                        'video_stopped_status'=>$value->video_stopped_status,
                        'video_payment_status'=> $videopayment ? DEFAULT_TRUE : DEFAULT_FALSE,
                        "live_group_id"=> $value->live_group_id ? $value->live_group_id : 0,
                        'live_group_name'=>$value->live_group_name,

                    ];
                }

                $response_array = ['success'=>true, 'data'=>$values];

        }

        return response()->json($response_array, 200);


    }    

    /**
     * Function Name : subscriptions()
     *
     * @uses To display all the subscription plans
     *
     * @created Shobana
     *
     * @updated Vidhya R
     *
     * @param request id
     *
     * @return JSON Response
     */
    public function subscriptions(Request $request) {

        try {

            $base_query = Subscription::BaseResponse()->where('subscriptions.status' , APPROVED);

            if ($request->id) {

                $user_details = User::find($request->id);

                if ($user_details) {

                   if ($user_details->one_time_subscription == DEFAULT_TRUE) {

                       $base_query->where('subscriptions.amount','>', 0);

                   }

                } 

            }

            $subscriptions = $base_query->orderBy('amount' , 'asc')->get();

            if($subscriptions) {

                foreach ($subscriptions as $key => $subscription_details) {
                    
                    $subscription_details->plan = $subscription_details->plan <= 1 ? $subscription_details->plan ." month" : $subscription_details->plan." months";
                }

            }

            $response_array = ['success' => true, 'data' => $subscriptions];

            return response()->json($response_array, 200);

        } catch (Exception $e) {

            $message = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success' => false, 'error_messages' => $message, 'error_code' => $code];

            return response()->json($response_array);

        }
    }

    /**
     * Function Name : suggestions()
     *
     * To display all the suggestion users
     *
     * @param user id $request  - User Details
     *
     * @return response of users list
     */
    public function suggestions(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'skip'=>'required|numeric',
            ));

        if ($validator->fails()) {
            // Error messages added in response for debugging
            $errors = implode(',',$validator->messages()->all());

            $response_array = ['success' => false,'error_messages' => $errors,'error_code' => 101];

        } else {

                $list = [];

                if ($request->id) {

                    $user = User::find($request->id);

                    $is_content_creator = $user->is_content_creator;

                    // Blocked Users
                    $blockUserIds = BlockList::where('user_id', $request->id)->get()->pluck('block_user_id')->toArray();

                    array_push($blockUserIds, $request->id);

                    // Load Followers
                    $myfollowers = Follower::where('follower', $request->id)->get()->pluck('user_id')->toArray();
                    
                    $userIds = array_merge($blockUserIds, $myfollowers);

                
                    $suggestion_query = User::whereNotIn('id', $userIds)
                                        ->where('status', DEFAULT_TRUE)
                                        ->skip($request->skip)
                                        ->take(Setting::get('admin_take_count' ,12))
                                        ->orderBy('created_at', 'desc');

                    if ($is_content_creator == VIEWER_STATUS) {

                        $suggestion_query->where('is_content_creator', CREATOR_STATUS);
                        
                    }

                    $suggestions = $suggestion_query->get();

                    foreach ($suggestions as $key => $suggestion) {

                        $no_of_followers = Follower::where('user_id', $suggestion->id)->count();

                        $list[] = ['id'=>$request->id, 'follower_id'=>$suggestion->id, 'name'=> $suggestion->name, 'description'=>$suggestion->description, 'picture'=> $suggestion->chat_picture, 'no_of_followers'=>$no_of_followers ? $no_of_followers : 0];

                    }
                }

                $response_array = ['success'=>true, 'data'=>$list];

        }
        return response()->json($response_array, 200);

    }

    /**
     * Function Name : add_follower()
     *
     * @uses To add follower based on user logged in
     *
     * @created Shobana
     *
     * @updated Vidhya
     *
     * @param object $request - User Details
     *
     * @return boolean response with message
     */
    
    public function add_follower(Request $request) {

        try {

            Db::beginTransaction();

            $validator = Validator::make(
                $request->all(),
                array(
                    'follower_id'=>'required|integer|exists:users,id',
                ));

            if ($validator->fails()) {
                // Error messages added in response for debugging
                $errors = implode(',',$validator->messages()->all());

                throw new Exception($errors, 101);
                
            } else {

                $model = Follower::where('follower', $request->id)
                            ->where('user_id', $request->follower_id)->first();

                if ($request->follower_id == $request->id) {

                    throw new Exception(tr('same_user_not_as_follower'));
                    
                }

                $follower_model = User::find($request->follower_id);

                $user_model = User::find($request->id);

                $is_content_creator = VIEWER_STATUS;

                if ($user_model) {

                    $is_content_creator = $user_model->is_content_creator;

                }

                if ($is_content_creator == VIEWER_STATUS && $follower_model->is_content_creator == VIEWER_STATUS) {

                    throw new Exception(tr('you_can_not_follow_this_user'));
                    
                }

                if (!$model) {

                    $model = new Follower;

                    $model->user_id = $request->follower_id;

                    $model->follower = $request->id;

                    $model->status = DEFAULT_TRUE;

                    if ($model->save()) {

                        $no_of_followers = Follower::where('user_id', $request->follower_id)->count();

                        $data = [
                            'id' => $request->id,
                            'name' =>  $follower_model->name,
                            'email'  => $follower_model->email,
                            'picture' => $follower_model->picture,
                            'description' => $follower_model->description, 
                            'follower_id' => $follower_model->id,
                            'status' => $model->status,
                            'is_block' => DEFAULT_FALSE,
                            'no_of_followers'=>$no_of_followers ? $no_of_followers : 0
                        ];
                        
                        // Save Notification

                        $this->dispatch(new \App\Jobs\AddFollowerJob($request->id, NO, $request->follower_id));

                        $response_array = ['success'=>true, 'message'=> Helper::get_message(123), 'data' => $data];

                    } else {

                        throw new Exception(tr('add_follower_not_added'));
                        
                    }

                } else {

                    throw new Exception(Helper::error_message(152), 152);
                    
                }
            }

            DB::commit();

            return response()->json($response_array, 200);

        } catch (Exception $e) {

            DB::rollback();

            $message = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages'=>$message, 'error_code'=>$code];

            return response()->json($response_array);

        }

    }

    /**
     * Function Name : remove_follower()
     *
     * To remove follower based on user logged in
     *
     * @param object $request - User Details
     *
     * @return boolean response with message
     */
    public function remove_follower(Request $request) {

        try {

            DB::beginTransaction();            

            $validator = Validator::make(
                $request->all(),
                array(
                    'follower_id'=>'required|integer|exists:users,id',
                ));

            if ($validator->fails()) {
                // Error messages added in response for debugging
                $errors = implode(',',$validator->messages()->all());

                throw new Exception($errors, 101);
                
            } else {

                $model = Follower::where('follower', $request->id)->where('user_id', $request->follower_id)->first();

                if ($model) {

                    if ($model->delete()) {

                        $follower_model = User::find($request->follower_id);

                        $no_of_followers = Follower::where('user_id', $request->follower_id)->count();

                        $data = [
                            'id' => $request->id,
                            'name' =>  $follower_model->name,
                            'email'  => $follower_model->email,
                            'picture' => $follower_model->picture,
                            'description' => $follower_model->description, 
                            'follower_id' => $follower_model->id,
                            'status' => DEFAULT_FALSE,
                            'is_block' => DEFAULT_FALSE,
                            'no_of_followers'=>$no_of_followers ? $no_of_followers : 0
                        ];

                        $response_array = ['success'=>true, 'message'=> Helper::get_message(122), 'data'=>$data];

                    } else {

                        throw new Exception(tr('not_delete_follower'), 151);

                    }

                } else {

                    throw new Exception(Helper::error_message(151), 151);

                }

            }

            DB::commit();

            return response()->json($response_array, 200);

        } catch (Exception $e) {

            DB::rollback();

            $message = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages'=>$message, 'error_code'=>$code];

            return response()->json($response_array);

        }

    }


    /** 
     * Function Name : save_live_video()
     *
     * To save the video info based on logged in user
     * 
     * @param object $request - Video Details
     *
     * @return response of json video details
     */
    public function save_live_video(Request $request) {

        try {

            // If the amount is empty

            if(!$request->amount) {

                $request->request->add([

                    'amount'=>0

                ]);

            }

            DB::beginTransaction();

            Log::info(print_r($request->all(), true));

            $validator = Validator::make($request->all(),array(
                    'title' => 'required',
                    'description' => 'required|max:255',
                    'payment_status'=>'required|numeric',
                    'amount' => $request->payment_status ? 'required|numeric|min:0.01|max:100000' : 'required|numeric',
                    'type' => 'required|in:'.TYPE_PRIVATE.','.TYPE_PUBLIC,
                    'live_group_id' => $request->live_group_id > 0 ? 'integer|exists:live_groups,id' : "",
                ),
                [
                    'live_group_id.exists' => Helper::error_message(909)
                ]
            );
            
            if($validator->fails()) {

                $errors = implode(',', $validator->messages()->all());

                throw new Exception($errors);
                
            } else {

                if (!Setting::get('SOCKET_URL')){

                    throw new Exception(tr('socket_url_not_enabled'));
                    
                }

                $user = User::find($request->id);

                if ($user) {

                    if ($user->user_type) {

                        if($request->live_group_id) {

                            // Check the group details

                            $group_details = LiveGroup::where('id',$request->live_group_id)->first();

                            if(!$group_details) {

                                throw new Exception(Helper::error_message(908), 908);
                                
                            }

                            if($group_details->status != LIVE_GROUP_APPROVED) {

                                throw new Exception(Helper::error_message(909), 909);

                            }

                        }

                        $call_status = $this->check_user_call($request)->getData();

                        if ($call_status->success) {

                            $model = new LiveVideo;

                            $model->title = $request->title;

                            $model->payment_status = $request->payment_status;

                            $model->type = $request->type;

                            $model->live_group_id = $request->live_group_id ? $request->live_group_id : 0;

                            $model->amount = ($request->payment_status) ? (($request->has('amount')) ? $request->amount : 0 ): 0;

                            $model->description = ($request->has('description')) ? $request->description : null;

                            $model->status = DEFAULT_FALSE;

                            $model->user_id = $request->id;

                            $model->virtual_id = md5(time());

                            $model->unique_id = $model->title;

                            $model->broadcast_type = $request->broadcast_type ?? BROADCAST_TYPE_BROADCAST;

                            $model->browser_name = $request->browser ? $request->browser : '';

                            $model->snapshot = asset("/images/default-image.jpg");

                            $model->start_time = getUserTime(date('H:i:s'), ($user) ? $user->timezone : '', "H:i:s");

                            $model->stream_key = routefreestring(strtolower($request->title.rand(1,10000).rand(1,10000) ?: rand(1,10000).rand(1,10000)));

                            Log::info(print_r($request->device_type, true));

                            if ($request->device_type == DEVICE_WEB) {

                                Log::info("Testing ".print_r($request->device_type, true));

                                if (Setting::get('kurento_socket_url')) {

                                    $last = LiveVideo::orderBy('port_no', 'desc')->first();

                                    $destination_port = 44104;

                                    if ($last) {

                                        if ($last->port_no) {

                                            $destination_port = $last->port_no + 2;

                                        }

                                    }

                                    $model->port_no = $destination_port;

                                }

                            } else {

                                $model->is_streaming = DEFAULT_TRUE;
                                
                            }

                            if ($model->save()) {

                                $destination_ip = Setting::get('wowza_ip_address');

                                if ($request->device_type == DEVICE_WEB || $request->device_type == DEVICE_ANDROID) {

                                    if (Setting::get('kurento_socket_url') && $destination_ip) {

                                        $streamer_file = $user->id.'-'.$model->id.'.sdp';  

                                    } else {

                                        $streamer_file = "";
                                    }

                                } else {

                                    $streamer_file = $user->id.'_'.$model->id;  

                                }

                                $appSettings = [];

                                Log::info("device type ".$request->device_type == DEVICE_IOS);

                                if ($request->device_type == DEVICE_WEB) {

                                    $appSettings = json_encode([
                                        'SOCKET_URL' => Setting::get('SOCKET_URL'),
                                        'CHAT_ROOM_ID' => isset($model) ? $model->id : null,
                                        'BASE_URL' => Setting::get('BASE_URL'),
                                        'TURN_CONFIG' => [],
                                        'TOKEN' => $request->token,
                                        'USER_PICTURE'=>$user->chat_picture,
                                        'NAME'=>$user->name,
                                        'CLASS'=>'left',
                                        'USER' => ['id' => $request->id, 'role' => "model"],
                                        'VIDEO_PAYMENT'=>null,
                                    ]);

                                    // $model->video_url = $streamer_file ? 'http://'.Setting::get('cross_platform_url').'/'.Setting::get('wowza_app_name').'/'.$streamer_file.'/playlist.m3u8';

                                } else if($request->device_type == DEVICE_IOS){

                                    // $model->video_url = 'http://'.Setting::get('cross_platform_url').'/'.Setting::get('wowza_app_name').'/'.$streamer_file.'/playlist.m3u8';

                                    $model->browser_name = $request->device_type;

                                }

                                $model->video_url = $streamer_file;

                                $model->save();

                                if ($request->id == $model->user_id) {

                                    $redirect_web_url = Setting::get('ANGULAR_URL').'streamer-video?user_id='.$request->id.'&video_id='.$model->id;
 
                                } else {

                                    $redirect_web_url = Setting::get('ANGULAR_URL').'viewer-video?user_id='.$request->id.'&video_id='.$model->id;
                                }

                                $request->request->add(['broadcast_type' => $model->broadcast_type, 'virtual_id' => $model->virtual_id, 'live_video_id' => $model->id]);

                                $mobile_live_streaming_url = Helper::get_mobile_live_streaming_url($request);

                                $live_video_user = $user;

                                $response_array =[
                                    'success' => true , 
                                    'id'=>$request->id,
                                    'video_id' => $model->id,
                                    'unique_id' => $model->unique_id,
                                    'title'=>$model->title,
                                    'payment_status'=>$model->payment_status ? $model->payment_status : 0, 
                                    'virtual_id'=>$model->virtual_id,
                                    'video_type'=>$model->type,
                                    'amount'=>$model->amount, 
                                    'description'=>$model->description, 
                                    'snapshot'=>$model->snapshot,
                                    'viewer_cnt'=>(int) ($model->viewer_cnt ? $model->viewer_cnt : 0),
                                    'is_streaming'=>$model->is_streaming,
                                    'video_url'=>$model->video_url,
                                    "share_link"=>Setting::get('ANGULAR_URL').'live-video/'.$model->id,
                                    'appSettings'=>$appSettings,
                                    // 'redirect_web_url'=>$redirect_web_url,
                                    'redirect_web_url' => get_antmedia_playurl($redirect_web_url, $model, $live_video_user),
                                    'hostAddress'=>Setting::get('wowza_ip_address'),
                                    'portNumber'=>Setting::get('wowza_port_number'),
                                    'applicationName'=>Setting::get('wowza_app_name'),
                                    'streamName'=>$streamer_file,
                                    'wowzaUsername'=>Setting::get('wowza_username'),
                                    'wowzaPassword'=>Setting::get('wowza_password'),
                                    'wowzaLicenseKey'=>Setting::get('wowza_license_key'),
                                    'live_group_id' => $model->live_group_id,
                                    'live_group_name' => $model->getLiveGroup ? $model->getLiveGroup->name : "",
                                    'broadcast_type' => $model->broadcast_type,
                                    'mobile_live_streaming_url' => $mobile_live_streaming_url
                                ];

                                Log::info("save live video".print_r($response_array, true));

                                $title = Setting::get('site_name');

                                $message = $user->name." ".tr('live_stream_push');

                                $request->request->add([

                                    'video_id'=>$model->id,
                                ]);

                                /*$this->send_notification_to_followers($request);


                                if(check_push_notification_configuration() && Setting::get('push_notification') == YES ) {

                                    $push_data = ['type' => PUSH_SINGLE_VIDEO, 'video_id' => $model->id];

                                    $this->dispatch(new sendPushNotification($request->id,LIVE_PUSH,PUSH_SINGLE_VIDEO,$title, $message , $model->id, $push_data));

                                }*/

                            } else {
                                
                                throw new Exception(Helper::error_message(003), 003);
                                
                            }

                        } else {

                            throw new Exception(api_error(174), 174);
                        }

                    } else {

                         throw new Exception(Helper::error_message(154), 154);
                         
                    }
                } else {

                    throw new Exception(Helper::error_message(150), 150);
                    
                }
            
            }   

            DB::commit();

            Log::info("Save Live video ".print_r($request->all(), true));

            return response()->json($response_array,200);

        } catch (Exception $e) {

            DB::rollback();

            $message = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages'=>$message, 'error_code'=>$code];

            // $response_array = ['success'=>false, 'error_messages'=>$message];

            return response()->json($response_array);
        }

    }

    /**
     * Function Name : searchUser()
     * 
     * To search the other users, videos and live tv videos
     *
     * @param object $request - term that search
     *
     * @return user list
     */
    public function searchUser(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'term' => 'required',
                'live_group_id' => 'exists:live_groups,id,user_id,'.$request->id
            ),
            array(
                'exists' => 'The :attribute doesn\'t exists',
                'live_group_id.exists' => Helper::error_message(909)
            )
        );
    
        if ($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            $response_array = array('success' => false, 'error_messages' => $error_messages, 'error_code' => 101);

        } else {

            $list = [];

            $q = $request->term;

            $items = array();
            
            $results = Helper::search_user($request->id, $q, $request->skip, Setting::get('admin_take_count' ,12));

            if(count($results)) {
                    
                foreach ($results as $key => $suggestion) {

                   
                    // Blocked Users by You
                    $blockedUsersByYou = BlockList::where('user_id', $request->id)
                            ->where('block_user_id', $suggestion->id)->first();

                     // Blocked By Others
                    $blockedUsersByOthers = BlockList::where('user_id', $suggestion->id)
                            ->where('block_user_id', $request->id)->first();

                    if (!$blockedUsersByYou && !$blockedUsersByOthers) {

                        $model = Follower::where('follower', $request->id)->where('user_id', $suggestion->id)->first();

                        $is_follow = DEFAULT_FALSE;

                        if($model) {

                            $is_follow = DEFAULT_TRUE;

                        }

                        $no_of_followers = Follower::where('user_id', $suggestion->id)->count();

                        // USED FOR LIVE GROUP ADD MEMBER SEARCH

                        $is_member = LIVE_GROUP_MEMBER_NO;

                        // $is_owner = LIVE_GROUP_OWNER_NO;

                        if($request->live_group_id) {

                            $group_details = LiveGroup::find($request->live_group_id);

                            $member_details = LiveGroupMember::where('live_group_id' , $request->live_group_id)->where('member_id' , $suggestion->id)->count();

                            $is_member = $member_details ? LIVE_GROUP_MEMBER_YES : LIVE_GROUP_MEMBER_NO; 

                            // $is_owner = ($group_details) ? ($suggestion->id == $request->id ? LIVE_GROUP_OWNER_YES : LIVE_GROUP_OWNER_NO) : LIVE_GROUP_OWNER_NO; 
                        }

                        $list[] = [
                            'id'=>$request->id, 
                            'follower_id'=>$suggestion->id, 
                            'name'=> $suggestion->name, 
                            'description'=>$suggestion->description, 
                            'picture'=> $suggestion->picture, 
                            'is_follow'=>$is_follow,
                            'no_of_followers'=>$no_of_followers ? $no_of_followers : 0,
                            'is_member' => $is_member,
                            // 'is_owner' => $is_owner ? LIVE_GROUP_OWNER_YES : LIVE_GROUP_OWNER_NO
                        ];

                    }

                }
            }   

            $response_array = ['success'=> true, 'data'=>$list];
            
        }   
    
        return response()->json($response_array, 200);

    }

    /**
     * function Name : single_video
     *
     * To get live video single page
     *
     * @param object $request - Video Details
     *
     * @return video details
     */
    public function single_video(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'browser'=>'required',
                'device_type'=>'required|in:'.DEVICE_ANDROID.','.DEVICE_IOS.','.DEVICE_WEB,
                'video_id'=>'required|exists:live_videos,id',
            ));

        if ($validator->fails()) {

            // Error messages added in response for debugging

            $errors = implode(',',$validator->messages()->all());

            $response_array = ['success' => false,'error_messages' => $errors,'error_code' => 101];

        } else {

            $model = LiveVideo::find($request->video_id);

            if ($model) {

                $going_to_stream = $model->is_streaming;

                if ($model->user_id == $request->id) {

                    $going_to_stream = 1;

                }

                if ($going_to_stream) {

                    if(!$model->status) {

                        $user = User::find($model->user_id);

                        if ($user) {

                            // Load Based on id
                            $chat = ChatMessage::where('live_video_id', $model->id)->get();

                            $messages = [];

                            if($chat->count() > 0) {

                                foreach ($chat as $key => $value) {
                                    
                                    $messages[] = [
                                        'id' => $value->id,
                                         
                                        'user_id' => ($value->getUser)? $value->user_id : $value->live_video_viewer_id, 
                                        'namespace' => ($value->getUser) ? $value->getUser->name : (($value->getViewUser) ? $value->getViewUser->name : ""),

                                        'picture'=> ($value->getUser) ? $value->getUser->chat_picture : (($value->getViewUser) ? $value->getViewUser->chat_picture : ""),

                                        'live_video_id'=>$value->live_video_id,

                                        'message'=>$value->message,

                                        'username' => ($value->getUser) ? $value->getUser->name : (($value->getViewUser) ? $value->getViewUser->name : ""),

                                        'userpicture'=> ($value->getUser) ? $value->getUser->chat_picture : (($value->getViewUser) ? $value->getViewUser->chat_picture : ""),];

                                }
                                
                            }

                            $follower = Follower::where('follower', $request->id)->where('user_id', $user->id)->first();

                            $is_follow = DEFAULT_FALSE;

                            if($follower) {

                                $is_follow = DEFAULT_TRUE;

                            }


                            if ($request->id != $user->id) {


                                if ($model->type == TYPE_PRIVATE && $is_follow != DEFAULT_TRUE) {

                                    return response()->json(['success'=>false, 'error_messages'=>tr('not_followed_user')]);

                                }

                                $video_url = "";

                                if ($model->unique_id == 'sample') {

                                    $video_url = $model->video_url;

                                } else {

                                    if ($model->video_url) {

                                        /*if ($request->device_type == 'ios') {

                                            $video_url = CommonRepo::iosUrl($model);

                                        } else {

                                            $video_url = CommonRepo::rtmpUrl($model);
                                        
                                        }*/

                                        //  || $request->browser == IOS_BROWSER || $request->browser == WEB_SAFARI

                                        if ($request->device_type == 'ios') {

                                            $video_url = CommonRepo::iosUrl($model);

                                        } else if($model->browser_name == DEVICE_IOS){

                                           // $video_url = CommonRepo::rtmpUrl($model);

                                           $video_url = CommonRepo::iosUrl($model);

                                        }

                                        if (($request->browser == IOS_BROWSER || $request->browser == WEB_SAFARI) && ($model->browser_name == DEVICE_IOS)) {

                                            $video_url = CommonRepo::iosUrl($model);

                                        }

                                        // $video_url = CommonRepo::rtmpUrl($model);

                                    } else {

                                        $video_url = "";

                                        /*if ($request->device_type == DEVICE_WEB) {

                                            // || $model->browser_name == WEB_OPERA || 
                                            if ($model->browser_name == WEB_FIREFOX) {

                                                if ($request->browser == IOS_BROWSER || $request->browser == WEB_SAFARI) {

                                                    $video_url = CommonRepo::webIosUrl($model);

                                                } else {

                                                    $video_url = "";

                                                }

                                            } else {

                                                $video_url = "";

                                            }

                                        } else {

                                            $video_url = CommonRepo::getUrl($model, $request);

                                        }*/

                                    }

                                }

                            } else {

                                $video_url = "";
                            }

                            $videopayment = DEFAULT_TRUE;

                            if ($request->id != $user->id) {

                                if ($model->amount > 0) {

                                    $videopayment = LiveVideoPayment::where('live_video_id', $model->id)
                                                    ->where('live_video_viewer_id', $request->id)
                                                    ->where('status',DEFAULT_TRUE)->first();

                                }

                            }


                            if ($videopayment) {

                                if ($request->id == $model->user_id) {

                                    $is_streamer = DEFAULT_TRUE;

                                    $redirect_web_url = Setting::get('ANGULAR_URL').'streamer-video?user_id='.$request->id.'&video_id='.$model->id;
 
                                } else {

                                    $is_streamer = DEFAULT_FALSE;

                                    $redirect_web_url = Setting::get('ANGULAR_URL').'viewer-video?user_id='.$request->id.'&video_id='.$model->id;

                                    // Save Notification

                                    $viewerModel = User::find($request->id);

                                    $notification = NotificationTemplate::getRawContent(USER_JOIN_VIDEO, $viewerModel);

                                    $content = $notification ? $notification : USER_JOIN_VIDEO;

                                    UserNotification::save_notification($model->user_id, $content, $model->id, USER_JOIN_VIDEO , $request->id);
                                }

                                $request->request->add(['broadcast_type' => $model->broadcast_type, 'virtual_id' => $model->virtual_id,'live_video_id' => $model->id]);

                                $mobile_live_streaming_url = Helper::get_mobile_live_streaming_url($request);

                                $live_video_user = $user;

                                $data = [
                                     'id'=>$request->id,
                                     'port_no'=>$model->port_no,
                                     'is_streamer'=>$is_streamer,
                                     'user_id'=>$user->id,
                                     'unique_id'=>$model->unique_id,
                                     'name'=>$user->name,
                                     'email'=>$user->email,
                                     'user_picture'=>$user->chat_picture,
                                     'chat_picture' => $user->chat_picture ? $user->chat_picture : $user->picture,
                                     'video_id'=>$model->id,
                                     'title'=>$model->title,
                                     'type'=>$model->type,
                                     'payment_status' => $model->payment_status ? $model->payment_status : 0,
                                     'description'=>$model->description,
                                     'amount'=>$model->amount,
                                     'snapshot'=>$model->snapshot,
                                     'viewers'=>(int) ($model->viewer_cnt ? $model->viewer_cnt : 0),
                                     'viewer_cnt'=>(int) ($model->viewer_cnt ? $model->viewer_cnt : 0),
                                     'no_of_minutes'=>$model->no_of_minutes,
                                     'created_at'=>$model->created_at ? $model->created_at->diffForhumans() : '',
                                     'share_link'=>Setting::get('ANGULAR_URL').'live-video/'.$model->id,
                                     'is_follow'=>$is_follow,
                                     'ios_video_url'=> $video_url,
                                     'video_url'=>$video_url,
                                     'currency'=> Setting::get('currency'),
                                     'comments'=>$messages,  
                                     'virtual_id'=>$model->virtual_id,
                                     'created_date'=>common_date($model->created_at, $user->timezone),
                                     'video_payment_status'=> $videopayment ? DEFAULT_TRUE : DEFAULT_FALSE,
                                     'redirect_web_url' => get_antmedia_playurl($redirect_web_url, $model, $live_video_user),
                                     'is_streaming'=>$model->is_streaming,
                                     'status'=>$model->status,
                                     'live_group_id'=>$model->live_group_id,
                                     'live_group_name'=>$model->getLiveGroup ? $model->getLiveGroup->name : "",
                                     'broadcast_type' => $model->broadcast_type,
                                     'mobile_live_streaming_url' => $mobile_live_streaming_url
                     
                                ];

                               /* $data = Helper::null_safe($data);

                                $data['comments'] = $messages;*/

                                Log::info("single video -----".print_r($data, true));

                                $response_array = ['success'=>true, 'data'=>$data];

                            } else {

                                $response_array = ['success'=>false, 'error_messages'=>Helper::error_message(156), 'error_code'=>156];

                            }

                       }  else {

                            $response_array = ['success'=>false, 'error_messages'=>Helper::error_message(150), 'error_code'=>150];

                       }

                    } else {

                        $response_array = ['success'=>false, 'error_messages'=>Helper::error_message(147), 'error_code'=>147];

                    }

                } else {

                    $response_array = ['success'=>false, 'error_messages'=>Helper::error_message(148), 'error_code'=>148];

                }

            } else {

                $response_array = ['success'=>false, 'error_messages'=>Helper::error_message(149), 'error_code'=>149];

            }
        }

        return response()->json($response_array, 200);

    }

    public function save_chat(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'video_id'=>'required|exists:live_videos,id',
                'viewer_id'=>'required|exists:users,id',
                'message'=>'required',
                'type'=>'required|in:uv,vu',
                'delivered'=>'required',
            ));

        if ($validator->fails()) {
            // Error messages added in response for debugging
            $errors = implode(',',$validator->messages()->all());

            $response_array = ['success' => false,'error_messages' => $errors,'error_code' => 101];

        } else {

            $model = new ChatMessage;

            $model->live_video_id = $request->video_id;

            $model->user_id = $request->id;

            $model->live_video_viewer_id = $request->viewer_id;

            $model->message = $request->message;

            $model->type = $request->type;

            $model->delivered = $request->delivered;

            $model->save();

            Log::info("saving Data");

            Log::info(print_r("Data".$model, true));

            $response_array = ['success'=>true, 'data'=>$model];
        }

        return response()->json($response_array, 200);
    
    }

    /**
     * Function Name : block_user()
     *
     * To block the user , if the user need not to get particular video he can block them
     *
     * @param object $request - User Details
     *
     * @return boolean with message
     */
    public function block_user(Request $request) {

        try {

            DB::beginTransaction();

            $validator = Validator::make(
                $request->all(),
                array(
                    'blocker_id'=>'required|exists:users,id',
                ));

            if ($validator->fails()) {
                // Error messages added in response for debugging
                $errors = implode(',',$validator->messages()->all());

                throw new Exception($errors, 101);

            } else {

                if($request->blocker_id != $request->id) {

                    $model = BlockList::where('user_id', $request->id)
                                ->where('block_user_id', $request->blocker_id)->first();

                    if (!$model) {

                        $model = new BlockList;

                        $model->user_id = $request->id;

                        $model->block_user_id = $request->blocker_id;

                        $model->status = DEFAULT_TRUE;

                        if ($model->save()) {


                            $response_array = ['success'=>true, 'message'=> Helper::get_message(124)];

                        } else {

                            throw new Exception(tr('add_block_user'));
                            
                        }

                    } else {

                        throw new Exception(Helper::error_message(153), 153);

                    }

                } else {

                    throw new Exception(Helper::error_message(155), 155);

                }
            }

            DB::commit();

            return response()->json($response_array, 200);

        }catch(Exception $e) {

            DB::rollback();

            $message = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages'=>$message, 'error_code'=>$code];

            return response()->json($response_array);

        }

    }

    /**
     * Function Name : unblock_user()
     *
     * To un block the user , if the user wants to get particular video he can un block them
     *
     * @param object $request - User Details
     *
     * @return boolean with message
     */
    public function unblock_user(Request $request) {

        try {

            DB::beginTransaction();

            $validator = Validator::make(
                $request->all(),
                array(
                    'blocker_id'=>'required|exists:users,id',
                ));

            if ($validator->fails()) {
                // Error messages added in response for debugging
                $errors = implode(',',$validator->messages()->all());

                throw new Exception($errors, 150);

            } else {

                $model = BlockList::where('block_user_id', $request->blocker_id)
                    ->where('user_id', $request->id)->first();

                if ($model) {

                    if ($model->delete()) {

                        $blocked_user = User::find($request->blocker_id);

                        $no_of_followers = Follower::where('user_id', $request->blocker_id)->count();

                        $data = [
                            'id' => $request->id,
                            'name' =>  $blocked_user->name,
                            'email'  => $blocked_user->email,
                            'picture' => $blocked_user->picture,
                            'description' => $blocked_user->description, 
                            'follower_id' => $blocked_user->id,
                            'status' => DEFAULT_FALSE,
                            'is_block' => DEFAULT_FALSE,
                            'no_of_followers'=>$no_of_followers ? $no_of_followers : 0
                        ];

                        $response_array = ['success'=>true, 'message'=> Helper::get_message(125), 'data'=>$data];

                    } else {

                        throw new Exception(tr('cound_not_unblock'));
                        
                    }

                } else {

                    throw new Exception(Helper::error_message(150), 150);

                }

            }

            DB::commit();

            return response()->json($response_array, 200);

        } catch (Exception $e) {

            DB::rollback();

            $message = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages'=>$message, 'error_code'=>$code];

            return response()->json($response_array);

        }

    }

    public function followers_list(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'skip'=>'required|numeric',
            ));

        if ($validator->fails()) {
            // Error messages added in response for debugging
            $errors = implode(',',$validator->messages()->all());

            $response_array = ['success' => false,'error_messages' => $errors,'error_code' => 101];

        } else {

            $data = [];

            $followers = Follower::select('user_id as id',
                            'users.name as name', 
                            'users.email as email', 
                            'users.picture',
                            'users.description' ,
                            'followers.follower as follower_id' ,
                            'followers.created_at as created_at'
                           )
                    ->leftJoin('users' , 'users.id' ,'=' , 'followers.follower')
                    ->where('user_id', $request->id)
                    ->skip($request->skip)
                    ->take(Setting::get('admin_take_count', 12))
                    ->orderBy('created_at', 'desc')
                    ->get();


           
            foreach ($followers as $key => $value) {

                $model = Follower::where('follower', $request->id)->where('user_id', $value->follower_id)->first();

                $is_follow = DEFAULT_FALSE;

                if($model) {

                    $is_follow = DEFAULT_TRUE;

                }

                $block = BlockList::where('user_id', $request->id)->where('block_user_id', $value->follower_id)->first();

                $block_by_user = BlockList::where('user_id', $value->follower_id)->where('block_user_id', $request->id)->first();

                $is_block = DEFAULT_FALSE;

                if($block || $block_by_user) {

                    $is_block = DEFAULT_TRUE;

                }
                    

                $no_of_followers = Follower::where('user_id', $value->follower_id)->count();

                $data[] = [
                        'id'=>$value->id, 
                        'name'=>$value->name,
                        'email'=>$value->email,
                        'picture'=>$value->picture,
                        'description'=>$value->description,
                        'follower_id'=>$value->follower_id,
                        'status'=>$is_follow,
                        'is_block'=>$is_block,
                        'no_of_followers'=>$no_of_followers ? $no_of_followers : 0,
                        ];
            }

            $response_array = ['success'=>true, 'data'=>$data];

        }

        return response()->json($response_array, 200);

    }

    public function followings_list(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'skip'=>'required|numeric',
            ));

        if ($validator->fails()) {
            // Error messages added in response for debugging
            $errors = implode(',',$validator->messages()->all());

            $response_array = ['success' => false,'error_messages' => $errors,'error_code' => 101];

        } else {

            $data = [];

            $followers = Follower::select('followers.follower as id',
                            'users.name as name', 
                            'users.email as email', 
                            'users.picture',
                            'users.description',
                            'users.id as follower_id' ,
                            'followers.created_at as created_at' 
                           )
                    ->leftJoin('users' , 'users.id' ,'=' , 'followers.user_id')
                    ->where('follower', $request->id)
                    ->skip($request->skip)
                    ->take(Setting::get('admin_take_count', 12))
                    ->orderBy('created_at', 'desc')->get();


            foreach ($followers as $key => $value) {

                $model = Follower::where('follower', $value->id)->where('user_id', $value->follower_id)->first();


                $is_follow = DEFAULT_FALSE;

                if($model) {

                    $is_follow = DEFAULT_TRUE;

                }

                $block = BlockList::where('user_id', $request->id)->where('block_user_id', $value->follower_id)->first();

                $block_by_user = BlockList::where('user_id', $value->follower_id)->where('block_user_id', $request->id)->first();

                $is_block = DEFAULT_FALSE;

                if($block || $block_by_user) {

                    $is_block = DEFAULT_TRUE;

                }
                
                $data[] = [
                        'id'=>$value->id, 
                        'name'=>$value->name,
                        'email'=>$value->email,
                        'picture'=>$value->picture,
                        'description'=>$value->description,
                        'follower_id'=>$value->follower_id,
                        'status'=>$is_follow,
                        'is_block'=>$is_block
                        ];
            }

            $response_array = ['success'=>true, 'data'=>$data];

        }

        return response()->json($response_array, 200);

    }

    /**
     * Function Name : blockersList()
     *
     * To get blockers list based on logged in user list
     *
     * @param Object $request - User id and token
     *
     * @return blockers list
     */
    public function blockersList(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'skip'=>'required|numeric',
            ));

        if ($validator->fails()) {
            // Error messages added in response for debugging
            $errors = implode(',',$validator->messages()->all());

            $response_array = ['success' => false,'error_messages' => $errors,'error_code' => 101];

        } else {

            $data = [];

            // Blocked Users
            $blockedUsers = BlockList::select('block_lists.user_id as id',
                            'users.name as name', 
                            'users.email as email', 
                            'users.picture',
                            'users.description' ,
                            'block_lists.block_user_id as blocked_user_id' ,
                            'block_lists.created_at as created_at')
                    ->where('user_id', $request->id)
                    ->leftJoin('users' , 'users.id' ,'=' , 'block_user_id')
                    ->skip($request->skip)
                    ->take(Setting::get('admin_take_count', 12))
                    ->orderBy('created_at', 'desc')
                    ->get();

            foreach ($blockedUsers as $key => $value) {

                $model = BlockList::where('user_id', $value->id)->where('block_user_id', $value->blocked_user_id)->first();

                $is_block = DEFAULT_FALSE;

                if($model) {

                    $is_block = DEFAULT_TRUE;

                }
                
                $data[] = [
                        'id'=>$value->id, 
                        'blocked_user_id'=>$value->blocked_user_id,
                        'name'=>$value->name,
                        'email'=>$value->email,
                        'picture'=>$value->picture,
                        'description'=>$value->description,
                        'status'=>$is_block
                        ];
            }

            $response_array = ['success'=>true, 'data'=>$data];

        }

        return response()->json($response_array, 200);

    }

    /**
     * Function Name : check_coupon_applicable_to_user()
     *
     * To check the coupon code applicable to the user or not
     *
     * @created_by - Shobana Chandrasekar
     *
     * @updated_by - 
     *
     * @param objects $coupon - Coupon details
     *
     * @param objects $user - User details
     *
     * @return response of success/failure message
     */
    public function check_coupon_applicable_to_user($user, $coupon) {

        try {

            $sum_of_users = UserCoupon::where('coupon_code', $coupon->coupon_code)->sum('no_of_times_used');

            if ($sum_of_users < $coupon->no_of_users_limit) {


            } else {

                throw new Exception(tr('total_no_of_users_maximum_limit_reached'));
                
            }

            $user_coupon = UserCoupon::where('user_id', $user->id)
                ->where('coupon_code', $coupon->coupon_code)
                ->first();

            // If user coupon not exists, create a new row

            if ($user_coupon) {

                if ($user_coupon->no_of_times_used < $coupon->per_users_limit) {

                   // $user_coupon->no_of_times_used += 1;

                   // $user_coupon->save();

                    $response_array = ['success'=>true, 'message'=>tr('add_no_of_times_used_coupon'), 'code'=>2002];

                } else {

                    throw new Exception(tr('per_users_limit_exceed'));
                }

            } else {

                $response_array = ['success'=>true, 'message'=>tr('create_a_new_coupon_row'), 'code'=>2001];

            }

            return response()->json($response_array);

        } catch (Exception $e) {

            $response_array = ['success'=>false, 'error_messages'=>$e->getMessage()];

            return response()->json($response_array);
        }

    }

    /**
     * Function Name : pay_now()
     * 
     * @usage_place : MOBILE
     *
     * Pay the amount through paypal (For Subscription)
     *
     * @param object $request - Plan Details
     * 
     * @return user Details and payment Details
     */
    public function pay_now(Request $request) {

        try {
            
            DB::beginTransaction();

            $validator = Validator::make(
                $request->all(),
                array(
                    'subscription_id'=>'required|exists:subscriptions,id',
                    'payment_id'=>'required',
                    'coupon_code'=>'nullable|exists:coupons,coupon_code',
                ), array(
                    'coupon_code.exists' => tr('coupon_code_not_exists'),
                    'subscription_id.exists' => tr('subscription_not_exists'),
            ));

            if ($validator->fails()) {
                // Error messages added in response for debugging
                $errors = implode(',',$validator->messages()->all());

                throw new Exception($errors, 101);

            } else {

                $user = User::find($request->id);

                $subscription = Subscription::find($request->subscription_id);

                $total = $subscription->amount;

                $coupon_amount = 0;

                $coupon_reason = '';

                $is_coupon_applied = COUPON_NOT_APPLIED;

                if ($request->coupon_code) {

                    $coupon = Coupon::where('coupon_code', $request->coupon_code)->first();

                    if ($coupon) {
                        
                        if ($coupon->status == COUPON_INACTIVE) {

                            $coupon_reason = tr('coupon_inactive_reason');

                        } else {

                            $check_coupon = $this->check_coupon_applicable_to_user($user, $coupon)->getData();

                            if ($check_coupon->success) {

                                $is_coupon_applied = COUPON_APPLIED;

                                $amount_convertion = $coupon->amount;

                                if ($coupon->amount_type == PERCENTAGE) {

                                    $amount_convertion = round(amount_convertion($coupon->amount, $subscription->amount), 2);

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

                        $coupon_reason = tr('coupon_delete_reason');
                    }
                }

                $model = UserSubscription::where('user_id' , $request->id)
                            ->where('status', DEFAULT_TRUE)
                            ->orderBy('id', 'desc')->first();

                $user_payment = new UserSubscription();

                if ($model) {

                    if (strtotime($model->expiry_date) >= strtotime(date('Y-m-d H:i:s'))) {

                         $user_payment->expiry_date = date('Y-m-d H:i:s', strtotime("+{$subscription->plan} months", strtotime($model->expiry_date)));

                    } else {

                        $user_payment->expiry_date = date('Y-m-d H:i:s',strtotime("+{$subscription->plan} months"));

                    }

                } else {

                    $user_payment->expiry_date = date('Y-m-d H:i:s',strtotime("+{$subscription->plan} months"));

                }

                $user_payment->payment_id  = $request->payment_id;
                $user_payment->user_id = $request->id;
                $user_payment->subscription_id = $request->subscription_id;

                $user_payment->status = PAID_STATUS;

                $user_payment->payment_mode = PAYPAL;

                // Coupon details

                $user_payment->is_coupon_applied = $is_coupon_applied;

                $user_payment->coupon_code = $request->coupon_code  ? $request->coupon_code  :'';

                $user_payment->coupon_amount = $coupon_amount;

                $user_payment->subscription_amount = $subscription->amount;

                $user_payment->amount = $total;

                $user_payment->coupon_reason = $is_coupon_applied == COUPON_APPLIED ? '' : $coupon_reason;
 
                if($user_payment->save()) {

                    if ($user) {

                        $user->user_type = 1;

                        $user->amount_paid += $total;

                        $user->expiry_date = $user_payment->expiry_date;

                        $now = time(); // or your date as well

                        $end_date = strtotime($user->expiry_date);

                        $datediff =  $end_date - $now;

                        $user->no_of_days = ($user->expiry_date) ? floor($datediff / (60 * 60 * 24)) + 1 : 0;

                        if ($user_payment->amount <= 0) {

                            $user->one_time_subscription = 1;
                        }

                        if ($user->save()) {

                            $response_array = ['success'=>true, 
                                    'message'=>tr('payment_success'), 
                                    'data'=>[
                                        'id'=>$request->id,
                                        'token'=>$user_payment->user ? $user_payment->user->token : '',
                                ]];

                        } else {


                            throw new Exception(tr('user_details_not_saved'));
                            
                        }

                    } else {

                        throw new Exception(tr('user_not_found'));
                        
                    }
                }

            }

            DB::commit();

            return response()->json($response_array, 200);

        } catch(Exception $e) {

            DB::rollback();

            $message = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages'=>$message, 'error_code'=>$code];

            return response()->json($response_array);

        }

    }

    /**
     * Function Name :video_subscription()
     *
     * To view the live video need to pay some amount to the streamer then watch the video
     * 
     * @created_by Shobana Chandrasekar
     *
     * @updated_by -
     *
     * @param object $request - Video Details , coupon details with user details
     *
     * @return response of success/failure details with coupons
     */
    public function video_subscription(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'video_id'=>'required|exists:live_videos,id',
                'coupon_code'=>'exists:coupons,coupon_code',
                'payment_id'=>'required',

            ), array(
                    'coupon_code.exists' => tr('coupon_code_not_exists'),
                    'video_id.exists' => tr('livevideo_not_exists'),
            ));

        if ($validator->fails()) {
            // Error messages added in response for debugging
            $errors = implode(',',$validator->messages()->all());

            $response_array = ['success' => false,'error_messages' => $errors,'error_code' => 101];

        } else {

            $live_video = LiveVideo::find($request->video_id);

            $viewerModel = User::find($request->id);

            if ($live_video->status) {

               $response_array = ['success'=>false, 'error_messages'=>tr('stream_stopped')]; 

            } else {

                $total = $live_video->amount;

                $coupon_amount = 0;

                $coupon_reason = '';

                $is_coupon_applied = COUPON_NOT_APPLIED;

                if ($request->coupon_code) {

                    $coupon = Coupon::where('coupon_code', $request->coupon_code)->first();

                    if ($coupon) {
                        
                        if ($coupon->status == COUPON_INACTIVE) {

                            $coupon_reason = tr('coupon_inactive_reason');

                        } else {

                            $check_coupon = $this->check_coupon_applicable_to_user($viewerModel, $coupon)->getData();

                            if ($check_coupon->success) {

                                $is_coupon_applied = COUPON_APPLIED;

                                $amount_convertion = $coupon->amount;

                                if ($coupon->amount_type == PERCENTAGE) {

                                    $amount_convertion = round(amount_convertion($coupon->amount, $live_video->amount), 2);

                                }

                                if ($amount_convertion < $live_video->amount) {

                                    $total = $live_video->amount - $amount_convertion;

                                    $coupon_amount = $amount_convertion;

                                } else {

                                    // throw new Exception(Helper::get_error_message(156),156);

                                    $total = 0;

                                    $coupon_amount = $amount_convertion;
                                    
                                }

                                // Create user applied coupon

                                if($check_coupon->code == 2002) {

                                    $user_coupon = UserCoupon::where('user_id', $viewerModel->id)
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

                                    $user_coupon->user_id = $viewerModel->id;

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

                $user_payment = new LiveVideoPayment;

                $check_live_video_payment = LiveVideoPayment::where('live_video_viewer_id' , $request->id)->where('live_video_id' , $request->video_id)->first();

                if($check_live_video_payment) {
                    $user_payment = $check_live_video_payment;
                }

                $user_payment->payment_id  = $request->payment_id;
                $user_payment->live_video_viewer_id = $request->id;
                $user_payment->live_video_id = $request->video_id;
                $user_payment->user_id = $live_video->user_id;
                $user_payment->status = DEFAULT_TRUE;
                $user_payment->payment_mode = PAYPAL;
                $user_payment->currency = Setting::get('currency');

                 // Coupon details

                $user_payment->is_coupon_applied = $is_coupon_applied;

                $user_payment->coupon_code = $request->coupon_code ? $request->coupon_code : '';

                $user_payment->coupon_amount = $coupon_amount;

                $user_payment->live_video_amount = $live_video->amount;

                $user_payment->amount = $total;

                $user_payment->coupon_reason = $is_coupon_applied == COUPON_APPLIED ? '' : $coupon_reason;

                if($user_payment->save()) {

                    // Commission Spilit 

                    $admin_commission = Setting::get('admin_commission')/100;

                    $admin_amount = $total * $admin_commission;

                    $user_amount = $total - $admin_amount;

                    $user_payment->admin_amount = $admin_amount;

                    $user_payment->user_amount = $user_amount;

                    $user_payment->save();

                    // Commission Spilit Completed

                    if($user = User::find($user_payment->user_id)) {

                        $user->total_admin_amount = $user->total_admin_amount + $admin_amount;

                        $user->total_user_amount = $user->total_user_amount + $user_amount;

                        $user->remaining_amount = $user->remaining_amount + $user_amount;

                        $user->total = $user->total + $total;

                        $user->save();

                        add_to_redeem($user->id , $user_amount);
                    
                    }

                }

                $response_array = ['success'=>true, 'message'=>tr('payment_success'), 
                            'data'=>['id'=>$request->id,
                                     'token'=>$viewerModel ? $viewerModel->token : '']];
           
            }

        }

        return response()->json($response_array, 200);

    }

    public function get_viewers(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'video_id'=>'required|exists:live_videos,id',
            ));

        if ($validator->fails()) {
            // Error messages added in response for debugging
            $errors = implode(',',$validator->messages()->all());

            $response_array = ['success' => false,'error_messages' => $errors,'error_code' => 101];

        } else {

            $viewer_cnt = 0;

            $live_video = LiveVideo::find($request->video_id);

            if ($live_video) {

                if ($live_video->user_id != $request->id) {


                    // Load Viewers model

                    $model = Viewer::where('video_id', $request->video_id)->where('user_id', $request->id)->first();

                    $new_user = 0;

                    if(!$model) {

                        $new_user = 1;

                        $model = new Viewer;

                        $model->video_id = $request->video_id;

                        $model->user_id = $request->id;

                    }

                    $model->count = ($model->count) ? $model->count + 1 : 1;

                    $model->save();

                    if ($new_user) {

                        $live_video->viewer_cnt += 1;

                        $live_video->save();

                    }

                }

                $viewer_cnt = $live_video->viewer_cnt ? $live_video->viewer_cnt : 0;

                
            }

            $response_array  = ['success'=>true, 
                    'viewer_cnt'=> (int) $viewer_cnt];
            
        }

        return response()->json($response_array);
    
    }

    /**
     * Function Name : subscribedPlans()
     *
     * Based on logged in user , subscribed plans details will display
     *
     * @param object $request - skip, user id, token
     *
     * @return plan details
     */
    public function subscribedPlans(Request $request){

        $validator = Validator::make(
            $request->all(),
            array(
                'skip'=>'required|numeric',
            ));

        if ($validator->fails()) {

            // Error messages added in response for debugging
            
            $errors = implode(',',$validator->messages()->all());

            $response_array = ['success' => false,'error_messages' => $errors,'error_code' => 101];

        } else {


            $model = UserSubscription::where('user_id' , $request->id)
                        ->leftJoin('subscriptions', 'subscriptions.id', '=', 'subscription_id')
                        ->select('user_id as id',
                                'subscription_id',
                                'user_subscriptions.id as user_subscription_id',
                                'subscriptions.title as title',
                                'subscriptions.description as description',
                                'subscriptions.popular_status as popular_status',
                                'subscriptions.plan',
                                'subscriptions.amount as main_subscription_amount',
                                'user_subscriptions.amount as amount',
                                'user_subscriptions.status as status',
                                // 'user_subscriptions.expiry_date as expiry_date',
                                \DB::raw('DATE_FORMAT(user_subscriptions.expiry_date , "%e %b %Y") as expiry_date'),
                                'user_subscriptions.created_at as created_at',
                                DB::raw("'$' as currency"),
                                'user_subscriptions.payment_mode',
                                'user_subscriptions.is_coupon_applied',
                                'user_subscriptions.coupon_code',
                                'user_subscriptions.coupon_amount',
                                'user_subscriptions.subscription_amount',
                                'user_subscriptions.coupon_reason',
                                'user_subscriptions.is_cancelled',
                                'user_subscriptions.payment_id',
                                'user_subscriptions.cancel_reason')
                        ->orderBy('user_subscriptions.updated_at', 'desc')
                        ->skip($request->skip)
                        ->take(Setting::get('admin_take_count' ,12))
                        ->get();

            $data = [];

            foreach ($model as $key => $value) {
                
                $data[] = [

                    'id'=>$value->id,
                    'subscription_id'=>$value->subscription_id,
                    'user_subscription_id'=>$value->user_subscription_id,
                    'title'=>$value->title,
                    'description'=>$value->description,
                    'popular_status'=>$value->popular_status,
                    'plan'=>$value->plan,
                    'amount'=>$value->amount,
                    'status'=>$value->status,
                    'expiry_date'=>$value->expiry_date,
                    'created_at'=>$value->created_at,
                    'currency'=>$value->currency,
                    'payment_mode'=>$value->payment_mode,
                    'is_coupon_applied'=>$value->is_coupon_applied,
                    'coupon_code'=>$value->coupon_code,
                    'coupon_amount'=>$value->coupon_amount,
                    'subscription_amount'=>$value->subscription_amount,
                    'coupon_reason'=>$value->coupon_reason,
                    'is_cancelled'=>$value->is_cancelled,
                    'payment_id'=>$value->payment_id,
                    'cancel_reason'=>$value->cancel_reason,
                    'show_autorenewal_options'=> ($value->status && $key == 0) ? ($value->main_subscription_amount > 0 ? DEFAULT_TRUE :  DEFAULT_FALSE ): DEFAULT_FALSE ,
                    'show_pause_autorenewal'=> ($value->status && $key == 0 && $value->is_cancelled == AUTORENEWAL_ENABLED) ? DEFAULT_TRUE : DEFAULT_FALSE,
                    'show_enable_autorenewal'=> ($value->status && $key == 0 && $value->is_cancelled == AUTORENEWAL_CANCELLED) ? DEFAULT_TRUE : DEFAULT_FALSE,

                ];

            }


            $response_array = ['success'=>true, 'data'=>$data];

        }

        return response()->json($response_array);

    }

    /**
     * Function Name : peerProfile
     *
     * To view the user profile based on the other user
     *
     * @param object $request Peer Id
     *
     * @return other profile details
     */
    public function peerProfile(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'peer_id'=>'required|exists:users,id',
            ));

        if ($validator->fails()) {
            // Error messages added in response for debugging
            $errors = implode(',',$validator->messages()->all());

            $response_array = ['success' => false,'error_messages' => $errors,'error_code' => 101];

        } else {

            $user = User::find($request->peer_id);

            $follower_list = [];

            $followers = Follower::select('user_id as id',
                            'users.name as name', 
                            'users.email as email', 
                            'users.picture',
                            'users.description' ,
                            'followers.follower as follower_id' ,
                            'followers.created_at as created_at'
                           )
                    ->leftJoin('users' , 'users.id' ,'=' , 'followers.follower')
                    ->where('followers.user_id', $request->peer_id)
                    ->skip($request->skip)
                    ->take(Setting::get('admin_take_count', 12))
                    ->orderBy('created_at', 'desc')
                    ->get();


            foreach ($followers as $key => $value) {

                $model = Follower::where('follower', $request->id)->where('user_id', $value->follower_id)->first();

                $follower_is_follow = DEFAULT_FALSE;

                if($model) {

                    $follower_is_follow = DEFAULT_TRUE;

                }


                if ($request->id == $value->follower_id) {

                    $follower_is_follow = -1;

                }


                $follower_block = BlockList::where('user_id', $request->id)->where('block_user_id', $value->follower_id)->first();

                $follower_block_by_user = BlockList::where('user_id', $value->follower_id)->where('block_user_id', $request->id)->first();

                $follower_is_block = DEFAULT_FALSE;

                if($follower_block || $follower_block_by_user) {

                    $follower_is_block = DEFAULT_TRUE;

                }

                $no_of_followers = Follower::where('user_id', $value->follower_id)->count();


                $follower_list[] = [
                        'id'=>$value->id, 
                        'name'=>$value->name,
                        'email'=>$value->email,
                        'picture'=>$value->picture,
                        'description'=>$value->description,
                        'follower_id'=>$value->follower_id,
                        'status'=>$follower_is_follow,
                        'is_block'=>$follower_is_block,
                        'no_of_followers'=>$no_of_followers,
                        ];
            }

            $followings_list = [];

            $followings = Follower::select('followers.follower as id',
                            'users.name as name', 
                            'users.email as email', 
                            'users.picture',
                            'users.description',
                            'users.id as follower_id' ,
                            'followers.created_at as created_at' 
                           )
                    ->leftJoin('users' , 'users.id' ,'=' , 'followers.user_id')
                    ->where('follower', $request->peer_id)
                    ->skip($request->skip)
                    ->take(Setting::get('admin_take_count', 12))
                    ->orderBy('created_at', 'desc')
                    ->get();


            foreach ($followings as $key => $value) {

                $model = Follower::where('follower', $request->id)->where('user_id', $value->follower_id)->first();

                $followings_is_follow = DEFAULT_FALSE;

                if($model) {

                    $followings_is_follow = DEFAULT_TRUE;

                }

                if ($request->id == $value->follower_id) {

                    $followings_is_follow = -1;

                }

                $follwing_block = BlockList::where('user_id', $request->id)->where('block_user_id', $value->follower_id)->first();

                $follower_block_by_user = BlockList::where('user_id', $value->follower_id)->where('block_user_id', $request->id)->first();

                $following_is_block = DEFAULT_FALSE;

                if($follwing_block || $follower_block_by_user) {

                    $following_is_block = DEFAULT_TRUE;

                }

                $no_of_followers = Follower::where('user_id', $value->follower_id)->count();
                
                $followings_list[] = [
                        'id'=>$value->id, 
                        'name'=>$value->name,
                        'email'=>$value->email,
                        'picture'=>$value->picture,
                        'description'=>$value->description,
                        'follower_id'=>$value->follower_id,
                        'status'=>$followings_is_follow,
                        'is_block'=>$following_is_block,
                        'no_of_followers'=>$no_of_followers ? $no_of_followers : 0
                ];
            }


            $model = Follower::where('follower', $request->id)->where('user_id', $request->peer_id)->first();

            $is_follow = DEFAULT_FALSE;

            if($model) {

                $is_follow = DEFAULT_TRUE;

            }

            
            $block = BlockList::where('user_id', $request->id)->where('block_user_id', $request->peer_id)->first();


            // Blocked By Others
            $blockedUsersBythisPeer = BlockList::where('block_user_id', $request->id)
                    ->where('user_id', $request->peer_id)->first();


            $is_block = DEFAULT_FALSE;

            if($block || $blockedUsersBythisPeer) {

                $is_block = DEFAULT_TRUE;

            }

            $is_userLive = false;

            $video_id = "";

            $blockUserIds = [];

            $public_video = [];

            if (!$blockedUsersBythisPeer) {

                $public_video = LiveVideo::where("is_streaming", DEFAULT_TRUE)

                        ->videoResponse()

                        ->leftJoin('users' , 'users.id' ,'=' , 'live_videos.user_id')

                        ->where('live_videos.status', DEFAULT_FALSE)

                        ->where('live_videos.type', TYPE_PUBLIC)

                        ->where('live_videos.user_id',$request->peer_id)

                        ->get();

            }

            $public_videos = [];

            foreach ($public_video as $key => $value) {

                $videopayment = LiveVideoPayment::where('live_video_id', $value->id)
                    ->where('live_video_viewer_id', $request->id)
                    ->where('status',DEFAULT_TRUE)->first();

                $public_videos[] = [
                    "video_id"=> $value->video_id,
                    "title"=> $value->title,
                    "type"=> $value->type,
                    'payment_status' => $value->payment_status ? $value->payment_status : 0,
                    "description"=> $value->description,
                    "amount"=> $value->amount,
                    "snapshot"=> $value->snapshot,
                    "viewers"=> $value->viewers ? $value->viewers : 0,
                    "no_of_minutes"=> $value->no_of_minutes,
                    "date"=> $value->date,
                    'currency'=> Setting::get('currency'),
                    "share_link"=>Setting::get('ANGULAR_URL').'live-video/'.$value->id,
                    'video_stopped_status'=>$value->video_stopped_status,
                    'video_payment_status'=> $videopayment ? DEFAULT_TRUE : DEFAULT_FALSE,
                    "live_group_id"=> $value->live_group_id ? $value->live_group_id : 0,
                    'live_group_name'=>$value->getLiveGroup ? $value->getLiveGroup->name : "",
                ];

                $is_userLive = true;

                $video_id = $value->video_id;
            }

            $private_videos = [];


            if ($is_follow) {


                $private_video = LiveVideo::where("is_streaming", DEFAULT_TRUE)

                        ->videoResponse()

                        ->leftJoin('users' , 'users.id' ,'=' , 'live_videos.user_id')

                        ->where('live_videos.status', DEFAULT_FALSE)->where('type', TYPE_PRIVATE)

                        ->where('live_videos.user_id',$request->peer_id)

                        ->get();

                foreach ($private_video as $key => $value) {

                    $videopayment = LiveVideoPayment::where('live_video_id', $value->id)
                        ->where('live_video_viewer_id', $request->id)
                        ->where('status',DEFAULT_TRUE)->first();

                    $private_videos[] = [
                        "video_id"=> $value->video_id,
                        "title"=> $value->title,
                        "type"=> $value->type,
                        'payment_status' => $value->payment_status ? $value->payment_status : 0,
                        "description"=> $value->description,
                        "amount"=> $value->amount,
                        "snapshot"=> $value->snapshot,
                        "viewers"=> $value->viewers ? $value->viewers : 0,
                        "no_of_minutes"=> $value->no_of_minutes,
                        "date"=> $value->date,
                        'currency'=> Setting::get('currency'),
                        "share_link"=>Setting::get('ANGULAR_URL').'live-video/'.$value->id,
                        'video_stopped_status'=>$value->video_stopped_status,
                        'video_payment_status'=> $videopayment ? DEFAULT_TRUE : DEFAULT_FALSE,
                        "live_group_id"=> $value->live_group_id ? $value->live_group_id : 0,
                        'live_group_name'=>$value->getLiveGroup ? $value->getLiveGroup->name : "",
                    ];

                    $is_userLive = true;

                    $video_id = $value->video_id;
                }
            }

            // For streamere gallery

            $request->request->add([
                'skip'=>0,
                'user_id'=>$user->id
            ]);

            $galleries = StreamerGalleryRepo::streamer_galleries_list($request)->getData();
            
            $response_array = array(
                'success' => true,
                'id' => $user->id,
                'name' => $user->name,
                'mobile' => $user->mobile,
                'gender' => $user->gender,
                'email' => $user->email,
                'picture' => $user->picture,
                'chat_picture' => $user->chat_picture ? $user->chat_picture : $user->picture,
                'description'=>$user->description,
                'token' => $user->token,
                'token_expiry' => $user->token_expiry,
                'login_by' => $user->login_by,
                'social_unique_id' => $user->social_unique_id,
                'followers'=>$follower_list,
                'followings'=>$followings_list,
                'no_of_followers'=>$follower_list ? count($follower_list) : 0 ,
                'no_of_followings'=>$followings_list ? count($followings_list) : 0,
                'is_follow'=>$is_follow,
                'is_block'=>$is_block,
                'cover' => $user->cover ? $user->cover : asset('cover.jpg'),
                'public_videos'=>$public_videos,
                'private_videos'=>$private_videos,
                'is_userLive'=>$is_userLive,
                'is_content_creator'=>$user->is_content_creator,
                'video_id'=>$video_id,
                'gallery_description'=>$user->gallery_description,
                'galleries'=>$galleries->success ? $galleries->data : []
            );

            $response_array = response()->json(Helper::null_safe($response_array), 200);

        }

        return $response_array;

    }

    public function close_streaming(Request $request) {

        $validator = Validator::make(
            $request->all(), array(
                'video_id'=>'required|exists:live_videos,id',
        ));

        if ($validator->fails()) {
            // Error messages added in response for debugging
            $errors = implode(',',$validator->messages()->all());

            $response_array = ['success' => false,'error_messages' => $errors,'error_code' => 101];

        } else {

            // Load Model
            $model = LiveVideo::find($request->video_id);

            $model->status = DEFAULT_TRUE;

            $model->end_time = getUserTime(date('H:i:s'), ($model->user) ? $model->user->timezone : '', "H:i:s");

            $model->no_of_minutes = getMinutesBetweenTime($model->start_time, $model->end_time);

            if ($model->save()) {

                if ($request->device_type == DEVICE_WEB) {

                    if ($model->user_id == $request->id) {  

                        if (Setting::get('wowza_server_url')) {

                            $this->disConnectStream($model->user_id.'-'.$model->id);

                        }

                    }

                }

                $response_array = ['success'=>true, 'message'=>tr('streaming_stopped')];
            }
        }

        return response()->json($response_array,200);
    
    }

    /**
     * Function Name : checkVideoStreaming()
     *
     * Check video streaming every 10/5 sec to chek whether the streaming happening or not
     *
     * @param integer $request - Video Id
     *
     * @return response of boolean with message
     */ 
    public function checkVideoStreaming(Request $request) {

        try {

            $validator = Validator::make(
                $request->all(), array(
                    'video_id'=>'required|exists:live_videos,id',
            ));

            if ($validator->fails()) {
                // Error messages added in response for debugging
                $errors = implode(',',$validator->messages()->all());

                throw new Exception($errors);                

            } else {

                $video = LiveVideo::find($request->video_id);

                if ($video) {

                    if($video->is_streaming) {

                        if (!$video->status) {

                           /* $timezone = ($video->user) ? ($video->user->timezone ? $video->user->timezone : 'Asia/Kolkata') : 'Asia/Kolkata';

                            $now = getUserTime(date('H:i:s'), $timezone , "H:i:s");    

                            $time1 = new DateTime($video->start_time, new DateTimeZone($timezone));
                            
                            $time2 = new DateTime($now, new DateTimeZone($timezone));

                            $time1->setTimeZone(new DateTimeZone('UTC'));

                            $time2->setTimeZone(new DateTimeZone('UTC'));


                            $interval = $time1->diff($time2);

                            $hour = $interval->format('%h hour');

                            $min = $interval->format('%i min');

                            $sec = $interval->format('%s second');


                            if ($hour > 0) {

                                $video->no_of_minutes =  $hour.' '.$min.' '.$sec;

                            } else if ($min > 0) {

                                $video->no_of_minutes =  $min.' '.$sec;

                            } else{

                                $video->no_of_minutes = $sec;

                            }*/

                            $response_array = [

                                'success'=> true, 
                                'message'=>tr('video_streaming'), 
                                'data'=>['viewer_cnt' => (int) ($video->viewer_cnt ? $video->viewer_cnt : 0),
                                //'no_of_minutes'=>$video->no_of_minutes
                                ]
                            ];

                        } else {

                            throw new Exception(tr('stream_stopped'), 550);
                        }

                    } else {

                        throw new Exception(tr('no_streaming_video_present'), 101);

                    }

                } else {

                    throw new Exception(tr('no_live_video_present'));
                    
                }
               
                return response()->json($response_array,200);

            }

        } catch (Exception $e) {

            $message = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages'=>$message, 'error_code'=>$code];

            return $response_array;
        }

    }


    /**
     * Function Name : check_user_call()
     *
     * To check the user have already any ongng calls or not
     *
     * @param integer $request - User  Id
     *
     * @return response of boolean with message
     */
    public function check_user_call(Request $request) {

        $model = LiveVideo::where('user_id', $request->id)->where('status', VIDEO_STREAMING_ONGOING)->count();

        if($model) {

            return response()->json(['success'=>false, 'data'=>$model, 'error_messages'=>tr('video_call_already_present')]);

        } else {

            return response()->json(['success'=>true]);

        }

    }

    /**
     * Function Name : erase_videos()
     *
     * To erase all the videos based on the user id
     *
     * @param integer $request - User Id
     *
     * @return response of boolean with message
     */
    public function erase_videos(Request $request) {

        /*$video = LiveVideo::where('user_id',$request->id)
                    ->where('status', DEFAULT_FALSE)
                    ->get();

        if ($video->count() > 0) {

            foreach ($video as $key => $value) {

                if ($value->is_streaming) {

                    $value->status = DEFAULT_TRUE;

                    $value->end_time = getUserTime(date('H:i:s'), ($value->user) ? $value->user->timezone : '', "H:i:s");

                    $value->no_of_minutes = getMinutesBetweenTime($value->start_time, $value->end_time);

                    $value->save();

                } else {
             
                    $value->delete();

                }

            }

            $response_array = ['success'=>true, 'message'=>tr('proceed_to_start_stream')];

        } else {

            $response_array = ['success'=>false, 'error_messages'=>tr('no_live_video_present')];
        }
*/
        LiveVideo::where('user_id', $request->id)->where('status', VIDEO_STREAMING_ONGOING)->where('is_streaming', IS_STREAMING_NO)->delete();

        $live_videos = LiveVideo::where('user_id', $request->id)->where('status', VIDEO_STREAMING_ONGOING)->where('is_streaming', IS_STREAMING_YES)->get();

        foreach($live_videos as $key => $live_video) {

            $live_video->status = DEFAULT_TRUE;

            $live_video->end_time = getUserTime(date('H:i:s'), $this->timezone, 'H:i:s');

            $live_video->no_of_minutes = getMinutesBetweenTime($live_video->start_time, $live_video->end_time);

            $live_video->save();

        }

        return $this->sendResponse(api_success(142), $code = 142, $data = []);    
    }

    /**
     * Function name : live_video_snapshot()
     *
     * @usage : used to save the live video snapshot 
     *
     * Created By : vidhya R
     *
     * Edited By : - 
     *
     * @return JSON response 
     */
    public function live_video_snapshot(Request $request) {

      //  Log::info("snapshot".print_r($request->snapshot , true));

        // Log::info("device_type ".print_r($request->device_type , true));

        try {

            $validator = Validator::make(
                $request->all(), array(
                'video_id'=>'required|exists:live_videos,id',
                'snapshot' => 'required',
            ));

            if ($validator->fails()) {

                // Error messages added in response for debugging

                $errors = implode(',',$validator->messages()->all());

                throw new Exception($errors);                

            } else {


                $live_video_details = LiveVideo::find($request->video_id);

                if ($live_video_details) {

                    if(!$live_video_details->is_streaming) {

                        throw new Exception(tr('stream_stopped'));

                    } else {

                        File::isDirectory(public_path().'/uploads/rooms') or File::makeDirectory(public_path().'/uploads/rooms', 0777, true, true);


                        if ($request->snapshot) {

                            if($request->device_type == DEVICE_WEB) {

                                $data = explode(',', $request->get('snapshot'));

                                file_put_contents(join(DIRECTORY_SEPARATOR, [public_path(), 'uploads', 'rooms', $request->video_id . '.png']), base64_decode($data[1]));

                                $live_video_details->snapshot = Helper::web_url()."/uploads/rooms/".$request->video_id . '.png';

                            } else if ($request->device_type == DEVICE_IOS) {


                                if($request->snapshot) {

                                    $picture = $request->file('snapshot');
                                    
                                    $ext = $picture->getClientOriginalExtension();

                                    $picture->move(public_path()."/uploads/rooms/", $$request->video_id . "." . $ext);


                                }
                                
                                $live_video_details->snapshot = Helper::web_url()."/uploads/rooms/".$request->video_id . '.'.$ext;

                            }else{

                                file_put_contents(join(DIRECTORY_SEPARATOR, [public_path(), 'uploads', 'rooms', $request->video_id . '.png']), base64_decode($request->snapshot));

                                $live_video_details->snapshot = Helper::web_url()."/uploads/rooms/".$request->video_id . '.png';


                            }

                            

                            $live_video_details->save();

                            $response_array = ['success' => true];

                        } else {

                            throw new Exception(tr('snapshot_empty'));


                        }

                    } 

                } else {

                    throw new Exception(tr('no_live_video_present'));
                    
                }
               
                return response()->json($response_array,200);

            }

        } catch (Exception $e) {

            $message = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages'=>$message, 'error_code'=>$code];

            return $response_array;
        }

    }

    /**
     * Function name : subscription_invoice()
     *
     * To get particualr subscription details based on the id
     *
     * @param object $request - User id, token, subscription id
     *
     * @return subscription details
     */
    public function subscription_invoice(Request $request) {

        try {

            $validator = Validator::make(
                $request->all(), array(
                    'subscription_id'=>'required|exists:subscriptions,id',
            ));

            if ($validator->fails()) {
                // Error messages added in response for debugging
                $errors = implode(',',$validator->messages()->all());

                throw new Exception($errors);                

            } else {

                $model = Subscription::select('id as subscription_id',
                    'title', 'description', 'plan', 'amount')
                ->find($request->subscription_id);

                if ($model) {

                    $model['currency'] = Setting::get('currency');

                    $model['amount'] = (int) $model->amount;

                    $response_array = ['success'=>true, 'data'=>$model];

                } else {

                    throw new Exception(tr('subscription_not_found'));

                }

            }

            return $response_array;

        } catch(Exception $e) {

            $message = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages'=>$message, 'error_code'=>$code];

            return $response_array;
        }
    
    }

    /**
     * Function name : live_video_invoice()
     *
     * To get particualr live video details based on the live_video id
     *
     * @param object $request - User id, token, live video id
     *
     * @return subscription details
     */
    public function live_video_invoice(Request $request) {

        try {

            $currency = Setting::get('currency');

            $validator = Validator::make(
                $request->all(), array(
                    'live_video_id'=>'required|exists:live_videos,id',
            ));

            if ($validator->fails()) {
                // Error messages added in response for debugging
                $errors = implode(',',$validator->messages()->all());

                throw new Exception($errors);                

            } else {

                $model = LiveVideo::select('user_id as id', 'id as live_video_id', 'virtual_id',
                'type', 'payment_status', 'title', 'description', 'amount', 
                'is_streaming', 'snapshot as picture', 'viewer_cnt', 'status as call_status',
                DB::raw('DATE_FORMAT(live_videos.created_at , "%e %b %y") as streamed_at'),
                DB::raw("'$currency' as currency"))
                ->find($request->live_video_id);

                if ($model) {

                    $response_array = ['success'=>true, 'data'=>$model];

                } else {

                    throw new Exception(tr('live_video_not_found'));

                }

            }

            return $response_array;

        } catch(Exception $e) {

            $message = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages'=>$message, 'error_code'=>$code];

            return $response_array;
        }
    
    }

    /**
     * Function Name : videos_info()
     *
     * To list out all streamd videos based on logged in user
     *
     * @param object $request - User Id, token
     *
     * @return video info details
     */
    public function videos_info(Request $request) {

        $validator = Validator::make(
                $request->all(),
            array(
                'skip'=>'required|numeric',
            ));

        if ($validator->fails()) {

            // Error messages added in response for debugging
            
            $errors = implode(',',$validator->messages()->all());

            $response_array = ['success' => false,'error_messages' => $errors,'error_code' => 101];

        } else {


            $currency = Setting::get('currency');

            $model = LiveVideo::select('user_id as id', 'id as live_video_id', 'virtual_id',
                'type', 'payment_status', 'title', 'description', 'amount', 
                'is_streaming', 'snapshot as picture', 'viewer_cnt', 'status as video_status',
                DB::raw('DATE_FORMAT(live_videos.created_at , "%e %b %y %H:%i:%s") as streamed_at'),
                DB::raw("'$currency' as currency"))
                            ->where('user_id', $request->id)
                            ->orderBy('created_at', 'desc')
                            ->skip($request->skip)
                            ->take(Setting::get('admin_take_count' ,12))
                            ->get();

            $items = [];

            foreach ($model as $key => $value) {

                $earnings = LiveVideoPayment::where('live_video_id', $value->live_video_id)->sum('user_amount');

                $status = $value->is_streaming ? ($value->video_status ? tr('video_call_ended') : tr('video_call_started')) : tr('video_call_initiated');
                
                $items[] = ['id'=>$value->id, 'live_video_id'=>$value->live_video_id,
                    'virtual_id'=>$value->virtual_id, 'type'=>$value->type,
                    'payment_status'=>$value->payment_status, 'title'=>$value->title,
                    'description'=>$value->description, 'amount'=>$value->amount,
                    'is_streaming'=>$value->is_streaming, 'picture'=>$value->picture,
                    'viewer_cnt'=>(int) $value->viewer_cnt, 'video_status'=>$status,
                    'streamed_at'=>$value->streamed_at, 'currency'=>$value->currency,
                    'earnings'=>$earnings ? $earnings : 0,
                    "live_group_id"=> $value->live_group_id ? $value->live_group_id : 0,
                    'live_group_name'=>$value->getLiveGroup ? $value->getLiveGroup->name : "",];

            }

            $response_array = ['success'=>true, 'data'=>$items];

        }

        return response()->json($response_array);
    
    }

    /**
     * Function Name : paid_videos()
     *
     * To list out all paid videos based on logged in user
     *
     * @param object $request - User Id, token
     *
     * @return video info details
     */
    public function paid_videos(Request $request) {

        $validator = Validator::make(
                $request->all(),
            array(
                'skip'=>'required|numeric',
            ));

        if ($validator->fails()) {

            // Error messages added in response for debugging
            
            $errors = implode(',',$validator->messages()->all());

            $response_array = ['success' => false,'error_messages' => $errors,'error_code' => 101];

        } else {

            $currency = Setting::get('currency');

            $model = LiveVideoPayment::select('live_video_viewer_id as id', 
                'live_video_payments.id as live_video_payment_id', 
                'live_video_payments.user_id as streamer_id', 
                'live_video_id', 'payment_id', 'live_video_payments.amount',
                DB::raw("'$currency' as currency"), 
                'live_video_payments.status as paid_status',
                'type', 'payment_status', 
                'title', 
                'description', 
                'is_streaming', 
                'snapshot as picture', 
                'viewer_cnt', 
                'live_videos.status as call_status',
                'live_video_payments.payment_mode',
                'live_video_payments.coupon_reason',
                'live_video_payments.coupon_code',
                'live_video_payments.coupon_amount',
                'live_video_payments.live_video_amount',
                'live_video_payments.is_coupon_applied',
                DB::raw('DATE_FORMAT(live_video_payments.created_at , "%e %b %y") as paid_at'))
                ->leftJoin('live_videos', 'live_videos.id', '=', 'live_video_id')
                ->where('live_video_viewer_id', $request->id)
                ->orderBy('live_video_payments.created_at', 'desc')
                ->skip($request->skip)
                ->take(Setting::get('admin_take_count' ,12))
                ->get();

                $response_array = ['success'=>true, 'data'=>$model];

        }

        return response()->json($response_array);
    
    }

    /**
     * Function Name : redeems()
     * 
     * List  of all my redeem based on logged in user id
     *
     * @param object $request - User id ,token
     *
     * @return redeem list wih boolean response
     */
    public function redeems(Request $request) {

        Log::info("redeems API");

        $currency = Setting::get('currency');

        $data = Redeem::where('user_id' , $request->id)
                ->select('total' , 'paid' , 'remaining' , 'status', DB::raw("'$currency' as currency"))
                ->get()->toArray();

        if(count($data) == 0) {

            $data['total'] = $data['paid'] = $data['remaining'] = "0";

            $data['status'] = 1;

            $data['currency'] = $currency;
        }

        $response_array = ['success' => true , 'data' => $data];

        return response()->json($response_array , 200);
    
    }

    /**
     * Function Name : redeem_request_list()
     * 
     * List of redeem requests based on logged in user id 
     *
     * @param object $request - User id ,token
     *
     * @return redeem list wih boolean response
     */
    public function redeem_request_list(Request $request) {

        
        Log::info("redeem_request_list");

        $currency = Setting::get('currency');

        $model = RedeemRequest::where('user_id' , $request->id)
                ->select('request_amount' , 
                     DB::raw("'$currency' as currency"),
                     DB::raw('DATE_FORMAT(created_at , "%e %b %y") as requested_date'),
                     'paid_amount',
                     DB::raw('DATE_FORMAT(updated_at , "%e %b %y") as paid_date'),
                     'status',
                     'id as redeem_request_id'
                 )
                ->orderBy('created_at', 'desc')
                ->get();
        
        $redeem_amount = Redeem::where('user_id' , $request->id)
                ->select('total' , 'paid' , 'remaining' , 'status', DB::raw("'$currency' as currency"))
                ->first();

        $data = [];

        foreach ($model as $key => $value) {
            
            $redeem_status = redeem_request_status($value->status);

            $redeem_cancel_status = in_array($value->status, [REDEEM_REQUEST_SENT , REDEEM_REQUEST_PROCESSING]) ? 1 : 0;
            
            $data[] = [
                    'redeem_request_id'=>$value->redeem_request_id,
                    'request_amount' => $value->request_amount,
                    'redeem_status'=>$redeem_status,
                    'currency'=>$value->currency,
                    'requested_date'=>$value->requested_date,
                    'paid_amount'=>$value->paid_amount,
                    'paid_date'=>$value->paid_amount > 0 ? $value->paid_date : '-',
                    'redeem_cancel_status'=>$redeem_cancel_status,
                    'status'=>$value->status
            ];

        }

        if (!$redeem_amount) {

            $redeem_amount = ['total'=> "0.00", 'paid'=>"0.00",'remaining'=>"0.00",'status'=>"0.00",'currency'=>$currency , 'status' => 1];

        } else {
            $redeem_amount->status = $redeem_amount->remaining > Setting::get('minimum_redeem') ? 1 : 0; 
        }

        $response_array = ['success' => true , 'data' => $data, 'redeem_amount'=>$redeem_amount];

        return response()->json($response_array , 200);
    
    }


    /** 
     * Function Name : send_redeem_request()
     *
     * to send redeem request to the user
     *
     * @param object $request - User id, token, 
     *
     * @return success /failure with boolean response
     */
    public function send_redeem_request(Request $request) {

        //  Get admin configured - Minimum Provider Credit

        $minimum_redeem = Setting::get('minimum_redeem' , 1);

        // Get Provider Remaining Credits 

        $redeem_details = Redeem::where('user_id' , $request->id)->first();

        if($redeem_details) {


            $remaining = $redeem_details->remaining;

            // check the provider have more than minimum credits

            if($remaining > $minimum_redeem) {

                $redeem_amount = abs($remaining - $minimum_redeem);

                // Check the redeems is not empty

                if($redeem_amount) {

                    // Save Redeem Request

                    $redeem_request = new RedeemRequest;

                    $redeem_request->user_id = $request->id;

                    $redeem_request->request_amount = $redeem_amount;

                    $redeem_request->status = false;

                    $redeem_request->save();

                    // Update Redeems details 

                    $redeem_details->remaining = abs($redeem_details->remaining-$redeem_amount);

                    $redeem_details->save();

                    $response_array = ['success' => true];

                } else {

                    $response_array = ['success' => false , 'error_messages' => Helper::error_message(159) , 'error_code' => 159];
                }

            } else {
                $response_array = ['success' => false , 'error_messages' => Helper::error_message(158) ,'error_code' => 158];
            }

        } else {
            $response_array = ['success' => false , 'error_messages' => Helper::error_message(161) , 'error_code' => 161];
        }


        return response()->json($response_array , 200);

    }

    /**
     * Function Name : redeem_request_cancel()
     *
     * To cancel the request before admin approves
     *
     * @param object $request - User id, token ,redeem request id
     *
     * @return success / failure message with boolean response
     */
    public function redeem_request_cancel(Request $request) {

        $validator = Validator::make($request->all() , [
            'redeem_request_id' => 'required|exists:redeem_requests,id,user_id,'.$request->id,
            ]);

         if ($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            $response_array = array('success' => false, 'error_code' => 101, 'error_messages'=>$error_messages);

        } else {

            if($redeem_details = Redeem::where('user_id' , $request->id)->first()) {

                if($redeem_request_details = RedeemRequest::find($request->redeem_request_id)) {

                    // Check status to cancel the redeem request

                    if(in_array($redeem_request_details->status, [REDEEM_REQUEST_SENT , REDEEM_REQUEST_PROCESSING])) {
                        // Update the redeeem 

                        $redeem_details->remaining = $redeem_details->remaining + abs($redeem_request_details->request_amount);

                        $redeem_details->save();

                        // Update the redeeem request Status

                        $redeem_request_details->status = REDEEM_REQUEST_CANCEL;

                        $redeem_request_details->save();

                        $response_array = ['success' => true];


                    } else {
                        $response_array = ['success' => false ,  'error_messages' => Helper::error_message(160) , 'error_code' => 160];
                    }

                } else {
                    $response_array = ['success' => false ,  'error_messages' => Helper::error_message(161) , 'error_code' => 161];
                }

            } else {

                $response_array = ['success' => false ,  'error_messages' => Helper::error_message(161) , 'error_code' =>161 ];
            }

        }

        return response()->json($response_array , 200);

    }


    /**
     * Function Name : plan_detail()
     *
     * Display plan detail based on plan id
     *
     * @param object $param - User id, token and plan id
     *
     * @return response of object
     */
    public function plan_detail(Request $request) {

        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:subscriptions,id',            
        ], array(
                'exists' => 'The :attribute doesn\'t exists',
            ));
        
        if ($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            $response_array = array('success' => false, 'error_messages'=>$error_messages , 'error_code' => 101);

            return response()->json($response_array);
        }
        
        $currency = Setting::get('currency');

        $model = Subscription::select('id as plan_id', 'title', 'description', 'plan', 'amount', 'status', 'popular_status', 'created_at', 'unique_id', DB::raw("'$currency' as currency"))->where('id',$request->plan_id)->first();

        if ($model) {

            return response()->json(['success'=>true, 'data'=>$model]);

        } else {
            
            return response()->json(['success'=>false, 'message'=>tr('subscription_not_found')]);
        }

    }

    /**
     * function Name : single_video
     *
     * To get live video single page
     *
     * @param object $request - Video Details
     *
     * @return video details
     */
    public function video_details(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'video_id'=>'required|exists:live_videos,id',
            ));

        if ($validator->fails()) {

            // Error messages added in response for debugging

            $errors = implode(',',$validator->messages()->all());

            $response_array = ['success' => false,'error_messages' => $errors,'error_code' => 101];

        } else {

            $currency = Setting::get('currency');

            $model = LiveVideo::select('title', 'description', 'amount', DB::raw("'$currency' as currency"), 'id as video_id')->where('id',$request->video_id)->first();

            if($model) {

                $request->request->add(['broadcast_type' => $model->broadcast_type, 'virtual_id' => $model->virtual_id, 'live_video_id' => $model->live_video_id]);

                $model->mobile_live_streaming_url = Helper::get_mobile_live_streaming_url($request);
            }

            $response_array = ['success' => true,'data' => $model];


        }

        return response()->json($response_array);

    }

    /**
     * Function Name : check_token_valid()
     *
     * To check the token is valid for the user or not
     * 
     * @param object $request - User id and token
     *
     * @return Object with success message
     */
    public function check_token_valid(Request $request) {

        if($request->id) {

            $user = User::find($request->id);

            $user->token_expiry = Helper::generate_token_expiry();

            $user->save();

        }

        return response()->json(['data'=>$request->all(), 'success'=>true]);

    }

   /**
     * Function Name : logout()
     *
     * Delete logged device while logout user
     *
     * @param interger $request - User Id
     *
     * @return boolean  succes/failure message
     */
    public function logout(Request $request) {

        try {

            DB::beginTransaction();

            $user = User::find($request->id);

            if ($user) {

        		$user->login_status = 0;

        		$user->save();

                $response_array = ['success'=>true];

            } else {

                throw new Exception(tr('user_not_found'));
                
            }

            DB::commit();

            return response()->json($response_array);

        } catch(Exception $e) {

            $message = $e->getMessage();

            $response_array = ['success'=>false, 'error_messages'=>$e];

            return response()->json($response_array);
        }

    }

    /**
     * Function Name : send_notification_to_followers() 
     *
     * we have email notification for users following and if followed user goes live
     *
     * @param object $request - User id and token
     *
     * @return response of json
     */
    public function send_notification_to_followers(Request $request) {

        $followers = Follower::select('user_id as id',
                            'users.name as name', 
                            'users.email as email', 
                            'users.picture',
                            'users.description' ,
                            'followers.follower as follower_id' ,
                            'followers.created_at as created_at'
                           )
                    ->leftJoin('users' , 'users.id' ,'=' , 'followers.follower')
                    ->where('user_id', $request->id)
                    ->orderBy('created_at', 'desc')
                    ->get();


        $user = User::find($request->id);

        // Save Notification

        $notification = NotificationTemplate::getRawContent(LIVE_STREAM_STARTED, $user);

        $content = $notification ? $notification : LIVE_STREAM_STARTED;

        foreach ($followers as $key => $value) {
            
            $email_data['name'] = $value->name;

            $email_data['streamer'] = $user->name;

            $email_data['subject'] = $user->name.' '.tr('new_video_streaming');

            $email_data['page'] = "emails.user.notification";

            $email_data['email'] = $value->email;

            $this->dispatch(new \App\Jobs\SendEmailJob($email_data));

            UserNotification::save_notification($value->follower_id, $content, $request->video_id,LIVE_STREAM_STARTED , $request->id);

        }

        return response()->json(true);

    }

    /**
     * Function Name ; streaming_status()
     *
     * Once streaming started changed the status into one
     *
     * @param object $param - User id, token & video_id
     *
     */
    public function streaming_status(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'video_id'=>'required|exists:live_videos,id',
            ));

        if ($validator->fails()) {

            // Error messages added in response for debugging

            $errors = implode(',',$validator->messages()->all());

            $response_array = ['success' => false,'error_messages' => $errors,'error_code' => 101];

        } else {

            $model = LiveVideo::where('id',$request->video_id)->first();

            $model->is_streaming = DEFAULT_TRUE;

            $model->save();

            $destination_ip = Setting::get('wowza_ip_address');

            if (Setting::get('kurento_socket_url')) {

                $destination_port = $model->port_no;

                File::isDirectory(public_path().'/uploads/sdp_files') or File::makeDirectory(public_path().'/uploads/sdp_files', 0777, true, true);

                if (!file_exists(public_path()."/uploads/sdp_files/".$model->user_id.'-'.$model->id.".sdp")) {

                    $myfile = fopen(public_path()."/uploads/sdp_files/".$model->user_id.'-'.$model->id.".sdp", "w") or die("Unable to open file!");

                    $data = "v=0\n"
                            ."o=- 0 0 IN IP4 " . $destination_ip . "\n"
                            . "s=Kurento\n"
                            . "c=IN IP4 " . $destination_ip . "\n"
                            . "t=0 0\n"
                            . "m=video " . $destination_port . " RTP/AVP 100\n"
                            . "a=rtpmap:100 H264/90000\n";

                    fwrite($myfile, $data);

                    fclose($myfile);

                    $filepath = public_path()."/uploads/sdp_files/".$model->user_id.'-'.$model->id.".sdp";

                    shell_exec("mv $filepath /usr/local/WowzaStreamingEngine/content/");

                    $this->connectStream($model->user_id.'-'.$model->id);

                }

            }


            $response_array = ['success' => true,'data' => $model];

        }

        return response()->json($response_array);

    }

    /**
     * Function Name : card_details()
     * 
     * List of card details based on logged in user id
     *
     * @param object $request - user id
     * 
     * @return list of cards
     */
    public function card_details(Request $request) {

        $cards = Card::select('user_id as id','id as card_id','customer_id',
                'last_four', 'card_token', 'is_default', 
            \DB::raw('DATE_FORMAT(created_at , "%e %b %y") as created_date'))
            ->where('user_id', $request->id)->get();

        $cards = (!empty($cards) && $cards != null) ? $cards : [];

        $response_array = ['success'=>true, 'data'=>$cards];

        return response()->json($response_array, 200);
    }

    /**
     * Function Name : cards_add()
     * 
     * @uses Add Payment card based on logged in user id
     *
     * @created Vidhya R 
     *
     * @updated Vidhya R 
     *
     * @param object $request - user id
     * 
     * @return card details objet
     */
    public function cards_add(Request $request) {

        if(Setting::get('stripe_secret_key')) {

            \Stripe\Stripe::setApiKey(Setting::get('stripe_secret_key'));

        } else {

            throw new Exception(tr('add_card_is_not_enabled'), 101);
        }

        DB::beginTransaction();

        try {

            $validator = Validator::make($request->all(), array(
                    'number' => 'numeric',
                    'card_token'=>'required',
                )
                );

            if($validator->fails()) {

                $error_messages = implode(',', $validator->messages()->all());
                
                throw new Exception($error_messages , 101);

            } else {
                Log::info("INSIDE CARDS ADD");

                $user_details = User::find($request->id);

                if(!$user_details) {

                    throw new Exception(Helper::error_message(505), 505);  

                }

                // Get the key from settings table
                    
                $customer = \Stripe\Customer::create([
                        "card" => $request->card_token,
                        "email" => $user_details->email,
                        "description" => "Customer for ".Setting::get('site_name'),
                    ]);

                  

                if($customer) {

                    $customer_id = $customer->id;

                    $card_details = new Card;
                    $card_details->user_id = $user_details->id;
                    $card_details->customer_id = $customer_id;
                    $card_details->card_token = $customer->sources->data ? $customer->sources->data[0]->id : "";
                    $card_details->card_type = $customer->sources->data ? $customer->sources->data[0]->brand : "";
                    $card_details->last_four = $customer->sources->data[0]->last4 ? $customer->sources->data[0]->last4 : "";

                    $card_details->card_holder_name = $request->card_holder_name ?: $user_details->name;

                    // Check is any default is available

                    $check_card_details = Card::where('user_id', $request->id)->count();

                    $card_details->is_default = $check_card_details ? 0 : 1;
                    
                    $card_details->save();

                    if($user_details && $card_details->is_default) {

                        $user_details->payment_mode = 'card';

                        $user_details->card_id = $card_details->id;

                        $user_details->save();
                    }

                    DB::commit();

                    $data = [
                            'user_id'=>$request->id, 
                            'id'=>$request->id, 
                            'token'=>$user_details->token,
                            'card_id'=>$card_details->id,
                            'customer_id'=>$card_details->customer_id,
                            'last_four'=>$card_details->last_four, 
                            'card_token'=>$card_details->card_token, 
                            'is_default'=>$card_details->is_default
                            ];

                    $response_array = ['success' => true, 'message'=> tr('add_card_success'), 
                        'data'=> $data];


                    return response()->json($response_array);

                } else {

                    throw new Exception( Helper::error_message(174) , 174);
                    
                }

            }

            DB::commit();

            return response()->json($response_array , 200);

        } catch(Stripe_CardError $e) {

            Log::info("error1");

            $error1 = $e->getMessage();

            $response_array = array('success' => false , 'error_messages' => $error1 ,'error_code' => 903);

            return response()->json($response_array , 200);

        } catch (Stripe_InvalidRequestError $e) {

            // Invalid parameters were supplied to Stripe's API

            Log::info("error2");

            $error2 = $e->getMessage();

            $response_array = array('success' => false , 'error_messages' => $error2 ,'error_code' => 903);

            return response()->json($response_array , 200);

        } catch (Stripe_AuthenticationError $e) {

            Log::info("error3");

            // Authentication with Stripe's API failed
            $error3 = $e->getMessage();

            $response_array = array('success' => false , 'error_messages' => $error3 ,'error_code' => 903);

            return response()->json($response_array , 200);

        } catch (Stripe_ApiConnectionError $e) {

            Log::info("error4");

            // Network communication with Stripe failed
            $error4 = $e->getMessage();

            $response_array = array('success' => false , 'error_messages' => $error4 ,'error_code' => 903);

            return response()->json($response_array , 200);

        } catch (Stripe_Error $e) {

            Log::info("error5");

            // Display a very generic error to the user, and maybe send
            // yourself an email
            $error5 = $e->getMessage();

            $response_array = array('success' => false , 'error_messages' => $error5 ,'error_code' => 903);

            return response()->json($response_array , 200);

        } catch (\Stripe\StripeInvalidRequestError $e) {

            Log::info("error7");

            // Log::info(print_r($e,true));

            $response_array = array('success' => false , 'error_messages' => Helper::get_error_message(903) ,'error_code' => 903);

            return response()->json($response_array , 200);

        } catch(Exception $e) {

            DB::rollback();

            $error_message = $e->getMessage();

            $error_code = $e->getCode() ?: 101;

            Log::info("catch FUNCTION INSIDE".$error_message);

            $response_array = ['success'=>false, 'error_messages'=> $error_message , 'error_code' => $error_code];

            return response()->json($response_array , 200);
        
        }

    }    

    /**
     * Function Name : default_card()
     * 
     * Change the card as default card
     *
     * @param object $request - user id, card id
     * 
     * @return card details object
     */
    public function default_card(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'card_id' => 'required|integer|exists:cards,id,user_id,'.$request->id,
            ),
            array(
                'exists' => 'The :attribute doesn\'t belong to user:'.$request->id
            )
        );

        if($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            $response_array = array('success' => false, 'error_messages' => $error_messages, 'error_code' => 101);

        } else {

            $user = User::find($request->id);
            
            $old_default = Card::where('user_id' , $request->id)->where('is_default', DEFAULT_TRUE)->update(array('is_default' => DEFAULT_FALSE));

            $card = Card::where('id' , $request->card_id)->update(array('is_default' => DEFAULT_TRUE));

            if($card) {

                if($user) {

                    $user->card_id = $request->card_id;

                    $user->save();
                }

                $response_array = Helper::null_safe(array('success' => true, 'data'=>['id'=>$request->id,'token'=>$user->token]));

            } else {

                $response_array = array('success' => false , 'error_messages' => tr('something_error'));

            }
        }
        return response()->json($response_array , 200);
    
    }

    /**
     * Function Name : delete_card()
     * 
     * Delete the card who has logged in (Based on User Id, Card Id)
     *
     * @param object $request - user id, card id
     * 
     * @return success/failure message
     */
    public function delete_card(Request $request) {
    
        $card_id = $request->card_id;

        $validator = Validator::make(
            $request->all(),
            array(
                'card_id' => 'required|integer|exists:cards,id,user_id,'.$request->id,
            ),
            array(
                'exists' => 'The :attribute doesn\'t belong to user:'.$request->id
            )
        );

        if ($validator->fails()) {
            
            $error_messages = implode(',', $validator->messages()->all());
            
            $response_array = array('success' => false , 'error_messages' => $error_messages , 'error_code' => 101);
        
        } else {

            $user = User::find($request->id);


            if ($user->card_id == $card_id) {

                $response_array = array('success' => false, 'error_messages'=> tr('card_default_error'));

            } else {

                Card::where('id',$card_id)->delete();

                if($user) {

                    $cards = Card::where('user_id' , $request->id)->count();

                    if ($cards > 1) {


                    } else {

                        if($check_card = Card::where('user_id' , $request->id)->first()) {

                            $check_card->is_default =  DEFAULT_TRUE;

                            $user->card_id = $check_card->id;

                            $check_card->save();

                        } else { 

                            $user->payment_mode = COD;

                            $user->card_id = DEFAULT_FALSE;
                        }

                    }

                    $user->save();
                }

                $response_array = array('success' => true, 
                        'message'=>tr('card_deleted'), 
                        'data'=> ['id'=>$request->id,'token'=>$user->token, 'position'=>$request->position]);

            }
            
        }
    
        return response()->json($response_array , 200);
    
    }

    /**
     * Function Name : stripe_payment()
     * 
     * User pay the subscription plan amount through stripe payment
     *
     * @param object $request - User id, Subscription id
     * 
     * @return response of success/failure message
     */
    public function stripe_payment(Request $request) {

        try {

            DB::beginTransaction();

            $validator = Validator::make($request->all(), 
                array(
                    'subscription_id' => 'required|exists:subscriptions,id',
                    'coupon_code'=>'exists:coupons,coupon_code',
                ), array(
                    'coupon_code.exists' => tr('coupon_code_not_exists'),
                    'subscription_id.exists' => tr('subscription_not_exists'),
            ));

            if($validator->fails()) {

                $error_messages = implode(',', $validator->messages()->all());

                throw new Exception($error_messages, 101);

            } else {

                $subscription = Subscription::find($request->subscription_id);

                $user = User::find($request->id);

                if ($subscription) {

                    $total = $subscription->amount;

                    $coupon_amount = 0;

                    $coupon_reason = '';

                    $is_coupon_applied = COUPON_NOT_APPLIED;

                    if ($request->coupon_code) {

                        $coupon = Coupon::where('coupon_code', $request->coupon_code)->first();

                        if ($coupon) {
                            
                            if ($coupon->status == COUPON_INACTIVE) {

                                $coupon_reason = tr('coupon_inactive_reason');

                            } else {

                                $check_coupon = $this->check_coupon_applicable_to_user($user, $coupon)->getData();

                                if ($check_coupon->success) {

                                    $is_coupon_applied = COUPON_APPLIED;

                                    $amount_convertion = $coupon->amount;

                                    if ($coupon->amount_type == PERCENTAGE) {

                                        $amount_convertion = round(amount_convertion($coupon->amount, $subscription->amount), 2);

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

                            $coupon_reason = tr('coupon_delete_reason');
                        }
                    }

                    if ($user) {

                        $check_card_exists = User::where('users.id' , $request->id)
                                        ->leftJoin('cards' , 'users.id','=','cards.user_id')
                                        ->where('cards.id' , $user->card_id)
                                        ->where('cards.is_default' , DEFAULT_TRUE);

                        if($check_card_exists->count() != 0) {

                            $user_card = $check_card_exists->first();

                            if ($total <= 0) {

                                
                                $previous_payment = UserSubscription::where('user_id' , $request->id)
                                            ->where('status', DEFAULT_TRUE)->orderBy('created_at', 'desc')->first();


                                $user_payment = new UserSubscription;

                                if($previous_payment) {

                                    if (strtotime($previous_payment->expiry_date) >= strtotime(date('Y-m-d H:i:s'))) {

                                     $user_payment->expiry_date = date('Y-m-d H:i:s', strtotime("+{$subscription->plan} months", strtotime($previous_payment->expiry_date)));

                                    } else {

                                        $user_payment->expiry_date = date('Y-m-d H:i:s',strtotime("+{$subscription->plan} months"));

                                    }


                                } else {
                                   
                                    $user_payment->expiry_date = date('Y-m-d H:i:s',strtotime("+".$subscription->plan." months"));
                                }


                                $user_payment->payment_id = "free plan";

                                $user_payment->user_id = $request->id;

                                $user_payment->subscription_id = $request->subscription_id;

                                $user_payment->status = 1;

                                $user_payment->amount = $total;

                                $user_payment->payment_mode = CARD;

                                // Coupon details

                                $user_payment->is_coupon_applied = $is_coupon_applied;

                                $user_payment->coupon_code = $request->coupon_code  ? $request->coupon_code  :'';

                                $user_payment->coupon_amount = $coupon_amount;

                                $user_payment->subscription_amount = $subscription->amount;

                                $user_payment->amount = $total;

                                $user_payment->coupon_reason = $is_coupon_applied == COUPON_APPLIED ? '' : $coupon_reason;


                                if ($user_payment->save()) {

                                
                                    if ($user) {

                                        $user->user_type = 1;

                                        $user->amount_paid += $total;

                                        $user->expiry_date = $user_payment->expiry_date;

                                        $now = time(); // or your date as well

                                        $end_date = strtotime($user->expiry_date);

                                        $datediff =  $end_date - $now;

                                        $user->no_of_days = ($user->expiry_date) ? floor($datediff / (60 * 60 * 24)) + 1 : 0;

                                        if ($user_payment->amount <= 0) {

                                            $user->one_time_subscription = 1;
                                        }

                                        if ($user->save()) {

                                             $data = ['id' => $user->id , 'token' => $user->token, 'no_of_account'=>$subscription->no_of_account , 'payment_id' => $user_payment->payment_id];

                                            $response_array = ['success' => true, 'message'=>tr('payment_success') , 'data' => $data];

                                        } else {


                                            throw new Exception(tr('user_details_not_saved'));
                                            
                                        }

                                    } else {

                                        throw new Exception(tr('user_not_found'));
                                        
                                    }
                                    
                                   
                                } else {

                                    throw new Exception(tr(Helper::error_message(902)), 902);

                                }


                            } else {

                                $stripe_secret_key = Setting::get('stripe_secret_key');

                                $customer_id = $user_card->customer_id;

                                if($stripe_secret_key) {

                                    \Stripe\Stripe::setApiKey($stripe_secret_key);

                                } else {

                                    throw new Exception(Helper::error_message(902), 902);

                                }

                                try{

                                   $user_charge =  \Stripe\Charge::create(array(
                                      "amount" => $total * 100,
                                      "currency" => "usd",
                                      "customer" => $customer_id,
                                    ));

                                   $payment_id = $user_charge->id;
                                   $amount = $user_charge->amount/100;
                                   $paid_status = $user_charge->paid;

                                    if($paid_status) {

                                        $previous_payment = UserSubscription::where('user_id' , $request->id)
                                            ->where('status', DEFAULT_TRUE)->orderBy('created_at', 'desc')->first();

                                        $user_payment = new UserSubscription;

                                        if($previous_payment) {

                                            $expiry_date = $previous_payment->expiry_date;
                                            $user_payment->expiry_date = date('Y-m-d H:i:s', strtotime($expiry_date. "+".$subscription->plan." months"));

                                        } else {
                                            
                                            $user_payment->expiry_date = date('Y-m-d H:i:s',strtotime("+".$subscription->plan." months"));
                                        }


                                        $user_payment->payment_id  = $payment_id;

                                        $user_payment->user_id = $request->id;

                                        $user_payment->subscription_id = $request->subscription_id;

                                        $user_payment->status = PAID_STATUS;

                                        $user_payment->payment_mode = CARD;


                                        // Coupon details

                                        $user_payment->is_coupon_applied = $is_coupon_applied;

                                        $user_payment->coupon_code = $request->coupon_code  ? $request->coupon_code  :'';

                                        $user_payment->coupon_amount = $coupon_amount;

                                        $user_payment->subscription_amount = $subscription->amount;

                                        $user_payment->amount = $total;

                                        $user_payment->coupon_reason = $is_coupon_applied == COUPON_APPLIED ? '' : $coupon_reason;


                                        if ($user_payment->save()) {

                                            if ($user) {

                                                $user->user_type = SUBSCRIBED_USER;

                                                $user->amount_paid += $total;

                                                $user->expiry_date = $user_payment->expiry_date;

                                                $now = time(); // or your date as well

                                                $end_date = strtotime($user->expiry_date);

                                                $datediff =  $end_date - $now;

                                                $user->no_of_days = ($user->expiry_date) ? floor($datediff / (60 * 60 * 24)) + 1 : 0;

                                                if ($user_payment->amount <= 0) {

                                                    $user->one_time_subscription = 1;
                                                }

                                                if ($user->save()) {

                                                     $data = ['id' => $user->id , 'token' => $user->token, 'no_of_account'=>$subscription->no_of_account , 'payment_id' => $user_payment->payment_id];

                                                    $response_array = ['success' => true, 'message'=>tr('payment_success') , 'data' => $data];

                                                } else {


                                                    throw new Exception(tr('user_details_not_saved'));
                                                    
                                                }

                                            } else {

                                                throw new Exception(tr('user_not_found'));
                                                
                                            }

                                        

                                        } else {

                                             throw new Exception(tr(Helper::error_message(902)), 902);

                                        }


                                    } else {

                                        $response_array = array('success' => false, 'error_messages' => Helper::error_message(903) , 'error_code' => 903);

                                        throw new Exception(Helper::error_message(903), 903);

                                    }

                                
                                } catch(\Stripe\Error\RateLimit $e) {

                                    throw new Exception($e->getMessage(), 903);

                                } catch(\Stripe\Error\Card $e) {

                                    throw new Exception($e->getMessage(), 903);

                                } catch (\Stripe\Error\InvalidRequest $e) {
                                    // Invalid parameters were supplied to Stripe's API
                                   
                                    throw new Exception($e->getMessage(), 903);

                                } catch (\Stripe\Error\Authentication $e) {

                                    // Authentication with Stripe's API failed

                                    throw new Exception($e->getMessage(), 903);

                                } catch (\Stripe\Error\ApiConnection $e) {

                                    // Network communication with Stripe failed

                                    throw new Exception($e->getMessage(), 903);

                                } catch (\Stripe\Error\Base $e) {
                                  // Display a very generic error to the user, and maybe send
                                    
                                    throw new Exception($e->getMessage(), 903);

                                } catch (Exception $e) {
                                    // Something else happened, completely unrelated to Stripe

                                    throw new Exception($e->getMessage(), 903);

                                } catch (\Stripe\StripeInvalidRequestError $e) {

                                        Log::info(print_r($e,true));

                                    throw new Exception($e->getMessage(), 903);
                                    
                                
                                }


                            }

                        } else {
     
                            throw new Exception(Helper::error_message(901), 901);
                            
                        }

                    } else {

                        throw new Exception(tr('no_user_detail_found'));
                        
                    }

                } else {

                    throw new Exception(Helper::error_message(901), 901);

                }         

                
            }

            DB::commit();

            return response()->json($response_array , 200);

        } catch (Exception $e) {

            DB::rollback();

            $error = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages'=>$error, 'error_code'=>$code];

            return response()->json($response_array);
        }
    
    }

    /**
     * Function Name : stripe_live_ppv()
     * 
     * Pay the payment for Pay per view through stripe
     *
     * @param object $request - Admin video id
     * 
     * @return response of success/failure message
     */
    public function stripe_live_ppv(Request $request) {

        try {

            DB::beginTransaction();

            $validator = Validator::make($request->all(), 
                array(
                    'video_id' => 'required|exists:live_videos,id,status,'.VIDEO_STREAMING_ONGOING,
                    'coupon_code'=>'exists:coupons,coupon_code,status,'.COUPON_ACTIVE,
                  //  'total_amount'=>'numeric',
                ), array(
                    'coupon_code.exists' => tr('coupon_code_not_exists'),
                    'video_id.exists' => tr('livevideo_not_exists'),
            ));

            if($validator->fails()) {

                $errors = implode(',', $validator->messages()->all());
                
                $response_array = ['success' => false, 'error_messages' => $errors, 'error_code' => 101];

                throw new Exception($errors);

            } else {

                $userModel = User::find($request->id);

                if ($userModel) {

                    if ($userModel->card_id) {

                        $user_card = Card::find($userModel->card_id);

                        if ($user_card && $user_card->is_default) {

                            $video = LiveVideo::find($request->video_id);

                            if($video) {

                                $total = $video->amount;

                                $coupon_amount = 0;

                                $coupon_reason = '';

                                $is_coupon_applied = COUPON_NOT_APPLIED;

                                if ($request->coupon_code) {

                                    $coupon = Coupon::where('coupon_code', $request->coupon_code)->first();

                                    if ($coupon) {
                                        
                                        if ($coupon->status == COUPON_INACTIVE) {

                                            $coupon_reason = tr('coupon_inactive_reason');

                                        } else {

                                            $check_coupon = $this->check_coupon_applicable_to_user($userModel, $coupon)->getData();

                                            if ($check_coupon->success) {

                                                $is_coupon_applied = COUPON_APPLIED;

                                                $amount_convertion = $coupon->amount;

                                                if ($coupon->amount_type == PERCENTAGE) {

                                                    $amount_convertion = round(amount_convertion($coupon->amount, $video->amount), 2);

                                                }


                                                if ($amount_convertion < $video->amount) {

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

                                if ($total <= 0) {

                                    $user_payment = new LiveVideoPayment;
                                    $user_payment->payment_id = $is_coupon_applied ? 'COUPON-DISCOUNT' : FREE_PLAN;
                                    $user_payment->user_id = $video->user_id;
                                    $user_payment->live_video_viewer_id = $request->id;
                                    $user_payment->live_video_id = $request->video_id;
                                    $user_payment->status = PAID_STATUS;
                                  
                                    $user_payment->admin_amount = 0;

                                    $user_payment->user_amount = 0;

                                    $user_payment->payment_mode = CARD;

                                    $user_payment->currency = Setting::get('currency');

                                    // Coupon details

                                    $user_payment->is_coupon_applied = $is_coupon_applied;

                                    $user_payment->coupon_code = $request->coupon_code ? $request->coupon_code : '';

                                    $user_payment->coupon_amount = $coupon_amount;

                                    $user_payment->live_video_amount = $video->amount;

                                    $user_payment->amount = $total;

                                    $user_payment->coupon_reason = $is_coupon_applied == COUPON_APPLIED ? '' : $coupon_reason;

                                    $user_payment->save();


                                    $data = ['id'=> $request->id, 'token'=> $userModel->token , 'payment_id' => $user_payment->payment_id];

                                    $response_array = array('success' => true, 'message'=>tr('payment_success'),'data'=> $data);

                                } else {

                                    // Get the key from settings table

                                    $stripe_secret_key = Setting::get('stripe_secret_key');

                                    $customer_id = $user_card->customer_id;
                                    
                                    if($stripe_secret_key) {

                                        \Stripe\Stripe::setApiKey($stripe_secret_key);

                                    } else {

                                        $response_array = array('success' => false, 'error_messages' => Helper::error_message(902) , 'error_code' => 902);

                                        throw new Exception(Helper::error_message(902));
                                        
                                    }

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

                                            $user_payment = new LiveVideoPayment;
                                            $user_payment->payment_id  = $payment_id;
                                            $user_payment->user_id = $video->user_id;
                                            $user_payment->live_video_viewer_id = $request->id;
                                            $user_payment->live_video_id = $request->video_id;
                                            $user_payment->status = PAID_STATUS;
                                            $user_payment->payment_mode = CARD;

                                            $user_payment->currency = Setting::get('currency');

                                             // Coupon details

                                            $user_payment->is_coupon_applied = $is_coupon_applied;

                                            $user_payment->coupon_code = $request->coupon_code ? $request->coupon_code : '';

                                            $user_payment->coupon_amount = $coupon_amount;

                                            $user_payment->live_video_amount = $video->amount;

                                            $user_payment->amount = $total;

                                            $user_payment->coupon_reason = $is_coupon_applied == COUPON_APPLIED ? '' : $coupon_reason;


                                            // Commission Spilit 

                                            $admin_commission = Setting::get('admin_commission')/100;

                                            $admin_amount = $total * $admin_commission;

                                            $user_amount = $total - $admin_amount;

                                            $user_payment->admin_amount = $admin_amount;

                                            $user_payment->user_amount = $user_amount;

                                            $user_payment->save();

                                            // Commission Spilit Completed

                                            if($user = User::find($user_payment->user_id)) {

                                                $user->total_admin_amount = $user->total_admin_amount + $admin_amount;

                                                $user->total_user_amount = $user->total_user_amount + $user_amount;

                                                $user->remaining_amount = $user->remaining_amount + $user_amount;

                                                $user->total = $user->total + $total;

                                                $user->save();

                                                add_to_redeem($user->id , $user_amount);
                                            
                                            }

                                            $data = ['id'=> $request->id, 'token'=> $userModel->token , 'payment_id' => $payment_id];

                                            $response_array = array('success' => true, 'message'=>tr('payment_success'),'data'=> $data);

                                        } else {

                                            $response_array = array('success' => false, 'error_messages' => Helper::error_message(902) , 'error_code' => 902);

                                            throw new Exception(tr('no_vod_video_found'));

                                        }
                                    
                                    } catch(\Stripe\Error\RateLimit $e) {

                                        throw new Exception($e->getMessage(), 903);

                                    } catch(\Stripe\Error\Card $e) {

                                        throw new Exception($e->getMessage(), 903);

                                    } catch (\Stripe\Error\InvalidRequest $e) {
                                        // Invalid parameters were supplied to Stripe's API
                                       
                                        throw new Exception($e->getMessage(), 903);

                                    } catch (\Stripe\Error\Authentication $e) {

                                        // Authentication with Stripe's API failed

                                        throw new Exception($e->getMessage(), 903);

                                    } catch (\Stripe\Error\ApiConnection $e) {

                                        // Network communication with Stripe failed

                                        throw new Exception($e->getMessage(), 903);

                                    } catch (\Stripe\Error\Base $e) {
                                      // Display a very generic error to the user, and maybe send
                                        
                                        throw new Exception($e->getMessage(), 903);

                                    } catch (Exception $e) {
                                        // Something else happened, completely unrelated to Stripe

                                        throw new Exception($e->getMessage(), 903);

                                    } catch (\Stripe\StripeInvalidRequestError $e) {

                                            Log::info(print_r($e,true));

                                        throw new Exception($e->getMessage(), 903);
                                        
                                    
                                    }

                                }

                            
                            } else {

                                $response_array = array('success' => false , 'error_messages' => tr('no_vod_video_found'));

                                throw new Exception(tr('no_vod_video_found'));
                                
                            }

                        } else {

                            throw new Exception(tr('no_default_card_available'), 901);

                        }

                    } else {

                        throw new Exception(tr('no_default_card_available'), 901);

                    }

                } else {

                    throw new Exception(tr('no_user_detail_found'));
                    

                }

            }

            DB::commit();

            return response()->json($response_array,200);

        } catch (Exception $e) {

            DB::rollback();

            $message = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages'=>$message, 'error_code'=>$code];

            return response()->json($response_array);

        }
        
    }

    /**
     * Function Name : subscriptions_payment_apple_pay()
     * 
     * User pay the subscription plan amount through stripe payment
     *
     * @param object $request - User id, Subscription id
     * 
     * @return response of success/failure message
     */
    public function subscriptions_payment_apple_pay(Request $request) {

        try {

            DB::beginTransaction();

            $validator = Validator::make($request->all(), 
                array(
                    'subscription_id' => 'required|exists:subscriptions,id',
                    'coupon_code'=>'exists:coupons,coupon_code',
                    'payment_mode' => 'required|in:'.APPLE_PAY,
                    'token_id' => 'required'
                ), array(
                    'coupon_code.exists' => tr('coupon_code_not_exists'),
                    'subscription_id.exists' => tr('subscription_not_exists'),
            ));

            if($validator->fails()) {

                $error_messages = implode(',', $validator->messages()->all());

                throw new Exception($error_messages, 101);

            }

            $subscription = Subscription::find($request->subscription_id);

            if(!$subscription) {

                throw new Exception(Helper::error_message(901), 901);
                    
            }

            $user = User::find($request->id);

            if(!$user) {

                throw new Exception(tr('no_user_detail_found'));
                    
            }

            $total = $subscription->amount;

            $coupon_amount = 0;

            $coupon_reason = '';

            $is_coupon_applied = COUPON_NOT_APPLIED;

            if ($request->coupon_code) {

                $coupon = Coupon::where('coupon_code', $request->coupon_code)->first();

                if ($coupon) {
                    
                    if ($coupon->status == COUPON_INACTIVE) {

                        $coupon_reason = tr('coupon_inactive_reason');

                    } else {

                        $check_coupon = $this->check_coupon_applicable_to_user($user, $coupon)->getData();

                        if ($check_coupon->success) {

                            $is_coupon_applied = COUPON_APPLIED;

                            $amount_convertion = $coupon->amount;

                            if ($coupon->amount_type == PERCENTAGE) {

                                $amount_convertion = round(amount_convertion($coupon->amount, $subscription->amount), 2);

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

                    $coupon_reason = tr('coupon_delete_reason');
                }
            }

            if ($total <= 0) {

                
                $previous_payment = UserSubscription::where('user_id' , $request->id)
                            ->where('status', DEFAULT_TRUE)->orderBy('created_at', 'desc')->first();


                $user_payment = new UserSubscription;

                if($previous_payment) {

                    if (strtotime($previous_payment->expiry_date) >= strtotime(date('Y-m-d H:i:s'))) {

                     $user_payment->expiry_date = date('Y-m-d H:i:s', strtotime("+{$subscription->plan} months", strtotime($previous_payment->expiry_date)));

                    } else {

                        $user_payment->expiry_date = date('Y-m-d H:i:s',strtotime("+{$subscription->plan} months"));

                    }


                } else {
                   
                    $user_payment->expiry_date = date('Y-m-d H:i:s',strtotime("+".$subscription->plan." months"));
                }


                $user_payment->payment_id = "free plan";

                $user_payment->user_id = $request->id;

                $user_payment->subscription_id = $request->subscription_id;

                $user_payment->status = 1;

                $user_payment->amount = $total;

                $user_payment->payment_mode = APPLE_PAY;

                // Coupon details

                $user_payment->is_coupon_applied = $is_coupon_applied;

                $user_payment->coupon_code = $request->coupon_code  ? $request->coupon_code  :'';

                $user_payment->coupon_amount = $coupon_amount;

                $user_payment->subscription_amount = $subscription->amount;

                $user_payment->amount = $total;

                $user_payment->coupon_reason = $is_coupon_applied == COUPON_APPLIED ? '' : $coupon_reason;


                if ($user_payment->save()) {

                
                    if ($user) {

                        $user->user_type = 1;

                        $user->amount_paid += $total;

                        $user->expiry_date = $user_payment->expiry_date;

                        $now = time(); // or your date as well

                        $end_date = strtotime($user->expiry_date);

                        $datediff =  $end_date - $now;

                        $user->no_of_days = ($user->expiry_date) ? floor($datediff / (60 * 60 * 24)) + 1 : 0;

                        if ($user_payment->amount <= 0) {

                            $user->one_time_subscription = 1;
                        }

                        if ($user->save()) {

                             $data = ['id' => $user->id , 'token' => $user->token, 'no_of_account'=>$subscription->no_of_account , 'payment_id' => $user_payment->payment_id];

                            $response_array = ['success' => true, 'message'=>tr('payment_success') , 'data' => $data];

                        } else {


                            throw new Exception(tr('user_details_not_saved'));
                            
                        }

                    } else {

                        throw new Exception(tr('user_not_found'));
                        
                    }
                    
                   
                } else {

                    throw new Exception(tr(Helper::error_message(902)), 902);

                }


            } else {

                $stripe_secret_key = Setting::get('stripe_secret_key');

                if($stripe_secret_key) {

                    \Stripe\Stripe::setApiKey($stripe_secret_key);

                } else {

                    throw new Exception(Helper::error_message(902), 902);

                }

                try{

                   $user_charge =  \Stripe\Charge::create(array(
                      "amount" => $total * 100,
                      "currency" => "usd",
                      "source" => $request->token_id,
                    ));

                   $payment_id = $user_charge->id;
                   $amount = $user_charge->amount/100;
                   $paid_status = $user_charge->paid;

                    if($paid_status) {

                        $previous_payment = UserSubscription::where('user_id' , $request->id)
                            ->where('status', DEFAULT_TRUE)->orderBy('created_at', 'desc')->first();

                        $user_payment = new UserSubscription;

                        if($previous_payment) {

                            $expiry_date = $previous_payment->expiry_date;
                            $user_payment->expiry_date = date('Y-m-d H:i:s', strtotime($expiry_date. "+".$subscription->plan." months"));

                        } else {
                            
                            $user_payment->expiry_date = date('Y-m-d H:i:s',strtotime("+".$subscription->plan." months"));
                        }


                        $user_payment->payment_id  = $payment_id;

                        $user_payment->user_id = $request->id;

                        $user_payment->subscription_id = $request->subscription_id;

                        $user_payment->status = PAID_STATUS;

                        $user_payment->payment_mode = APPLE_PAY;


                        // Coupon details

                        $user_payment->is_coupon_applied = $is_coupon_applied;

                        $user_payment->coupon_code = $request->coupon_code  ? $request->coupon_code  :'';

                        $user_payment->coupon_amount = $coupon_amount;

                        $user_payment->subscription_amount = $subscription->amount;

                        $user_payment->amount = $total;

                        $user_payment->coupon_reason = $is_coupon_applied == COUPON_APPLIED ? '' : $coupon_reason;


                        if ($user_payment->save()) {

                            if ($user) {

                                $user->user_type = SUBSCRIBED_USER;

                                $user->amount_paid += $total;

                                $user->expiry_date = $user_payment->expiry_date;

                                $now = time(); // or your date as well

                                $end_date = strtotime($user->expiry_date);

                                $datediff =  $end_date - $now;

                                $user->no_of_days = ($user->expiry_date) ? floor($datediff / (60 * 60 * 24)) + 1 : 0;

                                if ($user_payment->amount <= 0) {

                                    $user->one_time_subscription = 1;
                                }

                                if ($user->save()) {

                                     $data = ['id' => $user->id , 'token' => $user->token, 'no_of_account'=>$subscription->no_of_account , 'payment_id' => $user_payment->payment_id];

                                    $response_array = ['success' => true, 'message'=>tr('payment_success') , 'data' => $data];

                                } else {


                                    throw new Exception(tr('user_details_not_saved'));
                                    
                                }

                            } else {

                                throw new Exception(tr('user_not_found'));
                                
                            }

                        

                        } else {

                             throw new Exception(tr(Helper::error_message(902)), 902);

                        }


                    } else {

                        $response_array = array('success' => false, 'error_messages' => Helper::error_message(903) , 'error_code' => 903);

                        throw new Exception(Helper::error_message(903), 903);

                    }

                
                } catch(\Stripe\Error\RateLimit $e) {

                    throw new Exception($e->getMessage(), 903);

                } catch(\Stripe\Error\Card $e) {

                    throw new Exception($e->getMessage(), 903);

                } catch (\Stripe\Error\InvalidRequest $e) {
                    // Invalid parameters were supplied to Stripe's API
                   
                    throw new Exception($e->getMessage(), 903);

                } catch (\Stripe\Error\Authentication $e) {

                    // Authentication with Stripe's API failed

                    throw new Exception($e->getMessage(), 903);

                } catch (\Stripe\Error\ApiConnection $e) {

                    // Network communication with Stripe failed

                    throw new Exception($e->getMessage(), 903);

                } catch (\Stripe\Error\Base $e) {
                  // Display a very generic error to the user, and maybe send
                    
                    throw new Exception($e->getMessage(), 903);

                } catch (Exception $e) {
                    // Something else happened, completely unrelated to Stripe

                    throw new Exception($e->getMessage(), 903);

                } catch (\Stripe\StripeInvalidRequestError $e) {

                        Log::info(print_r($e,true));

                    throw new Exception($e->getMessage(), 903);
                    
                
                }


            }
                        
            DB::commit();

            return response()->json($response_array , 200);

        } catch (Exception $e) {

            DB::rollback();

            $error = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages'=>$error, 'error_code'=>$code];

            return response()->json($response_array);
        }
    
    }

    /**
     * Function Name : ppv_payment_apple_pay()
     * 
     * Pay the payment for Pay per view through stripe
     *
     * @param object $request - Admin video id
     * 
     * @return response of success/failure message
     */
    public function ppv_payment_apple_pay(Request $request) {

        try {

            DB::beginTransaction();

            $validator = Validator::make($request->all(), 
                array(
                    'video_id' => 'required|exists:live_videos,id,status,'.VIDEO_STREAMING_ONGOING,
                    'coupon_code'=>'exists:coupons,coupon_code,status,'.COUPON_ACTIVE,
                    'payment_mode' => 'required|in:'.APPLE_PAY,
                    'token_id' => 'required'
                  //  'total_amount'=>'numeric',
                ), array(
                    'coupon_code.exists' => tr('coupon_code_not_exists'),
                    'video_id.exists' => tr('livevideo_not_exists'),
            ));

            if($validator->fails()) {

                $errors = implode(',', $validator->messages()->all());
                
                $response_array = ['success' => false, 'error_messages' => $errors, 'error_code' => 101];

                throw new Exception($errors);

            }

            $userModel = User::find($request->id);

            if ($userModel) {

                throw new Exception(tr('no_user_detail_found'));

            }

            $video = LiveVideo::find($request->video_id);

            if($video) {

                $total = $video->amount;

                $coupon_amount = 0;

                $coupon_reason = '';

                $is_coupon_applied = COUPON_NOT_APPLIED;

                if ($request->coupon_code) {

                    $coupon = Coupon::where('coupon_code', $request->coupon_code)->first();

                    if ($coupon) {
                        
                        if ($coupon->status == COUPON_INACTIVE) {

                            $coupon_reason = tr('coupon_inactive_reason');

                        } else {

                            $check_coupon = $this->check_coupon_applicable_to_user($userModel, $coupon)->getData();

                            if ($check_coupon->success) {

                                $is_coupon_applied = COUPON_APPLIED;

                                $amount_convertion = $coupon->amount;

                                if ($coupon->amount_type == PERCENTAGE) {

                                    $amount_convertion = round(amount_convertion($coupon->amount, $video->amount), 2);

                                }


                                if ($amount_convertion < $video->amount) {

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

                if ($total <= 0) {

                    $user_payment = new LiveVideoPayment;
                    $user_payment->payment_id = $is_coupon_applied ? 'COUPON-DISCOUNT' : FREE_PLAN;
                    $user_payment->user_id = $video->user_id;
                    $user_payment->live_video_viewer_id = $request->id;
                    $user_payment->live_video_id = $request->video_id;
                    $user_payment->status = PAID_STATUS;
                  
                    $user_payment->admin_amount = 0;

                    $user_payment->user_amount = 0;

                    $user_payment->payment_mode = APPLE_PAY;

                    $user_payment->currency = Setting::get('currency');

                    // Coupon details

                    $user_payment->is_coupon_applied = $is_coupon_applied;

                    $user_payment->coupon_code = $request->coupon_code ? $request->coupon_code : '';

                    $user_payment->coupon_amount = $coupon_amount;

                    $user_payment->live_video_amount = $video->amount;

                    $user_payment->amount = $total;

                    $user_payment->coupon_reason = $is_coupon_applied == COUPON_APPLIED ? '' : $coupon_reason;

                    $user_payment->save();


                    $data = ['id'=> $request->id, 'token'=> $userModel->token , 'payment_id' => $user_payment->payment_id];

                    $response_array = array('success' => true, 'message'=>tr('payment_success'),'data'=> $data);

                } else {

                    // Get the key from settings table

                    $stripe_secret_key = Setting::get('stripe_secret_key');
                    
                    if($stripe_secret_key) {

                        \Stripe\Stripe::setApiKey($stripe_secret_key);

                    } else {

                        $response_array = array('success' => false, 'error_messages' => Helper::error_message(902) , 'error_code' => 902);

                        throw new Exception(Helper::error_message(902));
                        
                    }

                    try {

                       $user_charge =  \Stripe\Charge::create(array(
                          "amount" => $total * 100,
                          "currency" => "usd",
                          "source" => $request->token_id,
                        ));

                       $payment_id = $user_charge->id;
                       $amount = $user_charge->amount/100;
                       $paid_status = $user_charge->paid;
                       
                       if($paid_status) {

                            $user_payment = new LiveVideoPayment;
                            $user_payment->payment_id  = $payment_id;
                            $user_payment->user_id = $video->user_id;
                            $user_payment->live_video_viewer_id = $request->id;
                            $user_payment->live_video_id = $request->video_id;
                            $user_payment->status = PAID_STATUS;
                            $user_payment->payment_mode = APPLE_PAY;

                            $user_payment->currency = Setting::get('currency');

                             // Coupon details

                            $user_payment->is_coupon_applied = $is_coupon_applied;

                            $user_payment->coupon_code = $request->coupon_code ? $request->coupon_code : '';

                            $user_payment->coupon_amount = $coupon_amount;

                            $user_payment->live_video_amount = $video->amount;

                            $user_payment->amount = $total;

                            $user_payment->coupon_reason = $is_coupon_applied == COUPON_APPLIED ? '' : $coupon_reason;


                            // Commission Spilit 

                            $admin_commission = Setting::get('admin_commission')/100;

                            $admin_amount = $total * $admin_commission;

                            $user_amount = $total - $admin_amount;

                            $user_payment->admin_amount = $admin_amount;

                            $user_payment->user_amount = $user_amount;

                            $user_payment->save();

                            // Commission Spilit Completed

                            if($user = User::find($user_payment->user_id)) {

                                $user->total_admin_amount = $user->total_admin_amount + $admin_amount;

                                $user->total_user_amount = $user->total_user_amount + $user_amount;

                                $user->remaining_amount = $user->remaining_amount + $user_amount;

                                $user->total = $user->total + $total;

                                $user->save();

                                add_to_redeem($user->id , $user_amount);
                            
                            }

                            $data = ['id'=> $request->id, 'token'=> $userModel->token , 'payment_id' => $payment_id];

                            $response_array = array('success' => true, 'message'=>tr('payment_success'),'data'=> $data);

                        } else {

                            $response_array = array('success' => false, 'error_messages' => Helper::error_message(902) , 'error_code' => 902);

                            throw new Exception(tr('no_vod_video_found'));

                        }
                    
                    } catch(\Stripe\Error\RateLimit $e) {

                        throw new Exception($e->getMessage(), 903);

                    } catch(\Stripe\Error\Card $e) {

                        throw new Exception($e->getMessage(), 903);

                    } catch (\Stripe\Error\InvalidRequest $e) {
                        // Invalid parameters were supplied to Stripe's API
                       
                        throw new Exception($e->getMessage(), 903);

                    } catch (\Stripe\Error\Authentication $e) {

                        // Authentication with Stripe's API failed

                        throw new Exception($e->getMessage(), 903);

                    } catch (\Stripe\Error\ApiConnection $e) {

                        // Network communication with Stripe failed

                        throw new Exception($e->getMessage(), 903);

                    } catch (\Stripe\Error\Base $e) {
                      // Display a very generic error to the user, and maybe send
                        
                        throw new Exception($e->getMessage(), 903);

                    } catch (Exception $e) {
                        // Something else happened, completely unrelated to Stripe

                        throw new Exception($e->getMessage(), 903);

                    } catch (\Stripe\StripeInvalidRequestError $e) {

                            Log::info(print_r($e,true));

                        throw new Exception($e->getMessage(), 903);
                        
                    
                    }

                }

            
            } else {

                $response_array = array('success' => false , 'error_messages' => tr('no_vod_video_found'));

                throw new Exception(tr('no_vod_video_found'));
                
            }


            DB::commit();

            return response()->json($response_array,200);

        } catch (Exception $e) {

            DB::rollback();

            $message = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages'=>$message, 'error_code'=>$code];

            return response()->json($response_array);

        }
        
    }

    /**
     * Function name : admin()
     *
     * To load admin details
     *
     * @param object $request - Request Details
     *
     * @return response of josn
     */
    public function admin(Request $request) {

        $admin = Admin::select('id', 'name', 'email', 'mobile', 'address')->first();

        return response()->json($admin);

    }


    // Connect Stream
    public function connectStream($file = null) {

        try {
            $client = new \GuzzleHttp\Client();

            $url  = Setting::get('wowza_server_url')."/v2/servers/_defaultServer_/vhosts/_defaultVHost_/sdpfiles/$file/actions/connect?connectAppName=live&appInstance=_definst_&mediaCasterType=rtp";

            $request = new \GuzzleHttp\Psr7\Request('PUT', $url);
            $promise = $client->sendAsync($request)->then(function ($response) {
                    // echo 'I completed! ' . $response->getBody();
                Log::info(print_r($response->getBody(), true));
            });
            $promise->wait();
        } catch(\GuzzleHttp\Exception\ClientException $e) {
           // dd($e->getResponse()->getBody()->getContents());
        }

    }

    // Disconnect Stream
    public function disConnectStream($file = null) {

        try {
            $client = new \GuzzleHttp\Client();

            $sdp = $file.".sdp";

            $url  = Setting::get('wowza_server_url')."/v2/servers/_defaultServer_/vhosts/_defaultVHost_/applications/live/instances/_definst_/incomingstreams/$sdp/actions/disconnectStream";

            $request = new \GuzzleHttp\Psr7\Request('PUT', $url);
            $promise = $client->sendAsync($request)->then(function ($response) {
                    //  echo 'I completed! ' . $response->getBody();

                Log::info('I completed! ' . $response->getBody());
                
            });
            $promise->wait();

            $this->deleteStream($file);

        } catch(\GuzzleHttp\Exception\ClientException $e) {
            // dd($e->getResponse()->getBody()->getContents());

            Log::info($e->getResponse()->getBody()->getContents());
        }

    }

    // Delete Stream
    public function deleteStream($file = null) {
        try {
            $client = new \GuzzleHttp\Client();

            $url  = Setting::get('wowza_server_url')."/v2/servers/_defaultServer_/vhosts/_defaultVHost_/sdpfiles/$file";

            $request = new \GuzzleHttp\Psr7\Request('DELETE', $url);
            $promise = $client->sendAsync($request)->then(function ($response) {
                     Log::info('I completed! ' . $response->getBody());
            });
            $promise->wait();
        } catch(\GuzzleHttp\Exception\ClientException $e) {
            // dd($e->getResponse()->getBody()->getContents());

            Log::info($e->getResponse()->getBody()->getContents());
        }

    }

    /**
     * Function Name : become_creator()
     *
     * To change the viewer into creator
     *
     * @created_by Shobana Chandrasekar
     *
     * @updated_by -- 
     *
     * @param integer $request - User id,token
     *
     * @return response of json details
     */
    public function become_creator(Request $request) {

        $user = User::find($request->id);

        if ($user) {

            // Check the user registered as content creator /viewer

            if ($user->is_content_creator == CREATOR_STATUS) {

                $response_array = ['success'=>false, 'error_messages'=>tr('registered_as_content_creator')];

            } else {

                $user->is_content_creator = CREATOR_STATUS;

                $user->save();

                $response_array = ['success'=>true, 'message'=>tr('become_creator')];   

            }         

        } else {

            $response_array = ['success'=>false, 'error_messages'=>tr('user_not_found')];

        }

        return response()->json($response_array);

    }

    /**
     * Function Name : autorenewal_cancel
     *
     * To prevent automatic subscriptioon, user have option to cancel subscription
     *
     * @created Shobana C
     *
     * @updated -
     *
     * @param object $request - USer details & payment details
     *
     * @return boolean response with message
     */
    public function autorenewal_cancel(Request $request) {

        $user_payment = UserSubscription::where('user_id', $request->id)->where('status', DEFAULT_TRUE)->orderBy('created_at', 'desc')->first();

        if($user_payment) {

            // Check the subscription is already cancelled

            if($user_payment->is_cancelled == AUTORENEWAL_CANCELLED) {

                $response_array = ['success' => 'false' , 'error_messages' => Helper::error_message(164) , 'error_code' => 164];

                return response()->json($response_array , 200);

            }

            $user_payment->is_cancelled = AUTORENEWAL_CANCELLED;

            $user_payment->cancel_reason = $request->cancel_reason;

            $user_payment->save();

            $subscription = $user_payment->subscription;

            $data = ['id'=>$request->id, 
            'subscription_id'=>$user_payment->subscription_id,
            'user_subscription_id'=>$user_payment->id,
            'title'=>$subscription ? $subscription->title : '',
            'description'=>$subscription ? $subscription->description : '',
            'popular_status'=>$subscription ? $subscription->popular_status : '',
            'plan'=>$subscription ? $subscription->plan : '',
            'amount'=>$user_payment->amount,
            'status'=>$user_payment->status,
            'expiry_date'=>date('d M Y', strtotime($user_payment->expiry_date)),
            'created_at'=>$user_payment->created_at,
            'currency'=>Setting::get('currency'),
            'payment_mode'=>$user_payment->payment_mode,
            'is_coupon_applied'=>$user_payment->is_coupon_applied,
            'coupon_code'=>$user_payment->coupon_code,
            'coupon_amount'=>$user_payment->coupon_amount,
            'subscription_amount'=>$user_payment->subscription_amount,
            'coupon_reason'=>$user_payment->coupon_reason,
            'is_cancelled'=>$user_payment->is_cancelled,
            'cancel_reason'=>$user_payment->cancel_reason,
            'show_autorenewal_options'=> DEFAULT_TRUE,
            'show_pause_autorenewal'=> DEFAULT_FALSE,
            'show_enable_autorenewal'=> DEFAULT_TRUE,
            ];

            $response_array = ['success'=> true, 'message'=>tr('cancel_subscription_success'), 'data'=>$data];

        } else {

            $response_array = ['success'=> false, 'error_messages'=>Helper::error_message(163), 'error_code'=>163];

        }

        return response()->json($response_array);

    }

   /**
     * Function Name : autorenewal_enable
     *
     * To prevent automatic subscriptioon, user have option to cancel subscription
     *
     * @created Shobana C
     *
     * @updated -
     *
     * @param object $request - USer details & payment details
     *
     * @return boolean response with message
     */
    public function autorenewal_enable(Request $request) {

        $user_payment = UserSubscription::where('user_id', $request->id)->where('status', DEFAULT_TRUE)->orderBy('created_at', 'desc')->first();


        if($user_payment) {

        // Check the subscription is already cancelled

            if($user_payment->is_cancelled == AUTORENEWAL_ENABLED) {
        
                $response_array = ['success' => 'false' , 'error_messages' => Helper::error_message(165) , 'error_code' => 165];

                return response()->json($response_array , 200);
            
            }

            $user_payment->is_cancelled = AUTORENEWAL_ENABLED;
          
            $user_payment->save();

            $subscription = $user_payment->subscription;

            $data = ['id'=>$request->id, 
            'subscription_id'=>$user_payment->subscription_id,
            'user_subscription_id'=>$user_payment->id,
            'title'=>$subscription ? $subscription->title : '',
            'description'=>$subscription ? $subscription->description : '',
            'popular_status'=>$subscription ? $subscription->popular_status : '',
            'plan'=>$subscription ? $subscription->plan : '',
            'amount'=>$user_payment->amount,
            'status'=>$user_payment->status,
            'expiry_date'=>date('d M Y', strtotime($user_payment->expiry_date)),
            'created_at'=>$user_payment->created_at,
            'currency'=>Setting::get('currency'),
            'payment_mode'=>$user_payment->payment_mode,
            'is_coupon_applied'=>$user_payment->is_coupon_applied,
            'coupon_code'=>$user_payment->coupon_code,
            'coupon_amount'=>$user_payment->coupon_amount,
            'subscription_amount'=>$user_payment->subscription_amount,
            'coupon_reason'=>$user_payment->coupon_reason,
            'is_cancelled'=>$user_payment->is_cancelled,
            'cancel_reason'=>$user_payment->cancel_reason,
            'show_autorenewal_options'=> DEFAULT_TRUE,
            'show_pause_autorenewal'=> DEFAULT_TRUE,
            'show_enable_autorenewal'=> DEFAULT_FALSE,
            ];

            $response_array = ['success'=> true, 'message'=> Helper::get_message(126) , 'code' => 126, 'data'=>$data];

        } else {

            $response_array = ['success'=> false, 'error_messages'=>Helper::error_message(163), 'error_code'=>163];

        }

        return response()->json($response_array);

   }

    /**
     * Function Name : vod_videos_save()
     *
     * To save the uploadeed video by the content creator
     *
     * @param object $request - VOD details
     *
     * @return response of jsonsuccess/ failure mesage 
     */
    public function vod_videos_save(Request $request) {
        
        try {

            $user = User::find($request->id);

            if ($user->is_content_creator == VIEWER_STATUS) {

                throw new Exception(tr('registered_as_viewer'));
            
            }

            if ($user->user_type == NON_SUBSCRIBED_USER) {

                throw new Exception(tr('subscribe_and_continue'));
                
            }

            $request->request->add([
                'user_id'=>$request->id,
                'created_by'=>CREATOR
            ]);

            $response = VideoRepo::vod_videos_save($request)->getData();


            if ($response->success) {

                return response()->json($response);

            } else {

                throw new Exception($response->error_messages, $response->error_code);
                
            }

        } catch (Exception $e) {

            $response_array = ['success'=>false, 'error_messages'=>$e->getMessage(), 'error_code'=>$e->getCode()];

            return response()->json($response_array);

        }

    }

    /**
     * Function Name : vod_videos_delete
     *
     * To delete vod video based on the video id
     *
     * @param object $request - User id, token & Video id
     *
     * @return response of json success/failure message
     */
    public function vod_videos_delete(Request $request) {

        try {

            $request->request->add([
                'user_id'=>$request->id,
            ]);

            $response = VideoRepo::vod_videos_delete($request)->getData();

            if ($response->success) {

                return response()->json($response);

            } else {

                throw new Exception($response->error_messages, $response->error_code);
                
            }

        } catch (Exception $e) {

            DB::rollback();

            $response_array = ['success'=>false, 'error_messages'=>$e->getMessage(), 'error_code'=>$e->getCode()];

            return response()->json($response_array);

        }
    
    }


    /**
     * Function Name : vod_videos_status()
     *
     * To changes the video status as approve/decline by using this functonion
     *
     * @param object $request - user id, token , status
     *
     * @return response of success/failure message
     */
    public function vod_videos_status(Request $request) {

        try {

            $request->request->add([
                'user_id'=>$request->id,
                'decline_by'=>CREATOR
            ]);

            $response = VideoRepo::vod_videos_status($request)->getData();

            if ($response->success) {

                return response()->json($response);

            } else {

                throw new Exception($response->error_messages, $response->error_code);
                
            }

        } catch (Exception $e) {

            $response_array = ['success'=>false, 'error_messages'=>$e->getMessage(), 'error_code'=>$e->getCode()];

            return response()->json($response_array);

        }

    }

    /**
     * Function Name : vod_videos_list()
     *
     * To list out all the videos in vod based on users as well as rand
     *
     * @param object $request - user id, token 
     *
     * @return response of array objects
     *
     */
    public function vod_videos_list(Request $request) {

        $take = $request->take ?  $request->take : Setting::get('admin_take_count');

        $query = VodVideo::vodResponse()->orderBy('created_at', 'desc')->skip($request->skip)
            ->take($take);

        if ($request->status) {

            $query->where('user_id', $request->id);

        } else {

            $query->where('vod_videos.status', DEFAULT_TRUE)
                ->where('vod_videos.admin_status', DEFAULT_TRUE)
                ->where('vod_videos.publish_status', VIDEO_PUBLISHED);

        }

        if ($request->video_id) {

            $query->where('vod_videos.unique_id', '!=',$request->video_id);
        }

        $model = $query->get();

        $data = [];

        $user = $request->id ? User::find($request->id) : '';

        foreach ($model as $key => $value) {

            $share_link = Setting::get('ANGULAR_URL').'vod/single?title='.$value->title;

            $ppv_status = ($value->user_id == $request->id) ? true : UserRepo::pay_per_views_status_check($user ? $request->id : '', $user ? $user->user_type : 0, $value);
          
            $data[] = [
                'user_id'=>$value->user_id,
                'user_name'=>$value->user_name,
                'user_picture'=>$value->user_picture,
                'vod_id'=>$value->vod_id,
                'title'=>$value->title,
                'description'=>$value->description,
                'image'=>$value->image,
                'video'=>$value->video,
                'amount'=>$value->amount,
                'type_of_subscription'=>$value->type_of_subscription,
                'type_of_user'=>$value->type_of_user,
                'created_at'=>$value->created_at->diffForhumans(),
                'status'=>$value->status,
                'admin_status'=>$value->admin_status,
                'ppv_status'=> $ppv_status['success'] ?? false,
                'ppv_details'=>$ppv_status,
                'publish_time'=>$value->publish_time,
                'publish_status'=>$value->publish_status,
                'currency'=>Setting::get('currency'),
                'unique_id'=>$value->unique_id,
                'share_link'=>$share_link
               
            ];

        }

        $response_array = ['success'=>true, 'data'=>$data];
        
        return response()->json($response_array);   

    } 

    /**
     * Function Name : vod_videos_view()
     * 
     * To view uploaded video based on id
     *
     * @param object $request - User id, token and video id
     *
     * @return respons of object
     */
    public function vod_videos_view(Request $request) {

        try {

            DB::beginTransaction();

            $validator = Validator::make($request->all(),
                array(
                    'video_id'=>'required|exists:vod_videos,unique_id'
                ), array(

                    'exists'=>'The selected video not available',
                ));

            if ($validator->fails()) {

                // Error messages added in response for debugging
                
                $errors = implode(',',$validator->messages()->all());

                throw new Exception($errors, 101);


            } else {

                $model = VodVideo::vodResponse()->where('vod_videos.unique_id',$request->video_id)->first();

                if (!$model->status) {

                    if ($model->user_id != $request->id) {

                        throw new Exception(tr('declined_video'));
                    
                    }

                }

                if (!$model->admin_status) {

                    if ($model->user_id != $request->id) {

                        throw new Exception(tr('admin_declined_video'));
                    
                    }

                }

                if (!$request->id) {

                    if ($model->amount > 0) {

                        if ($request->invoice) {


                        } else {

                            throw new Exception(tr('video_has_ppv'), 2007);

                        }

                    }

                } else {

                    $user = User::find($request->id);

                    if ($model->user_id != $request->id) {

                        $ppv_status = UserRepo::pay_per_views_status_check($user->id, $user->user_type, $model);

                        if (!$ppv_status['success']) {

                            if ($request->invoice) {

                            } else {

                                throw new Exception(tr('video_has_ppv'), 2007); 

                            }
                            
                        }

                    }

                }

            }

            DB::commit();

            $model->date = $model->created_at->diffForhumans();

            // $rtmp_video = Helper::convert_normal_video_to_hlssecure(get_video_end($model->video) , $model->video);
                    
            $hls_video = Helper::convert_normal_video_to_hlssecure(get_video_end($model->video) , $model->video);

            $model->rtmp_video = $model->hls_video = $model->video = $hls_video;

            if($model->publish_time) {

                $model->publish_time = date('m/d/Y', strtotime($model->publish_time));

            }

            $model->publish_type = $model->publish_status == VIDEO_PUBLISHED ? PUBLISH_NOW : PUBLISH_LATER;

            $model->type_of_subscription = (int) $model->type_of_subscription;

            $response_array = ['success'=>true, 'data'=>$model];

            return response()->json($response_array);

        } catch (Exception $e) {

            DB::rollback();

            $response_array = ['success'=>false, 'error_messages'=>$e->getMessage(), 'error_code'=>$e->getCode()];

            return response()->json($response_array);

        }

    }

    /**
     * Function Name : vod_videos_set_ppv()
     *
     * To set pay per view in VOD video based on video id
     *
     * @param object $request - User id, token, video id, ppv details
     *
     * @return response of json success/failure message
     */
    public function vod_videos_set_ppv(Request $request) {

        try {

            $request->request->add([
                'user_id'=>$request->id,
            ]);

            $response = VideoRepo::vod_videos_set_ppv($request)->getData();

            if ($response->success) {

                return response()->json($response);

            } else {

                throw new Exception($response->error_messages, $response->error_code);
                
            }

        } catch (Exception $e) {

            $response_array = ['success'=>false, 'error_messages'=>$e->getMessage(), 'error_code'=>$e->getCode()];

            return response()->json($response_array);

        }

    }

    /**
     * Function Name : vod_videos_remove_ppv()
     *
     * To remove pay per view in VOD video based on video id
     *
     * @param object $request - User id, token, video id, ppv details
     *
     * @return response of json success/failure message
     */
    public function vod_videos_remove_ppv(Request $request) {

        try {

            $request->request->add([
                'user_id'=>$request->id,
            ]);

            $response = VideoRepo::vod_videos_remove_ppv($request)->getData();

            if ($response->success) {

                return response()->json($response);

            } else {

                throw new Exception($response->error_messages, $response->error_code);
                
            }

        } catch (Exception $e) {

            $response_array = ['success'=>false, 'error_messages'=>$e->getMessage(), 'error_code'=>$e->getCode()];

            return response()->json($response_array);

        }

    }

    /**
     * Function Name : vod_videos_payment()
     *
     * Pay the amount of ppv which is set in the video
     *
     * @param object $request - User id, token & video_id
     *
     * @return response of success/failure message
     *
     */
    public function vod_videos_payment(Request $request) {

        try {

            DB::beginTransaction();

            $validator = Validator::make(
                $request->all(),
                array(
                    'video_id'=>'required|exists:vod_videos,id,status,'.DEFAULT_TRUE.',admin_status,'.DEFAULT_TRUE,
                    'payment_id'=>'required',

                    'coupon_code'=>'exists:coupons,coupon_code',
                ),  array(
                     'coupon_code.exists' => tr('coupon_code_not_exists'),
                    'video_id.exists' => tr('livevideo_not_exists'),
                ));


            if ($validator->fails()) {
                // Error messages added in response for debugging
                $errors = implode(',',$validator->messages()->all());

                $response_array = ['success' => false,'error_messages' => $errors,'error_code' => 101];

                throw new Exception($errors);

            } else {

                $video = VodVideo::find($request->video_id);

                $user = User::find($request->id);

                $total = $video->amount;

                $coupon_amount = 0;

                $coupon_reason = '';

                $is_coupon_applied = COUPON_NOT_APPLIED;

                if ($request->coupon_code) {

                    $coupon = Coupon::where('coupon_code', $request->coupon_code)->first();

                    if ($coupon) {
                        
                        if ($coupon->status == COUPON_INACTIVE) {

                            $coupon_reason = tr('coupon_inactive_reason');

                        } else {

                            $check_coupon = $this->check_coupon_applicable_to_user($user, $coupon)->getData();

                            if ($check_coupon->success) {

                                $is_coupon_applied = COUPON_APPLIED;

                                $amount_convertion = $coupon->amount;

                                if ($coupon->amount_type == PERCENTAGE) {

                                    $amount_convertion = round(amount_convertion($coupon->amount, $video->amount), 2);

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

                        $coupon_reason = tr('coupon_delete_reason');
                    }
                }

                $payment = PayPerView::where('user_id', $request->id)
                            ->where('video_id', $request->video_id)
                            ->where('status', PAID_STATUS)
                            ->orderBy('ppv_date', 'desc')
                            ->first();

                $payment_status = DEFAULT_FALSE;

                if ($payment) {

                    if ($video->type_of_subscription == RECURRING_PAYMENT && $payment->is_watched == WATCHED) {

                        $payment_status = DEFAULT_FALSE;

                    } else {

                        $payment_status = DEFAULT_TRUE;

                    }

                } else {

                    $payment_status = DEFAULT_FALSE;

                }

                if ($video->is_pay_per_view == PPV_ENABLED) {

                    if ($payment_status) {

                        throw new Exception(tr('already_paid_amount_to_video'));

                    }

                    $user_payment = new PayPerView;
                    
                    $user_payment->payment_id  = $request->payment_id;

                    $user_payment->user_id = $request->id;

                    $user_payment->video_id = $request->video_id;

                    $user_payment->status = PAID_STATUS;

                    $user_payment->is_watched = NOT_YET_WATCHED;

                    $user_payment->payment_mode = PAYPAL;

                    $user_payment->ppv_date = date('Y-m-d H:i:s');

                    if ($video->type_of_user == NORMAL_USER) {

                        $user_payment->type_of_user = tr('normal_users');

                    } else if($video->type_of_user == PAID_USER) {

                        $user_payment->type_of_user = tr('paid_users');

                    } else if($video->type_of_user == BOTH_USERS) {

                        $user_payment->type_of_user = tr('both_users');
                    }


                    if ($video->type_of_subscription == ONE_TIME_PAYMENT) {

                        $user_payment->type_of_subscription = tr('one_time_payment');

                    } else if($video->type_of_subscription == RECURRING_PAYMENT) {

                        $user_payment->type_of_subscription = tr('recurring_payment');

                    }
                    // Coupon details

                    $user_payment->is_coupon_applied = $is_coupon_applied;

                    $user_payment->coupon_code = $request->coupon_code ? $request->coupon_code : '';

                    $user_payment->coupon_amount = $coupon_amount;

                    $user_payment->ppv_amount = $video->amount;

                    $user_payment->amount = $total;

                    $user_payment->coupon_reason = $is_coupon_applied == COUPON_APPLIED ? '' : $coupon_reason;

                    $user_payment->save();

                    if($user_payment) {

                        // Do Commission spilit  and redeems for moderator

                        Log::info("ppv_commission_spilit started");

                        UserRepo::ppv_commission_split($video->id , $user_payment->id , "");

                        Log::info("ppv_commission_spilit END"); 
   

                    } 

                    $response_array = ['success'=>true, 'message'=>tr('payment_success')];

                } else {

                    throw new Exception(tr('ppv_not_set'));
                    
                }

            }

            DB::commit();

            return response()->json($response_array, 200);

        } catch (Exception $e) {

            DB::rollback();

            $e = $e->getMessage();

            $response_array = ['success'=>false, 'error_messages'=>$e];

            return response()->json($response_array);
        }
    
    }

    /**
     * Function Name : ppv_history()
     *
     * To list of paid videos history of VOD
     *
     * @param object $request - User id, token
     *
     * @return response of array objects
     */
    public function ppv_history(Request $request) {

        $currency = Setting::get('currency');

        $take = $request->take ? $request->take : Setting::get('admin_take_count');

        $model = PayPerView::select('pay_per_views.id as ppv_id',
                'vod_videos.id as vod_id' , 
                'vod_videos.title' , 
                'vod_videos.description' , 
                'vod_videos.image',
                'vod_videos.video',
                'pay_per_views.amount',
                'pay_per_views.type_of_subscription',
                'pay_per_views.type_of_user',
                'pay_per_views.created_at',
                'pay_per_views.status',
                'pay_per_views.payment_id',
                'pay_per_views.payment_mode',
                'pay_per_views.is_watched',
                'pay_per_views.ppv_date',
                'pay_per_views.ppv_amount',
                'pay_per_views.coupon_amount',
                'pay_per_views.coupon_code',
                'pay_per_views.coupon_reason',
                'pay_per_views.reason',
                'pay_per_views.is_coupon_applied',
                DB::raw("'$currency' as currency"))
                ->leftJoin('vod_videos', 'vod_videos.id','=', 'pay_per_views.video_id')
                ->where('pay_per_views.user_id', $request->id)
                ->orderBy('pay_per_views.created_at', 'desc')->skip($request->skip)
                ->take($take)->get();

        $data = [];

        foreach ($model as $key => $value) {

            $hls_video = Helper::convert_normal_video_to_hlssecure(get_video_end($value->video), $value->video);
            
            $data[] = [
                'ppv_id'=>$value->ppv_id,
                'vod_id'=>$value->vod_id,
                'title'=>$value->title,
                'description'=>$value->description,
                'image'=>$value->image,
                'amount'=>$value->amount,
                'paid_status'=>$value->status,
                'type_of_subscription'=>$value->type_of_subscription,
                'type_of_user'=>$value->type_of_user,
                'created_at'=>date('F d, Y h:m a', strtotime($value->ppv_date)),
                'is_watched'=>$value->is_watched,
                'currency'=>$value->currency,
                'payment_id'=>$value->payment_id,
                'admin_status'=>$value->admin_status,
                'payment_mode'=>$value->payment_mode,
                'coupon_code'=>$value->coupon_code,
                'coupon_amount'=>$value->coupon_amount,
                'ppv_amount'=>$value->ppv_amount,
                'coupon_reason'=>$value->coupon_reason,
                'is_coupon_applied'=>$value->is_coupon_applied,
                'reason'=>$value->reason,
                'rtmp_video'=> $hls_video,
                'hls_video'=> $hls_video,
                'video'=> $hls_video

                ];

        }

        $response_array = ['success'=>true, 'data'=>$data];

        return response()->json($response_array);

    }


    /**
     * Function Name : ppv_revenue
     *
     * To Display all revenues of ppv list based on logged in user
     *
     * @param object $request - User id, token 
     *
     * @return response of success/failure message
     *
     */
    public function ppv_revenue(Request $request) {

        $currency = Setting::get('currency');

        $take = $request->take ? $request->take : Setting::get('admin_take_count');

        $model = VodVideo::vodRevenueResponse()
                ->where('vod_videos.user_id', $request->id)
                ->orderBy('vod_videos.user_amount', 'desc')->skip($request->skip)
                ->take($take)->get();

        $data = [];

        $user_commission = VodVideo::where('vod_videos.user_id', $request->id)->sum('user_amount');

        foreach ($model as $key => $value) {
            
            $data[] = [
                'vod_id'=>$value->vod_id,
                'title'=>$value->title,
                'user_id'=>$value->user_id,
                'description'=>$value->description,
                'image'=>$value->image,
                'amount'=>$value->amount,
                'admin_amount'=>$value->admin_amount,
                'user_amount'=>$value->user_amount,
                'created_at'=>$value->created_at->diffForhumans(),
                'currency'=>$value->currency,
                'unique_id'=>$value->unique_id
                ];

        }

        $paid_videos = VodVideo::vodRevenueResponse()
                ->where('vod_videos.user_id', $request->id)
                ->where('vod_videos.user_amount', '>', 0)->count();

        $total_videos = VodVideo::vodRevenueResponse()
                ->where('vod_videos.user_id', $request->id)->count();

        $response_array = ['success'=>true, 'data'=>$data, 
            'total_amount'=>$user_commission, 
            'currency'=>$currency, 
            'total_paid_videos'=>$paid_videos ? $paid_videos : 0,
            'total_videos'=>$total_videos ? $total_videos : 0];

        return response()->json($response_array);
    } 

    /**
     * Function Name : vod_videos_search()
     *
     * To display limites vod videos based on search
     *
     * @param object $request - User id, token
     *
     * @return response of json
     */
    public function vod_videos_search(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'term' => 'required',
            ),
            array(
                'exists' => 'The :attribute doesn\'t exists',
            )
        );
    
        if ($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            $response_array = array('success' => false, 'error_messages' => $error_messages, 'error_code' => 101);

        } else {

            $list = [];

            $q = $request->term;

            $take = $request->take ? $request->take : Setting::get('admin_take_count');

            $query = VodVideo::vodResponse()
                ->where('vod_videos.title', 'like', "%".$q."%");
            
            if ($request->status) {

                $query->where('user_id', $request->id);

            } else {

                $query->where('vod_videos.status', DEFAULT_TRUE)
                    ->where('vod_videos.admin_status', DEFAULT_TRUE)
                    ->where('vod_videos.publish_status', VIDEO_PUBLISHED);

            }

            $model = $query->skip($request->skip)->take($take)->get();

            $data = [];

            $user = $request->id ? User::find($request->id) : '';

            foreach ($model as $key => $value) {

                $ppv_status = ($value->user_id == $request->id) ? true : UserRepo::pay_per_views_status_check($user ? $request->id : '', $user ? $user->user_type : 0, $value);
               
                $data[] = [
                    'user_id'=>$value->user_id,
                    'user_name'=>$value->user_name,
                    'user_picture'=>$value->user_picture,
                    'vod_id'=>$value->vod_id,
                    'title'=>$value->title,
                    'description'=>$value->description,
                    'image'=>$value->image,
                    'video'=>$value->video,
                    'amount'=>$value->amount,
                    'type_of_subscription'=>$value->type_of_subscription,
                    'type_of_user'=>$value->type_of_user,
                    'created_at'=>$value->created_at->diffForhumans(),
                    'status'=>$value->status,
                    'admin_status'=>$value->admin_status,
                    'ppv_status'=>$ppv_status['success'],
                    'ppv_details'=>$ppv_status,
                    'unique_id'=>$value->unique_id

                ];

            }

            $response_array = ['success'=> true, 'data'=>$data];
            
        }   
    
        return response()->json($response_array, 200);

    }

    /**
     * Function Name  - vod_videos_oncomplete_ppv()
     *
     * To check the ppv video is one time payment or recurrng payment, if it is recurring payment need to pay again
     *
     * @param object $request - User if, token & video id
     *
     * @return response of json
     */
    public function vod_videos_oncomplete_ppv(Request $request) {

        try {

            DB::beginTransaction();

            $validator = Validator::make(
                $request->all(),
                array(
                    'video_id'=>'required|exists:vod_videos,id',
                ),  array(
                    'exists' => 'The :attribute doesn\'t exists',
                ));

            if ($validator->fails()) {
                // Error messages added in response for debugging
                $errors = implode(',',$validator->messages()->all());

                $response_array = ['success' => false,'error_messages' => $errors,'error_code' => 101];

                throw new Exception($errors);

            } else {

                $video = VodVideo::find($request->video_id);

                if ($video->user_id != $request->id)  {

                    $payment = PayPerView::where('user_id', $request->id)->where('video_id', $video->id)
                            ->where('status', PAID_STATUS)
                            ->where('is_watched', '!=', WATCHED)
                            ->orderBy('ppv_date', 'desc')
                            ->first();

                    if ($payment) {

                        $payment->is_watched = WATCHED;

                        $payment->save();

                    }

                    if ($video->type_of_subscription == RECURRING_PAYMENT) {

                        $response_array = ['success'=>true, 'navigate_and_pay'=>PAY_AND_WATCH];
                        
                    } else {

                        $response_array = ['success'=>true, 'navigate_and_pay'=>NO_NEED_TO_PAY];

                    }

                } else {

                    $response_array = ['success'=>true, 'navigate_and_pay'=>NO_NEED_TO_PAY];

                }

            }

            // $response_array = ['success'=>true, 'pay'=>1];

            DB::commit();

            return response()->json($response_array, 200);

        } catch (Exception $e) {

            DB::rollback();

            $e = $e->getMessage();

            $response_array = ['success'=>false, 'error_messages'=>$e];

            return response()->json($response_array);
        }

    }


    /**
     * Function Name : vod_videos_stripe_ppv()
     * 
     * Pay the payment for Pay per view through stripe
     *
     * @param object $request - Admin video id
     * 
     * @return response of success/failure message
     */
    public function vod_videos_stripe_ppv(Request $request) {

        try {

            DB::beginTransaction();

             $validator = Validator::make($request->all(), [
                'coupon_code' => 'nullable|exists:coupons,coupon_code,status,'.COUPON_ACTIVE,  
                'video_id'=>'required|exists:vod_videos,id,publish_status,'.VIDEO_PUBLISHED.',admin_status,'.VOD_APPROVED_BY_USER.',status,'.VOD_APPROVED_BY_ADMIN          
            ], array(
                    'coupon_code.exists' => tr('coupon_code_not_exists'),
                    'video_id.exists' => tr('video_not_exists'),
                ));

            if($validator->fails()) {

                $errors = implode(',', $validator->messages()->all());
                
                $response_array = ['success' => false, 'error_messages' => $errors, 'error_code' => 101];

                throw new Exception($errors);

            } else {

                $userModel = User::find($request->id);

                if ($userModel) {

                    if ($userModel->card_id) {

                        $user_card = Card::find($userModel->card_id);

                        if ($user_card && $user_card->is_default) {

                            $video = VodVideo::find($request->video_id);

                            if($video) {

                                $total = $video->amount;

                                $coupon_amount = 0;

                                $coupon_reason = '';

                                $is_coupon_applied = COUPON_NOT_APPLIED;

                                if ($request->coupon_code) {

                                    $coupon = Coupon::where('coupon_code', $request->coupon_code)->first();

                                    if ($coupon) {
                                        
                                        if ($coupon->status == COUPON_INACTIVE) {

                                            $coupon_reason = tr('coupon_inactive_reason');

                                        } else {

                                            $check_coupon = $this->check_coupon_applicable_to_user($userModel, $coupon)->getData();

                                            if ($check_coupon->success) {

                                                $is_coupon_applied = COUPON_APPLIED;

                                                $amount_convertion = $coupon->amount;

                                                if ($coupon->amount_type == PERCENTAGE) {

                                                    $amount_convertion = round(amount_convertion($coupon->amount, $video->amount), 2);

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

                                if ($total <= 0) {

                                    $user_payment = new PayPerView;

                                    $user_payment->payment_id = $is_coupon_applied ? 'COUPON-DISCOUNT' : FREE_PLAN;

                                    $user_payment->user_id = $request->id;
                                    $user_payment->video_id = $request->video_id;

                                    $user_payment->status = PAID_STATUS;

                                    $user_payment->is_watched = NOT_YET_WATCHED;

                                    $user_payment->ppv_date = date('Y-m-d H:i:s');

                                    if ($video->type_of_user == NORMAL_USER) {

                                        $user_payment->type_of_user = tr('normal_users');

                                    } else if($video->type_of_user == PAID_USER) {

                                        $user_payment->type_of_user = tr('paid_users');

                                    } else if($video->type_of_user == BOTH_USERS) {

                                        $user_payment->type_of_user = tr('both_users');
                                    }


                                    if ($video->type_of_subscription == ONE_TIME_PAYMENT) {

                                        $user_payment->type_of_subscription = tr('one_time_payment');

                                    } else if($video->type_of_subscription == RECURRING_PAYMENT) {

                                        $user_payment->type_of_subscription = tr('recurring_payment');

                                    }

                                    $user_payment->payment_mode = CARD;

                                    // Coupon details

                                    $user_payment->is_coupon_applied = $is_coupon_applied;

                                    $user_payment->coupon_code = $request->coupon_code ? $request->coupon_code : '';

                                    $user_payment->coupon_amount = $coupon_amount;

                                    $user_payment->ppv_amount = $video->amount;

                                    $user_payment->amount = $total;

                                    $user_payment->coupon_reason = $is_coupon_applied == COUPON_APPLIED ? '' : $coupon_reason;

                                    $user_payment->save();

                                    // Commission Spilit 

                                    if($video->amount > 0) { 

                                        // Do Commission spilit  and redeems for moderator

                                        Log::info("ppv_commission_spilit started");

                                        UserRepo::ppv_commission_split($video->id , $user_payment->id , "");

                                        Log::info("ppv_commission_spilit END"); 
                                        
                                    }

                                    \Log::info("ADD History - add_to_redeem");

                                    $data = ['id'=> $request->id, 'token'=> $userModel->token , 'payment_id' => $user_payment->payment_id];

                                    $response_array = array('success' => true, 'message'=>tr('payment_success'),'data'=> $data);

                                } else {

                                    // Get the key from settings table

                                    $stripe_secret_key = Setting::get('stripe_secret_key');

                                    $customer_id = $user_card->customer_id;
                                    
                                    if($stripe_secret_key) {

                                        \Stripe\Stripe::setApiKey($stripe_secret_key);

                                    } else {

                                        $response_array = array('success' => false, 'error_messages' => Helper::get_error_message(902) , 'error_code' => 902);

                                        throw new Exception(Helper::get_error_message(902));
                                        
                                    }

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

                                            $user_payment = new PayPerView;
                                            $user_payment->payment_id  = $payment_id;
                                            $user_payment->user_id = $request->id;
                                            $user_payment->video_id = $request->video_id;
                                            $user_payment->payment_mode = CARD;
                                        

                                            $user_payment->status = PAID_STATUS;

                                            $user_payment->is_watched = NOT_YET_WATCHED;

                                            $user_payment->ppv_date = date('Y-m-d H:i:s');

                                            if ($video->type_of_user == NORMAL_USER) {

                                                $user_payment->type_of_user = tr('normal_users');

                                            } else if($video->type_of_user == PAID_USER) {

                                                $user_payment->type_of_user = tr('paid_users');

                                            } else if($video->type_of_user == BOTH_USERS) {

                                                $user_payment->type_of_user = tr('both_users');
                                            }


                                            if ($video->type_of_subscription == ONE_TIME_PAYMENT) {

                                                $user_payment->type_of_subscription = tr('one_time_payment');

                                            } else if($video->type_of_subscription == RECURRING_PAYMENT) {

                                                $user_payment->type_of_subscription = tr('recurring_payment');

                                            }

                                            // Coupon details

                                            $user_payment->is_coupon_applied = $is_coupon_applied;

                                            $user_payment->coupon_code = $request->coupon_code ? $request->coupon_code : '';

                                            $user_payment->coupon_amount = $coupon_amount;

                                            $user_payment->ppv_amount = $video->amount;

                                            $user_payment->amount = $total;

                                            $user_payment->coupon_reason = $is_coupon_applied == COUPON_APPLIED ? '' : $coupon_reason;
                                                                  
                                            $user_payment->save();

                                            // Commission Spilit 

                                            if($video->amount > 0) { 

                                                // Do Commission spilit  and redeems for moderator

                                                Log::info("ppv_commission_spilit started");

                                                UserRepo::ppv_commission_split($video->id , $user_payment->id , "");

                                                Log::info("ppv_commission_spilit END");
                                                
                                            }

                                        
                                            $data = ['id'=> $request->id, 'token'=> $userModel->token , 'payment_id' => $payment_id];

                                            $response_array = array('success' => true, 'message'=>tr('payment_success'),'data'=> $data);

                                        } else {

                                            $response_array = array('success' => false, 'error_messages' => Helper::get_error_message(902) , 'error_code' => 902);

                                            throw new Exception(tr('no_vod_video_found'));

                                        }
                                    
                                    } catch(\Stripe\Error\RateLimit $e) {

                                        throw new Exception($e->getMessage(), 903);

                                    } catch(\Stripe\Error\Card $e) {

                                        throw new Exception($e->getMessage(), 903);

                                    } catch (\Stripe\Error\InvalidRequest $e) {
                                        // Invalid parameters were supplied to Stripe's API
                                       
                                        throw new Exception($e->getMessage(), 903);

                                    } catch (\Stripe\Error\Authentication $e) {

                                        // Authentication with Stripe's API failed

                                        throw new Exception($e->getMessage(), 903);

                                    } catch (\Stripe\Error\ApiConnection $e) {

                                        // Network communication with Stripe failed

                                        throw new Exception($e->getMessage(), 903);

                                    } catch (\Stripe\Error\Base $e) {
                                      // Display a very generic error to the user, and maybe send
                                        
                                        throw new Exception($e->getMessage(), 903);

                                    } catch (Exception $e) {
                                        // Something else happened, completely unrelated to Stripe

                                        throw new Exception($e->getMessage(), 903);

                                    } catch (\Stripe\StripeInvalidRequestError $e) {

                                            Log::info(print_r($e,true));

                                        throw new Exception($e->getMessage(), 903);
                                        
                                    
                                    }


                                }

                            
                            } else {

                                $response_array = array('success' => false , 'error_messages' => tr('no_vod_video_found'));

                                throw new Exception(tr('no_vod_video_found'));
                                
                            }

                        } else {

                        
                            throw new Exception(tr('no_default_card_available'), 901);

                        }

                    } else {


                        throw new Exception(tr('no_default_card_available'), 901);

                    }

                } else {

                    throw new Exception(tr('no_user_detail_found'));
                    

                }

            }

            DB::commit();

            return response()->json($response_array,200);

        } catch (Exception $e) {

            DB::rollback();

            $message = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages'=>$message, 'error_code'=>$code];

            return response()->json($response_array);

        }
        
    }

    /**
     * Function Name : vod_videos_publish()
     *
     * To Publish the video for user
     *
     * @created_by - Shobana Chandrasekar
     *
     * @updated_by - -  
     *
     * @param object $request : Video details with user details
     *
     * @return Flash Message
     */
    public function vod_videos_publish(Request $request) {

        try {

            $request->request->add([
                'user_id'=>$request->id,
            ]);

            $response = VideoRepo::vod_videos_publish($request)->getData();

            if ($response->success) {

                return response()->json($response);

            } else {

                throw new Exception($response->error_messages, $response->error_code);
                
            }

        } catch (Exception $e) {

            $response_array = ['success'=>false, 'error_messages'=>$e->getMessage(), 'error_code'=>$e->getCode()];

            return response()->json($response_array);

        }
    }

    /**
     * Function Name : streamer_galleries_save()
     *
     * To save gallery details of the streamer
     *
     * @created_by - Shobana Chandrasekar
     *
     * @updated_by - - 
     *
     * @param object $request - Model Object
     *
     * @return response of success / Failure
     */
    public function streamer_galleries_save(Request $request) {

        try {

            $request->request->add([
                'user_id'=>$request->id,
            ]);

            $response = StreamerGalleryRepo::streamer_galleries_save($request)->getData();

            if ($response->success) {

                return response()->json($response);

            } else {

                throw new Exception($response->error_messages, $response->error_code);
                
            }

        } catch (Exception $e) {

            $response_array = ['success'=>false, 'error_messages'=>$e->getMessage(), 'error_code'=>$e->getCode()];

            return response()->json($response_array);

        }

    }

    /**
     * Function Name : streamer_galleries_list()
     *
     * To load galleries based on user id
     *
     * @created_by - Shobana Chandrasekar
     *
     * @updated_by - - 
     *
     * @param model image object - $request
     *
     * @return response of succes failure 
     */
    public function streamer_galleries_list(Request $request) {

        try {

            /*$request->request->add([
                'user_id'=>$request->id,
            ]);*/

            $response = StreamerGalleryRepo::streamer_galleries_list($request)->getData();

            if ($response->success) {

                return response()->json($response);

            } else {

                throw new Exception($response->error_messages, $response->error_code);
                
            }

        } catch (Exception $e) {

            $response_array = ['success'=>false, 'error_messages'=>$e->getMessage(), 'error_code'=>$e->getCode()];

            return response()->json($response_array);

        }
    }

    /**
     * Function Name : streamer_galleries_delete()
     *
     * To delete particular image based on id
     *
     * @created_by - Shobana Chandrasekar
     *
     * @updated_by - - 
     *
     * @param model image object - $request
     *
     * @return response of succes failure 
     */
    public function streamer_galleries_delete(Request $request) {

        try {

            $request->request->add([
                'user_id'=>$request->id,
            ]);

            $response = StreamerGalleryRepo::streamer_galleries_delete($request)->getData();

            if ($response->success) {

                return response()->json($response);

            } else {

                throw new Exception($response->error_messages, $response->error_code);
                
            }

        } catch (Exception $e) {

            $response_array = ['success'=>false, 'error_messages'=>$e->getMessage(), 'error_code'=>$e->getCode()];

            return response()->json($response_array);

        }
    
    }

    /**
     * Function Name : apply_coupon_subscription()
     *
     * Apply coupon to subscription if the user having coupon codes
     *
     * @created By - Shobana Chandrasekar
     *
     * @updated_by - -
     *
     * @param object $request - User details, subscription details
     *
     * @return response of coupon details with amount
     *
     */
    public function apply_coupon_subscription(Request $request) {

        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required|exists:coupons,coupon_code',  
            'subscription_id'=>'required|exists:subscriptions,id'          
        ], array(
            'coupon_code.exists' => tr('coupon_code_not_exists'),
            'subscription_id.exists' => tr('subscription_not_exists'),
        ));
        
        if ($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            $response_array = array('success' => false, 'error_messages'=>$error_messages , 'error_code' => 101);

            return response()->json($response_array);
        }
        

        $model = Coupon::where('coupon_code', $request->coupon_code)->first();

        if ($model) {

            if ($model->status) {

                $user = User::find($request->id);

                $check_coupon = $this->check_coupon_applicable_to_user($user, $model)->getData();

                if(strtotime($model->expiry_date) >= strtotime(date('Y-m-d'))) {

                    if ($check_coupon->success) {

                        if(strtotime($model->expiry_date) >= strtotime(date('Y-m-d'))) {

                            $subscription = Subscription::find($request->subscription_id);

                            if($subscription) {

                                if($subscription->status) {

                                    $amount_convertion = $model->amount;

                                    if ($model->amount_type == PERCENTAGE) {

                                        $amount_convertion = round(amount_convertion($model->amount, $subscription->amount), 2);

                                    }

                                    if ($subscription->amount > $amount_convertion && $amount_convertion > 0) {

                                        $amount = $subscription->amount - $amount_convertion;
                    
                                        $response_array = ['success'=> true, 
                                        'data'=>[
                                            'remaining_amount'=>(string) $amount,
                                            'coupon_amount'=> (string) $amount_convertion,
                                            'coupon_code'=>$model->coupon_code,
                                            'original_coupon_amount'=>(string) ($model->amount_type == PERCENTAGE ? $model->amount.'%' : Setting::get('currency').$model->amount)
                                        ]];

                                    } else {

                                        // $response_array = ['success'=> false, 'error_messages'=>Helper::get_error_message(156), 'error_code'=>156];
                                        $amount = 0;
                                        $response_array = ['success'=> true, 
                                        'data'=>[
                                            'remaining_amount'=>(string) $amount,
                                            'coupon_amount'=> (string) $amount_convertion,
                                            'coupon_code'=>$model->coupon_code,
                                            'original_coupon_amount'=>(string) ($model->amount_type == PERCENTAGE ? $model->amount.'%' : Setting::get('currency').$model->amount)
                                        ]];

                                    }

                                } else {

                                    $response_array = ['success'=> false, 'error_messages'=>Helper::error_message(170), 'error_code'=>170];

                                }

                            } else {

                                $response_array = ['success'=> false, 'error_messages'=>Helper::error_message(169), 'error_code'=>169];
                            }

                        } else {

                            $response_array = ['success'=> false, 'error_messages'=>Helper::error_message(173), 'error_code'=>173];

                        }

                    } else {

                        $response_array = ['success'=> false, 'error_messages'=>$check_coupon->error_messages];
                    }

                } else {

                    $response_array = ['success'=> false, 'error_messages'=>Helper::error_message(173), 'error_code'=>173];
                }

            } else {

                $response_array = ['success'=> false, 'error_messages'=>Helper::error_message(168), 'error_code'=>168];
            }



        } else {

            $response_array = ['success'=> false, 'error_messages'=>Helper::error_message(167), 'error_code'=>167];

        }

        return response()->json($response_array);

    }

    /**
     * Function Name : apply_coupon_live_videos()
     *
     * Apply coupon to live videos if the user having coupon codes
     *
     * @created By - Shobana Chandrasekar
     *
     * @updated_by - -
     *
     * @param object $request - User details, livevideo details
     *
     * @return response of coupon details with amount
     *
     */
    public function apply_coupon_live_videos(Request $request) {

        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required|exists:coupons,coupon_code',  
            'live_video_id'=>'required|exists:live_videos,id'          
        ], array(
            'coupon_code.exists' => tr('coupon_code_not_exists'),
            'live_video_id.exists' => tr('livevideo_not_exists'),
        ));
        
        if ($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            $response_array = array('success' => false, 'error_messages'=>$error_messages , 'error_code' => 101);

            return response()->json($response_array);
        }
        

        $model = Coupon::where('coupon_code', $request->coupon_code)->first();

        if ($model) {

            if ($model->status) {

                $user = User::find($request->id);

                if(strtotime($model->expiry_date) >= strtotime(date('Y-m-d'))) {

                    $check_coupon = $this->check_coupon_applicable_to_user($user, $model)->getData();

                    if ($check_coupon->success) {

                        if(strtotime($model->expiry_date) >= strtotime(date('Y-m-d'))) {

                            $live_video = LiveVideo::find($request->live_video_id);

                            if($live_video) {

                                if($live_video->status == VIDEO_STREAMING_ONGOING) {

                                    $amount_convertion = $model->amount;

                                    if ($model->amount_type == PERCENTAGE) {

                                        $amount_convertion = round(amount_convertion($model->amount, $live_video->amount), 2);

                                    }

                                    if ($live_video->amount > $amount_convertion && $amount_convertion > 0) {

                                        $amount = $live_video->amount - $amount_convertion;

                                        $response_array = ['success'=> true, 
                                        'data'=>[
                                            'remaining_amount'=>(string) $amount,
                                            'coupon_amount'=> (string) $amount_convertion,
                                            'coupon_code'=>$model->coupon_code,
                                            'original_coupon_amount'=>(string) ($model->amount_type == PERCENTAGE ? $model->amount.'%' : Setting::get('currency').$model->amount)
                                        ]];


                                    } else {

                                        // $response_array = ['success'=> false, 'error_messages'=>Helper::get_error_message(156), 'error_code'=>156];
                                        $amount = 0;
                                        $response_array = ['success'=> true, 
                                        'data'=>[
                                            'remaining_amount'=>(string) $amount,
                                            'coupon_amount'=> (string) $amount_convertion,
                                            'coupon_code'=>$model->coupon_code,
                                            'original_coupon_amount'=>(string) ($model->amount_type == PERCENTAGE ? $model->amount.'%' : Setting::get('currency').$model->amount)
                                        ]];

                                    }

                                } else {

                                    $response_array = ['success'=> false, 'error_messages'=>tr('streaming_stopped')];

                                }

                            } else {

                                $response_array = ['success'=> false, 'error_messages'=>Helper::error_message(173), 'error_code'=>173];
                            }

                        } else {

                            $response_array = ['success'=> false, 'error_messages'=>Helper::error_message(906), 'error_code'=>906];

                        }

                    } else {

                        $response_array = ['success'=> false, 'error_messages'=>$check_coupon->error_messages];
                    }

                } else {

                    $response_array = ['success'=> false, 'error_messages'=>Helper::error_message(906), 'error_code'=>906];

                }

            } else {

                $response_array = ['success'=> false, 'error_messages'=>Helper::error_message(168), 'error_code'=>168];
            }



        } else {

            $response_array = ['success'=> false, 'error_messages'=>Helper::error_message(167), 'error_code'=>167];

        }

        return response()->json($response_array);

    }  

    /**
     * Function Name : apply_coupon_vod_videos()
     *
     * Apply coupon to PPV if the user having coupon codes
     *
     * @created By - Shobana Chandrasekar
     *
     * @updated_by - -
     *
     * @param object $request - User details, ppv video details
     *
     * @return response of coupon details with amount
     *
     */
    public function apply_coupon_vod_videos(Request $request) {

        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required|exists:coupons,coupon_code',  
            'video_id'=>'required|exists:vod_videos,id,publish_status,'.VIDEO_PUBLISHED.',admin_status,'.VOD_APPROVED_BY_USER.',status,'.VOD_APPROVED_BY_ADMIN          
        ], array(
                'coupon_code.exists' => tr('coupon_code_not_exists'),
                'video_id.exists' => tr('video_not_exists'),
            ));
        
        if ($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            $response_array = array('success' => false, 'error_messages'=>$error_messages , 'error_code' => 101);

            return response()->json($response_array);
        }
        
        $model = Coupon::where('coupon_code', $request->coupon_code)->first();

        if ($model) {

            if ($model->status) {

                $user = User::find($request->id);

                if(strtotime($model->expiry_date) >= strtotime(date('Y-m-d'))) {

                    $vod_video = VodVideo::where('id', $request->video_id)->first();

                    $check_coupon = $this->check_coupon_applicable_to_user($user, $model)->getData();

                    if ($check_coupon->success) {

                        if(strtotime($model->expiry_date) >= strtotime(date('Y-m-d'))) {

                            $amount_convertion = $model->amount;

                            if ($model->amount_type == PERCENTAGE) {

                                $amount_convertion = round(amount_convertion($model->amount, $vod_video->amount), 2);

                            }

                            if ($vod_video->amount > $amount_convertion && $amount_convertion > 0) {

                                $amount = $vod_video->amount - $amount_convertion;

                                $response_array = ['success'=> true, 'data'=>[
                                    'remaining_amount'=>$amount,
                                    'coupon_amount'=>$amount_convertion,
                                    'coupon_code'=>$model->coupon_code,
                                    'original_coupon_amount'=> $model->amount_type == PERCENTAGE ? $model->amount.'%' : Setting::get('currency').$model->amount
                                    ]];

                            } else {

                                $amount = $vod_video->amount - $amount_convertion;

                                $response_array = ['success'=> true, 'data'=>[
                                    'remaining_amount'=>0,
                                    'coupon_amount'=>$amount_convertion,
                                    'coupon_code'=>$model->coupon_code,
                                    'original_coupon_amount'=> $model->amount_type == PERCENTAGE ? $model->amount.'%' : Setting::get('currency').$model->amount
                                    ]];

                            }
                           

                        } else {

                            $response_array = ['success'=> false, 'error_messages'=>Helper::error_message(173), 'error_code'=>173];

                        }

                    } else {

                        $response_array = ['success'=> false, 'error_messages'=>$check_coupon->error_messages];

                    }
                } else {

                    $response_array = ['success'=> false, 'error_messages'=>Helper::error_message(173), 'error_code'=>173];

                }

            } else {

                $response_array = ['success'=> false, 'error_messages'=>Helper::error_message(168), 'error_code'=>168];
            }            

        } else {

            $response_array = ['success'=> false, 'error_messages'=>Helper::error_message(167), 'error_code'=>167];

        }

        return response()->json($response_array);

    }

    /**
     * Function Name : pages_list()
     *
     * To get all the static pages
     *
     * @created By - Shobana Chandrasekar
     *
     * @updated_by - -
     *
     * @param object $request - -
     *
     * @return response pageslist
     *
     */
    public function pages_list() {

        $all_pages = Page::select('id', 'type', 'title', 'heading')->get()->toArray();

        $all_pages = count($all_pages) > 0 ? array_chunk($all_pages, 4) : [];

        $all_pages = ['success'=>true, 'data'=>$all_pages];

        return response()->json($all_pages, 200);

    }

    /**
     * Function Name : pages_view()
     *
     * To get page based on the paricular id
     *
     * @created By - Shobana Chandrasekar
     *
     * @updated_by - -
     *
     * @param object $request - User details, ppv video details
     *
     * @return response of page view
     *
     */
    public function pages_view(Request $request) {

        $page = Page::where('id', $request->page_id)->first();

        $page = ['success'=>true, 'data'=>$page];

        return response()->json($page, 200);

    }

    /**
     * Function Name : check_social()
     *
     * To check social logins exists or not
     *
     * @created_by - Shobana Chandrasekar
     *
     * @updated_by - - 
     *
     * @param -
     * 
     * @return resonse of boolean
     *
     */
    public function check_social() {

        $facebook_client_id = envfile('FB_CLIENT_ID');
        $facebook_client_secret = envfile('FB_CLIENT_SECRET');
        $facebook_call_back = envfile('FB_CALL_BACK');

        $google_client_id = envfile('GOOGLE_CLIENT_ID');
        $google_client_secret = envfile('GOOGLE_CLIENT_SECRET');
        $google_call_back = envfile('GOOGLE_CALL_BACK');

        $fb_status = false;

        if (!empty($facebook_client_id) && !empty($facebook_client_secret) && !empty($facebook_call_back)) {

            $fb_status = true;

        }

        $google_status = false;

        if (!empty($google_client_id) && !empty($google_client_secret) && !empty($google_call_back)) {

            $google_status = true;

        }

        return response()->json(['fb_status'=>$fb_status, 'google_status'=>$google_status]);
    
    }

    /**
     * Function name : vod_invoice()
     *
     * To get particualr video details based on the vod id
     *
     * @param object $request - User id, token, video id
     *
     * @return vod details
     */
    public function vod_invoice(Request $request) {

        try {

            $currency = Setting::get('currency');

            if($this->device_type == DEVICE_WEB) {

                $video_details = VodVideo::where('unique_id', $request->video_id)->first();

                $video_id = $video_details ? $video_details->id : 0;

                $request->request->replace(['video_id' => $video_id]);
            }

            $validator = Validator::make(
                $request->all(), array(
                    'video_id'=>'required|exists:vod_videos,id,status, '.VOD_APPROVED_BY_USER.',admin_status,'.VOD_APPROVED_BY_ADMIN.',publish_status,'.VIDEO_PUBLISHED.',is_pay_per_view,'.PPV_ENABLED,
            ));

            if ($validator->fails()) {
                // Error messages added in response for debugging
                $error_messages = implode(',',$validator->messages()->all());

                throw new Exception($error_messages, 101);                

            } else {

                $model = VodVideo::select('user_id as id', 'id as vod_id', 'unique_id',
               'title', 'description', 'amount', 
                'image', 'type_of_user', 'type_of_subscription',
                    DB::raw('DATE_FORMAT(vod_videos.created_at , "%e %b %y") as date'),
                    DB::raw("'$currency' as currency"))
                    ->where('id',$request->video_id)->first();
                
                if ($model) {

                    $type_of_user = "";

                    if ($model->type_of_user == NORMAL_USER) {

                        $type_of_user = tr('normal_users');

                    } else if($model->type_of_user == PAID_USER) {

                        $type_of_user = tr('paid_users');

                    } else if($model->type_of_user == BOTH_USERS) {

                        $type_of_user = tr('both_users');
                    }

                    $type_of_subscription = "";
                    
                    if ($model->type_of_subscription == ONE_TIME_PAYMENT) {

                        $type_of_subscription = tr('one_time_payment');

                    } else if($model->type_of_subscription == RECURRING_PAYMENT) {

                        $type_of_subscription = tr('recurring_payment');

                    }

                    $model['type_of_user'] = $type_of_user;

                    $model['type_of_subscription'] = $type_of_subscription;
                    
                    $response_array = ['success'=>true, 'data'=>$model];

                } else {

                    throw new Exception(tr('no_vod_video_found'));

                }

            }

            return $response_array;

        } catch(Exception $e) {

            $message = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages'=>$message, 'error_code'=>$code];

            return $response_array;
        }
    
    }


    /**
     * Function name : site_settings()
     *
     * To list out all the home page settings
     * 
     * @created_by shobana
     *
     * @param object $request - User id, token (optional)
     *
     * @return site settings details
     */
    public function site_settings() {

        $settings = Settings::get();

        $home_bg = Setting::get('home_bg_image');

        $status = false;

        if ($home_bg) {

            $extension = pathinfo($home_bg)['extension'];

            if ($extension == 'jpg' || $extension == 'png' || $extension == 'jpeg') {

                $status = true;

            }

        }

        $data = ['settings'=>$settings, 'pathinfo'=>$status];

        return response()->json($data, 200); 
    
    }


    /**
     * Function name : user_view()
     *
     * To view the particualr user details using id
     * 
     * @created_by shobana
     *
     * @param object $request - User id, token (optional)
     *
     * @return user details
     */
    public function user_view(Request $request) {

        $user = User::find($request->id);

        if ($user) {

            $response_array = ['success'=>true, 'data'=>$user];

            return response()->json($response_array, 200); 

        } else {

            $response_array = ['success'=>false, 'error_messages'=>tr('user_not_found')];

            return response()->json($response_array, 200); 

        }

    }


    /**
     * Function Name : live_groups_index()
     *
     * Usage: used to list all the groups owned or joined groups
     *
     * @created Shobana 
     *
     * @updated Shobana
     *
     * @param object id, token and type
     *
     * @param type = owned , joined or all
     *
     * @return json response of the user
     */
    public function live_groups_index(Request $request) {

        try {
            
            $groups_query = LiveGroup::where('live_groups.status' , LIVE_GROUP_APPROVED);

            // To display owned groups by the login user

            if($request->type == "owned") {

                $groups_query = $groups_query->where('live_groups.user_id' , $request->id);

            } else if($request->type == "joined") { 

                // To display joined groups by the login user

                $groups_query = $groups_query->leftJoin('live_group_members' , 'live_groups.id' , '=', 'live_group_members.live_group_id' )->where('live_group_members.member_id' , $request->id);

            } else {

                // type== "all" => To all means owned & groups by the login user

                $groups_query = $groups_query->where('live_groups.user_id' , $request->id)
                        ->leftJoin('live_group_members' , 'live_groups.id' , '=', 'live_group_members.live_group_id' )
                        ->orWhere('live_group_members.member_id' , $request->id);

            }

            $groups = $groups_query->baseResponse()->groupBy('live_groups.id')->get();
           
            \Log::info('Response array'.print_r($groups->toArray() , true));

            $groups_data = [];

            foreach ($groups as $key => $group_details) {

                $group_details->total_members = LiveGroupMember::where('live_group_id' , $group_details->live_group_id)->count();

                $group_details->is_owner = $request->id == $group_details->owner_id ? LIVE_GROUP_OWNER_YES : LIVE_GROUP_OWNER_NO;

                $group_details->created_at = common_date($group_details->created_at,$this->timezone,'Y-m-d H:i:s');

                $group_details->updated_at = common_date($group_details->updated_at,$this->timezone,'Y-m-d H:i:s');
                
                array_push($groups_data, $group_details);
                
            }

            $data['groups'] = $groups_data;

            $data['total_groups'] = $groups->count();

            $response_array = ['success' => true , 'data' => $data];

            return response()->json($response_array,200);

        } catch(Exception $e) {

            $error_message = $e->getMessage();

            $error_code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages' => $error_message , 'error'=> $error_message , 'error_code' => $error_code];

            return response()->json($response_array);

        }

    }


    /**
     * Function Name: live_groups_save()
     *
     * Usage: store/update the group details
     *
     * @created Shobana
     *
     * @updated Shobana
     *
     * @param Form data
     * 
     * @return JSON Response
     */

    public function live_groups_save(Request $request) {

        DB::beginTransaction();

        try {

            $validator = Validator::make($request->all(),
                        array(
                            'live_group_id' => 'exists:live_groups,id,user_id,'.$request->id,
                            'name' => 'required|min:2|max:100',
                            'description' => 'max:255',
                            'picture' => 'mimes:jpeg,bmp,png|required_if:live_group_id,==,""',
                        ),
                        array(
                            'picture.required_if' => 'Please upload picture for group',
                            'picture.mimes' => 'Please choose proper images (jpeg,jpg or png)',

                        )
                    );

            if ($validator->fails()) {

               $error = implode(',', $validator->messages()->all());

               throw new Exception($error, 101);
               
            } else {   

                if(!$request->live_group_id) {

                    $group_details = new LiveGroup;

                    $group_details->created_by = USER;

                    $success_code = 130;

                } else {

                    $group_details = LiveGroup::where('id' , $request->live_group_id)->where('user_id' , $request->id)->first(); 

                    $group_details->created_by = $group_details->created_by ? $group_details->created_by : USER;

                    if($request->file('picture')) {

                        Helper::delete_avatar('uploads/users',$group_details->picture); // Delete the old pic

                    }

                    $success_code = 131;

                }

                $group_details->user_id = $request->id;

                $group_details->name = $request->name ? $request->name : "";

                $group_details->description = $request->description ? $request->description : "";

                if($request->file('picture')) {

                    $group_details->picture = Helper::upload_avatar('uploads/images', $request->file('picture'), 0);
                }

                $group_details->status = LIVE_GROUP_APPROVED;

                if($group_details->save()) {

                    $message = Helper::get_message($success_code);

                    $response_array = ['success' => true , 'message' => $message , 'code' => $success_code, 'group_id'=>$group_details->id];

                } else {

                    throw new Exception(Helper::error_message(907), 907);
                
                }

            }

            DB::commit();
        
            return response()->json($response_array,200); 

        } catch(Exception $e) {

            DB::rollback();

            $error_message = $e->getMessage();

            $error_code = $e->getCode();

            $response_array = ['success'=>false, 'error'=> $error_message ,'error_messages' => $error_message, 'error_code' => $error_code];

            return response()->json($response_array);
        
        }
    
    }

    /**
     * Function Name : live_groups_view()
     *
     * Usage: used to get the selected group details
     *
     * @created Shobana 
     *
     * @updated Shobana
     *
     * @param integer live_group_id
     *
     * @return json response
     */
    
    public function live_groups_view(Request $request) {

        try {
    
            $validator = Validator::make($request->all(),
                    array(
                        'live_group_id' => 'required|integer|exists:live_groups,id',
                    ),
                    array(
                        'exists' => Helper::error_message(908)
                    )
                );

            if ($validator->fails()) {

               $error = implode(',', $validator->messages()->all());

               throw new Exception($error, 101);
               
            } else {

                $group_details = LiveGroup::where('live_groups.id',$request->live_group_id)->baseResponse()->first();

                if(!$group_details) {

                    throw new Exception(Helper::error_message(908), 908);
                    
                }

                /**
                 | To prevent other member accessing the group
                 | 
                 | Case 1: check the login user is member or owner
                 |
                 | Case 2: If Member - check the login user is the member of the group
                 |
                 | Case 3: If Member - check the groups approve decline status
                 */

                // Case 1: check the login user is member or owner

                if($request->id != $group_details->owner_id) {

                    // Case 2: If Member - check the login user is the member of the group

                    $check_member = LiveGroupMember::where('live_group_id' , $request->live_group_id)->where('member_id', $request->id)->count();

                    if(!$check_member) {

                        throw new Exception(Helper::error_message(909), 909);
                    }

                    // Case 3: If Member - check the groups approve decline status

                    if($group_details->live_group_status != LIVE_GROUP_APPROVED) {

                        throw new Exception(Helper::error_message(909), 909);
                        
                    }
                }

                $members = LiveGroupMember::where('live_group_id' , $request->live_group_id)->commonResponse()->get();

                $member_data = [];

                foreach ($members as $key => $member_details) {

                    // check the member is streaming from this group

                    $check_is_user_live = LiveVideo::where("is_streaming", DEFAULT_TRUE)

                        ->where('status', DEFAULT_FALSE)

                        ->where('type', TYPE_PUBLIC)

                        ->where('live_group_id' , $request->live_group_id)

                        ->where('user_id',$member_details->member_id)

                        ->count();

                    $member_details->is_userLive = $check_is_user_live ? true : false;

                    array_push($member_data, $member_details);
                }

                $group_details->total_members = $members->count();

                $group_details->total_members_formatted = members_text($group_details->total_members);

                $group_details->is_owner = $request->id == $group_details->owner_id ? LIVE_GROUP_OWNER_YES : LIVE_GROUP_OWNER_NO;
                 
               
                $data['group_details'] = $group_details;

                $data['members'] = $member_data;
                

                $response_array = ['success' => true , 'data' => $data];

            }

            return response()->json($response_array,200);

        } catch(Exception $e) {

            $error_message = $e->getMessage();

            $error_code = $e->getCode();

            $response_array = ['success'=>false, 'error'=> $error_message , 'error_messages' => $error_message, 'error_code' => $error_code];

            return response()->json($response_array);

        }

    }


    /**
     * Function Name : live_groups_delete()
     *
     * Usage: used to delete the selected group 
     *
     * @created Shobana 
     *
     * @updated Shobana
     *
     * @param integer live_group_id
     *
     * @return json response
     */
    public function live_groups_delete(Request $request) {

        try {

            $validator = Validator::make($request->all(),
                    array(
                        'live_group_id' => 'required|integer|exists:live_groups,id,user_id,'.$request->id,
                    ),
                    array(
                        'exists' => Helper::error_message(908)
                    )
                );

            if ($validator->fails()) {

               $error = implode(',', $validator->messages()->all());

               throw new Exception($error, 101);
               
            } else {   

                $group_details = LiveGroup::where('id',$request->live_group_id)->first();

                if(!$group_details) {

                    throw new Exception(Helper::error_message(908), 908);
                    
                }

                if($group_details->user_id != $request->id) {
                    throw new Exception(Helper::error_message(909), 909);
                }

                if($group_details->delete()) {

                    $response_array = ['success' => true , 'message' => Helper::get_message(132) , 'code' => 132];

                } else {

                    throw new Exception(Helper::error_message(907), 907);
                }

            }

            return response()->json($response_array,200);

        } catch(Exception $e) {

            $error_message = $e->getMessage();

            $error_code = $e->getCode();

            $response_array = ['success'=>false, 'error'=> $error_message , 'error_messages' => $error_message, 'error_code' => $error_code];

            return response()->json($response_array);

        }

    }


    /**
     *
     * Function Name : live_groups_members()
     *
     * Usage: used to get the members list based on the selected group details
     *
     * @created Shobana 
     *
     * @updated Shobana
     *
     * @param integer live_group_id
     *
     * @return json response
     */
    public function live_groups_members(Request $request) {

        try {

            $validator = Validator::make($request->all(),
                    array(
                        'live_group_id' => 'required|integer|exists:live_groups,id',
                    ),
                    array(
                        'exists' => Helper::error_message(908)
                    )
                );

            if ($validator->fails()) {

               $error = implode(',', $validator->messages()->all());

               throw new Exception($error, 101);
               
            } else {   

                $group_details = LiveGroup::where('live_groups.id',$request->live_group_id)->baseResponse()->first();

                if(!$group_details) {

                    throw new Exception(Helper::error_message(908), 908);
                    
                }

                // Check the group details request accessing by owner

                if($request->id != $group_details->owner_id) {

                    if($group_details->live_group_status != LIVE_GROUP_APPROVED) {

                        throw new Exception(Helper::error_message(909), 909);
                        
                    }

                }

                $members = LiveGroupMember::where('live_group_id' , $request->live_group_id)->commonResponse()->get();

                $data['group_details'] = $group_details;

                $data['members'] = $members;

                $response_array = ['success' => true , 'data' => $data];

            }

            return response()->json($response_array,200);

        } catch(Exception $e) {

            $error_message = $e->getMessage();

            $error_code = $e->getCode();

            $response_array = ['success'=>false, 'error'=> $error_message , 'error_messages' => $error_message , 'error_code' => $error_code];

            return response()->json($response_array);

        }

    }

    /**
     * Function Name : live_groups_members_add()
     *
     * Usage: used to add a member to the selected group
     *
     * @created Shobana 
     *
     * @updated Shobana
     *
     * @param integer live_group_id
     *
     * @param integer member_id
     *
     * @return json response
     */
    public function live_groups_members_add(Request $request) {

        \Log::info("live_groups_members_add".print_r($request->all() , true));

        try {

            $validator = Validator::make($request->all(),
                    array(
                        'live_group_id' => 'required|integer|exists:live_groups,id',
                        'member_id' => 'required|exists:users,id',
                    ),
                    array(
                        'live_group_id.exists' => Helper::error_message(908),
                        'member_id.exists' => Helper::error_message(910),
                        'member_id.required' => Helper::error_message(910)
                    )
                );

            if ($validator->fails()) {

               $error = implode(',', $validator->messages()->all());

               throw new Exception($error, 101);
               
            } else {   

                $group_details = LiveGroup::where('id',$request->live_group_id)->first();

                if(!$group_details) {

                    throw new Exception(Helper::error_message(908), 908);
                    
                }

                /**
                 | CASE 1: Owner can't join to their group 
                 |
                 | Case 2: Owner only can add members to the group. The Other member of the group can't members
                 |
                 |
                 |
                */

                // CASE 1: Owner can't join to their group

                if($request->id == $group_details->user_id && $request->member_id == $group_details->user_id) {

                    throw new Exception(Helper::error_message(914), 914);
                    
                }

                // Case 2: Owner only can add members to the group. The Other member of the group can't members

                if($request->id != $group_details->user_id && $request->id != $request->member_id) {
                   
                    throw new Exception(Helper::error_message(915), 915);
                }

                $member_details = User::find($request->member_id);

                if($member_details->status != DEFAULT_TRUE) {

                    throw new Exception(Helper::error_message(911), 911);
                    
                }

                // Check the member added in the selected group

                $group_member_count = LiveGroupMember::where('member_id' , $request->member_id)->where('live_group_id' , $request->live_group_id)->count();

                if($group_member_count) {

                    throw new Exception(Helper::error_message(912), 912);

                }

                $group_member_details = new LiveGroupMember;
                $group_member_details->live_group_id = $request->live_group_id;
                $group_member_details->owner_id = $group_details->user_id;
                $group_member_details->member_id = $request->member_id;
                $group_member_details->status = 1;
                $group_member_details->added_by = $request->id == $group_details->user_id ? 'owner' : 'joined';

                if($group_member_details->save()) {

                    // Save Notification

                    $groupUserDetails = User::find($group_details->user_id);

                    $notification = NotificationTemplate::getRawContent(USER_GROUP_ADD, $groupUserDetails);

                    $content = $notification ? $notification : USER_GROUP_ADD;

                    UserNotification::save_notification($request->member_id, $content, $request->live_group_id, USER_GROUP_ADD , $request->id);

                    $response_array = ['success' => true , 'message' => Helper::get_message(133 , $member_details->username ? $member_details->username : "user") , 'code' => 133 , 'is_member' => LIVE_GROUP_MEMBER_YES];

                } else {

                    throw new Exception(Helper::error_message(907), 907);
                }

            }

            return response()->json($response_array,200);

        } catch(Exception $e) {

            $error_message = $e->getMessage();

            $error_code = $e->getCode();

            $response_array = ['success'=>false, 'error'=> $error_message ,'error_messages' => $error_message, 'error_code' => $error_code];

            return response()->json($response_array);

        }

    }


    /**
     * Function Name : live_groups_members_remove()
     *
     * Usage: used to remove a member to the selected group
     *
     * @created Shobana 
     *
     * @updated Shobana
     *
     * @param integer live_group_id
     *
     * @param integer member_id
     *
     * @return json response
     */
    public function live_groups_members_remove(Request $request) {

        Log::info("live_groups_members_remove".print_r($request->all() , true));

        try {

            $validator = Validator::make($request->all(),
                    array(
                        'live_group_id' => 'required|integer|exists:live_groups,id',
                        'member_id' => 'required|integer|exists:users,id',
                    ),
                    array(
                        'live_group_id.exists' => Helper::error_message(908),
                        'users.exists' => Helper::error_message(910)
                    )
                );

            if ($validator->fails()) {

               $error = implode(',', $validator->messages()->all());

               throw new Exception($error, 101);
               
            } else {   

                $group_details = LiveGroup::where('id',$request->live_group_id)->first();

                if(!$group_details) {

                    throw new Exception(Helper::error_message(908), 908);
                    
                }

                /**
                 | CASE 1: Owner can't remove by their own 
                 |
                 | Case 2: Owner only can remove members to the group and the member themself left from the group
                 |
                */

                // CASE 1: Owner can't remove by their own 

                if($request->id == $group_details->user_id && $request->member_id == $group_details->user_id) {

                    throw new Exception(Helper::error_message(914), 914);
                    
                }

                // Case 2: Owner only can remove members to the group and the member themself left from the group

                if($request->id != $group_details->user_id && $request->id != $request->member_id) {

                    throw new Exception(Helper::error_message(916), 916);
  
                }

                $member_details = User::find($request->member_id);

                // Check the member added in the selected group

                $group_member_details = LiveGroupMember::where('member_id' , $request->member_id)->where('live_group_id' , $request->live_group_id)->first();

                if(!$group_member_details) {

                    throw new Exception(Helper::error_message(913), 913);

                }

                if($group_member_details->delete()) {

                    $response_array = ['success' => true , 'message' => Helper::get_message(134 , $member_details->username ? $member_details->username : "user") , 'code' => 134];

                } else {

                    throw new Exception(Helper::error_message(907), 907);
                }

            }

            return response()->json($response_array,200);

        } catch(Exception $e) {

            $error_message = $e->getMessage();

            $error_code = $e->getCode();

            $response_array = ['success'=>false, 'error'=> $error_message , 'error_messages' => $error_message , 'error_code' => $error_code];

            return response()->json($response_array);

        }

    }

    /**
     * Function : custom_live_videos()
     *
     * @uses used to return list of live videos
     *
     * @created Shobana
     *
     * @updated vidhya
     *
     * @param integer custom_live_video_id
     *
     * @return JSON Response
     */
    public function custom_live_videos(Request $request) {

        $query = CustomLiveVideo::liveVideoResponse()->orderBy('custom_live_videos.created_at', 'desc');

        if ($request->has('custom_live_video_id')) {

            $query->whereNotIn('custom_live_videos.id', [$request->custom_live_video_id]);

        }

        // Used to check whether logged in user live videos or other user live videos

        if ($request->type == 'owned') {

            $query->where('custom_live_videos.user_id', $request->id);

        } else {

            // Blocked Users

            $blocked_user_ids = BlockList::where('user_id', $request->id)->get()->pluck('block_user_id')->toArray();

            array_push($blocked_user_ids, $request->id);

            $query->where('custom_live_videos.status', APPROVED)->whereNotIn('custom_live_videos.user_id' , $blocked_user_ids);

        }

        $take = $request->has('take') ? $request->take : Setting::get('admin_take_count' ,12);

        $custom_live_videos = $query->skip($request->skip)->take($take)->get();

        $response = [];

        foreach ($custom_live_videos as $key => $custom_live_video_details) {

            $check_follow = Follower::where('follower', $request->id)
                                ->where('user_id', $custom_live_video_details->user_id)
                                ->first();

            $follower_is_follow = DEFAULT_FALSE;

            if($check_follow) {

                $follower_is_follow = DEFAULT_TRUE;

            }
            
            if ($request->id == $custom_live_video_details->user_id) {

                $follower_is_follow = -1; // Same user

            }

            $no_of_followers = Follower::where('user_id', $custom_live_video_details->user_id)->count();

            $share_link = Setting::get('ANGULAR_URL').'live-tv/view?id='.$custom_live_video_details->custom_live_video_id;

            $response[] = [
                    'custom_live_video_id' => $custom_live_video_details->custom_live_video_id, 
                    'title' => $custom_live_video_details->title,
                    'image' => $custom_live_video_details->image,
                    'user_id' => $custom_live_video_details->user_id,
                    'user_name' => $custom_live_video_details->user_name,
                    'user_picture' => $custom_live_video_details->user_picture,
                    'description' => $custom_live_video_details->description,
                    'is_follow' => $follower_is_follow,
                    'no_of_followers' => $no_of_followers ? $no_of_followers : 0,
                    'hls_video_url' => $custom_live_video_details->hls_video_url,
                    'rtmp_video_url' => $custom_live_video_details->rtmp_video_url,
                    'created_date' => $custom_live_video_details->created_date,
                    'category_name' => $custom_live_video_details->category_name,
                    'viewer_count' => "",
                    'share_link' => $share_link
            ];
        
        }

        $response_array = ['success' => true , 'data' => $response];

        return response()->json($response_array , 200);

    }

    /**
     * Function Name : custom_live_videos_search()
     *
     * To search and display custom live videos based on the key
     *
     * @param object $request - User id, token
     *
     * @return response of json
     */
    public function custom_live_videos_search(Request $request) {

        $validator = Validator::make(
            $request->all(),
            array(
                'term' => 'required',
            ),
            array(
                'exists' => 'The :attribute doesn\'t exists',
            )
        );
    
        if ($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            $response_array = array('success' => false, 'error_messages' => $error_messages, 'error_code' => 101);

        } else {

            $list = [];

            $q = $request->term;

            $take = $request->take ? $request->take : Setting::get('admin_take_count');

            $query = CustomLiveVideo::liveVideoResponse()->orderBy('custom_live_videos.created_at', 'desc')
                    ->where('custom_live_videos.title', 'like', "%".$q."%");

            if ($request->has('custom_live_video_id')) {

                $query->whereNotIn('custom_live_videos.id', [$request->custom_live_video_id]);

            }

            if ($request->type == 'owned') {

                $query->where('custom_live_videos.user_id', $request->id);

            } else {

                $query->where('custom_live_videos.status', APPROVED);

            }

            $take = $request->has('take') ? $request->take : Setting::get('admin_take_count' ,12);

            $model = $query->skip($request->skip)->take($take)->get();

            $response = [];

            foreach ($model as $key => $value) {

                $model = Follower::where('follower', $request->id)

                        ->where('user_id', $value->user_id)->first();

                $follower_is_follow = DEFAULT_FALSE;

                if($model) {

                    $follower_is_follow = DEFAULT_TRUE;

                }
                
                if ($request->id == $value->user_id) {

                    $follower_is_follow = -1;

                }

                $no_of_followers = Follower::where('user_id', $value->user_id)->count();

                $share_link = Setting::get('ANGULAR_URL').'live-tv/view?id='.$value->custom_live_video_id;

                $response[] = [
                        'custom_live_video_id'=>$value->custom_live_video_id, 
                        'title'=>$value->title,
                        'user_name'=>$value->user_name,
                        'user_picture'=>$value->user_picture,
                        'description'=>$value->description,
                        'is_follow'=>$follower_is_follow,
                        'no_of_followers'=>$no_of_followers ? $no_of_followers : 0,
                        'hls_video_url'=>$value->hls_video_url,
                        'rtmp_video_url'=>$value->rtmp_video_url,
                        'image'=>$value->image,
                        'user_id'=>$value->user_id,
                        'created_date'=>$value->created_date,
                        'category_name'=>$value->category_name,
                        'viewer_count'=>"",
                        'share_link'=>$share_link
                ];
            
            }

            $response_array = ['success' => true , 'data' => $response];

        }   
    
        return response()->json($response_array, 200);

    }

    /**
     * Function : custom_live_videos_view()
     *
     * @uses used to get selected video details
     *
     * @created Shobana
     *
     * @updated vidhya
     *
     * @param integer custom_live_video_id
     *
     * @return JSON Response
     */
    public function custom_live_videos_view(Request $request) {

        try {

            $custom_live_video_details = CustomLiveVideo::where('custom_live_videos.id', $request->custom_live_video_id)
                        ->where('custom_live_videos.status' , APPROVED)
                        ->liveVideoResponse()->first();

            if ($custom_live_video_details) {

                $check_follow = Follower::where('follower', $request->id)
                                ->where('user_id', $custom_live_video_details->user_id)
                                ->first();

                $follower_is_follow = DEFAULT_FALSE;

                if($check_follow) {

                    $follower_is_follow = DEFAULT_TRUE;

                }
                
                if ($request->id == $custom_live_video_details->user_id) {

                    $follower_is_follow = -1; // Same user

                }

                $custom_live_video_details->is_follow = $follower_is_follow;

                $suggestions = CustomLiveVideo::where('custom_live_videos.id','!=', $request->custom_live_video_id)
                            ->where('custom_live_videos.status' , APPROVED)
                            ->liveVideoResponse()->get();

                $custom_live_video_details->share_link = Setting::get('ANGULAR_URL').'live-tv/view?id='.$custom_live_video_details->custom_live_video_id;

                $response_array = ['success' => true, 'data' => $custom_live_video_details , 'suggestions'  =>  $suggestions];

            } else {

                throw new Exception(tr('custom_live_video_not_found'), 101);
           
            }

            return response()->json($response_array,200);

        } catch(Exception $e) {

            $error = $e->getMessage();

            $error_code = $e->getCode();

            $response_array = ['success' => false , 'error' => $error , 'error_code' => $error_code];

            return response()->json($response_array , 200);
        }

    } 


    /**
     * Function : custom_live_videos_save()
     *
     * @created_by shobana
     *
     * @updated_by -
     *
     * @return Save the form data of the live video
     */
    public function custom_live_videos_save(Request $request) {
       
        $request->request->add([

            'user_id'=>$request->id,
            'id'=>$request->live_video_id

        ]);

        $response = CommonRepo::save_custom_live_video($request)->getData();

        if($response->success) {

            $response_array = ['success'=>true, 'message'=>$response->message];

        } else {

            $response_array = ['success' => false, 'error_messages' => $response->message];

        }

        return response()->json($response_array,200);

    }

    /**
     * Function : custom_live_videos_change_status()
     *
     * @created_by shobana
     *
     * @updated_by -
     *
     * @return Update the status of the live video.
     */
    public function custom_live_videos_change_status(Request $request) {

        $model = CustomLiveVideo::where('id',$request->custom_live_video_id)->where('user_id', $request->id)->first();

        if(!$model) {

            return response()->json(['success'=>false, 'error_messages'=>tr('custom_live_video_not_found')]);
        }

        $model->status = $model->status ?  DEFAULT_FALSE : DEFAULT_TRUE;

        $model->save();

        if($model->status ==1) {

            $message = tr('live_custom_video_approved_success');

        } else {

            $message = tr('live_custom_video_declined_success');

        }

        return response()->json(['success'=>true, 'message'=>$message]);

    }

    /**
     * Function : custom_live_videos_delete()
     *
     * @created_by shobana
     *
     * @updated_by -
     *
     * @return delete the selected record
     */
    public function custom_live_videos_delete(Request $request) {
        
        $model = CustomLiveVideo::where('id',$request->custom_live_video_id)->where('user_id', $request->id)->first();
        
        $image = "";

        if(!$model){

            return response()->json(['success'=>false, 'error_messages'=>tr('custom_live_video_not_found')]);

        } else {

            $image =  $model->image;

        }
        
        if ($model->delete()) {

            if($image) {

                Helper::storage_delete_file($model->image,LIVETV_IMAGE_PATH);

            }

            return response()->json(['success'=>true, 'error_messages'=>tr('live_custom_video_delete_success')]);   
        }
        
        return response()->json(['success'=>false, 'error_messages'=>tr('something_error')]);
    }

    /**
     * Function Name : search()
     *
     * @uses To search users, videos and custom live videos based on key word
     * 
     * @created - shobana
     *
     * @updated - shobana
     *
     * @param object $request - Key word
     *
     * @return response of details
     */
    public function search(Request $request) {

        try {

            $validator = Validator::make(
                $request->all(),
                array(
                    'term' => 'required',
                )
            );
        
            if ($validator->fails()) {

                $error = implode(',', $validator->messages()->all());

                throw new Exception($error, 101);
            
            } else {

                $data = [];

                // users list

                $users = [];


                $results = Helper::search_user($request->id, $request->term, $request->skip, 5);

                if(count($results)) {
                        
                    foreach ($results as $key => $suggestion) {

                       
                        // Blocked Users by You
                        $blockedUsersByYou = BlockList::where('user_id', $request->id)
                                ->where('block_user_id', $suggestion->id)->first();

                         // Blocked By Others
                        $blockedUsersByOthers = BlockList::where('user_id', $suggestion->id)
                                ->where('block_user_id', $request->id)->first();

                        if (!$blockedUsersByYou && !$blockedUsersByOthers) {

                            $model = Follower::where('follower', $request->id)->where('user_id', $suggestion->id)->first();

                            $is_follow = DEFAULT_FALSE;

                            if($model) {

                                $is_follow = DEFAULT_TRUE;

                            }

                            $no_of_followers = Follower::where('user_id', $suggestion->id)->count();

                            $users[] = [
                                'id'=>$request->id, 
                                'follower_id'=>$suggestion->id, 
                                'name'=> $suggestion->name, 
                                'description'=>$suggestion->description, 
                                'picture'=> $suggestion->picture, 
                                'is_follow'=>$is_follow,
                                'no_of_followers'=>$no_of_followers ? $no_of_followers : 0
                            ];

                        }

                    }
                
                }   

                $data[USERS]['term'] = $request->term;

                $data[USERS]['name'] = USERS;

                $data[USERS]['see_all_url'] = route('search.users');

                $data[USERS]['data'] = $users ? $users : [];

                // live videos List

                $live_videos = LiveVideo::videoResponse()
                            ->leftJoin('users', 'users.id', '=', 'live_videos.user_id')
                            ->where('title','like', '%'.$request->term.'%')
                            ->skip(0)
                            ->take(4)
                            ->where('live_videos.is_streaming', DEFAULT_TRUE)
                            ->where('live_videos.status', DEFAULT_FALSE)
                            ->get();


                $data[LIVE_VIDEOS]['term'] = $request->term;

                $data[LIVE_VIDEOS]['name'] = LIVE_VIDEOS;

                $data[LIVE_VIDEOS]['see_all_url'] = route('search.live_videos');

                $data[LIVE_VIDEOS]['data'] = $live_videos ? $live_videos : [];

                // custom live videos

                $live_tv = CustomLiveVideo::liveVideoResponse()
                            ->where('custom_live_videos.title','like', '%'.$request->term.'%')
                            ->skip(0)
                            ->take(4)
                            ->where('custom_live_videos.status', APPROVED)
                            ->get();

                $data[LIVE_TV]['term'] = $request->term;

                $data[LIVE_TV]['name'] = LIVE_TV;

                $data[LIVE_TV]['see_all_url'] = route('search.live_tv');

                $data[LIVE_TV]['data'] = $live_tv ? $live_tv : [];

                $response_array = [

                    'success' => true,

                    'code' => 200,

                    'data' => $data

                ];
                
            }

            return response()->json($response_array , 200);

        } catch(Exception $e) {

            $error = $e->getMessage();

            $error_code = $e->getCode();

            $response_array = ['success' => false , 'error' => $error , 'error_code' => $error_code];

            return response()->json($response_array , 200);
        }

    }


    /**
     * Function Name : searchDetails()
     * 
     * To search the other users, videos and live tv videos
     *
     * @param object $request - term that search
     *
     * @created by - shobana
     *
     * @updated by shobana 
     *
     * @return user list
     */
    public function searchDetails(Request $request) {

        try {

            $validator = Validator::make(
                $request->all(),
                array(
                    'term' => 'required',
                    'type'=>'in:'.LIVE_VIDEOS.','.USERS.','.LIVE_TV,
                )
            );
        
            if ($validator->fails()) {

                $error = implode(',', $validator->messages()->all());

                throw new Exception($error, 101);
            
            } else {

                switch ($request->type) {
                    
                    case LIVE_VIDEOS:

                        $lists = [];

                        $live_videos = LiveVideo::videoResponse()
                            ->leftJoin('users', 'users.id', '=', 'live_videos.user_id')
                            ->where('title','like', '%'.$request->term.'%')
                            ->skip($request->skip)
                            ->take(Setting::get('admin_take_count'))
                            ->where('live_videos.is_streaming', DEFAULT_TRUE)
                            ->where('live_videos.status', DEFAULT_FALSE)
                            ->get();

                        foreach ($live_videos as $key => $live_video_details) {

                            $is_blocked = check_blocked_status($request->id , $live_video_details->user_id);

                            if($is_blocked == NO) {

                                $live_video_details->share_link = Setting::get('ANGULAR_URL').'live-video/'.$live_video_details->video_id;

                                $live_video_details->is_follow = check_follow_status($request->id, $live_video_details->user_id);

                                $lists[] = $live_video_details;

                            }

                        }

                        break;

                    case USERS:

                        $lists = [];

                        $results = Helper::search_user($request->id, $request->term, $request->skip, Setting::get('admin_take_count'));

                        if(count($results)) {
                                
                            foreach ($results as $key => $suggestion) {

                                $is_blocked = check_blocked_status($request->id , $suggestion->id);

                                if ($is_blocked == NO) {

                                    $is_follow = check_follow_status($request->id, $suggestion->id);

                                    $no_of_followers = Follower::where('user_id', $suggestion->id)->count();

                                    $lists[] = [
                                        'id'=>$request->id, 
                                        'follower_id'=>$suggestion->id, 
                                        'name'=> $suggestion->name, 
                                        'description'=>$suggestion->description, 
                                        'picture'=> $suggestion->picture, 
                                        'is_follow'=>$is_follow,
                                        'no_of_followers'=>$no_of_followers ? $no_of_followers : 0
                                    ];

                                }

                            }
                        
                        }   

                        break;

                    case LIVE_TV:

                        $lists = [];

                        $custom_live_videos = CustomLiveVideo::liveVideoResponse()
                            ->where('custom_live_videos.title','like', '%'.$request->term.'%')
                            ->skip($request->skip)
                            ->take(Setting::get('admin_take_count'))
                            ->where('custom_live_videos.status', APPROVED)
                            ->get();

                        foreach ($custom_live_videos as $key => $custom_live_video_details) {

                            $is_blocked = check_blocked_status($request->id , $custom_live_video_details->user_id);

                            if ($is_blocked == NO) {

                                $share_link = Setting::get('ANGULAR_URL').'live-tv/view?id='.$custom_live_video_details->custom_live_video_id;

                                $custom_live_video_details->share_link = $share_link;

                                $custom_live_video_details->is_follow = DEFAULT_TRUE;

                                $custom_live_video_details->is_follow = check_follow_status($request->id, $custom_live_video_details->user_id);

                                $lists[] = $custom_live_video_details;

                            }

                        }

                        break;
                    
                    default:
                        # code...

                        $lists = [];

                        break;
                }

                
            }

            return ['success'=>true, 'code'=>200, 'data'=>$lists];

        } catch(Exception $e) {

            $error = $e->getMessage();

            $error_code = $e->getCode();

            $response_array = ['success' => false , 'error' => $error , 'error_code' => $error_code];

            return response()->json($response_array , 200);
        }


    }

    /**
     * Function: user_notifications()
     *
     * @uses: used to get the notifications for the selected user
     *
     * @created Shobana C
     *
     * @updated Shobana 
     *
     * @param integer id, token
     *
     * @return json response 
     */
    public function user_notifications(Request $request) {

        $user_notifications = UserNotification::select('user_notifications.id as notify_id', 
                            'user_notifications.notification as notification', 
                            'users.picture as picture',
                            'users.name as name',
                            'user_notifications.user_id as user_id',
                            'user_notifications.created_at as created_at',
                            'user_notifications.type',
                            'user_notifications.link_id',
                            'user_notifications.status')
                        ->where('user_notifications.user_id', $request->id)
                        ->leftJoin('users', 'users.id', '=', 'user_notifications.notifier_user_id')
                        ->skip($request->skip)
                        ->take(Setting::get('admin_take_count'))
                        ->orderBy('created_at', 'desc')
                        ->get();

        $notifications = [];

        foreach ($user_notifications as $key => $value) {
        
            $notifications[] = [ "notify_id" =>  $value->notify_id,
                                "notification" => $value->notification,
                                "picture" => $value->picture,
                                "name" => $value->name,
                                "user_id" => $value->user_id,
                                "type" => $value->type,
                                "link_id" => $value->link_id,
                                "created_at" => $value->created_at->diffForHumans(),
                                "status" => $value->status]; 

        }

        if ($request->device_type != DEVICE_WEB) {

            UserNotification::where('user_notifications.user_id', $request->id)
                    ->where('user_notifications.status', DEFAULT_FALSE)->update(['status'=> DEFAULT_TRUE]);

        }

        return response()->json(['data' => $notifications, 'success'=>true]);

    }


    /**
     * Function: notification_count()
     *
     * @uses: to get notification count of the user
     *
     * @created: Shobana C
     *
     * @updated: -
     *
     * @param integer id, token
     *
     * @return json response 
     */
    public function notification_count(Request $request) {

        $count = UserNotification::where('user_notifications.user_id', $request->id)->where('user_notifications.status', DEFAULT_FALSE)->count();

        return response()->json(['count'=>$count, 'success'=>true]);

    }

    /**
     * Function: change_notifications_status()
     *
     * @uses: used to change the status of the notifications for the selected user
     *
     * @created: Shobana C
     *
     * @updated: -
     *
     * @param integer id, token
     *
     * @return json response 
     */
    public function change_notifications_status(Request $request) {

        $new_value = UserNotification::select('user_notifications.id as id', 
                            'user_notifications.notification as notification', 
                            'users.picture as picture',
                            'users.name as name',
                            'users.id as user_id',
                            'user_notifications.type',
                            'user_notifications.link_id',
                            'user_notifications.created_at as created_at',
                            'user_notifications.status')
                        ->where('user_notifications.user_id', $request->id)
                        ->leftJoin('users', 'users.id', '=', 'user_notifications.user_id')
                        ->orderBy('created_at', 'desc')
                        ->where('user_notifications.status', DEFAULT_FALSE)->get();

        $notifications = [];

        foreach ($new_value as $key => $value) {

            $value->status = DEFAULT_TRUE;

            $value->save();

            $notifications[] = ["notify_id"=>$value->notify_id,
                                "notification"=>$value->notification,
                                "picture"=>$value->picture,
                                "name"=>$value->name,
                                "user_id"=>$value->user_id,
                                "type"=>$value->type,
                                "link_id"=>$value->link_id,
                                "created_at"=>$value->created_at->diffForHumans(),
                                "status"=>$value->status]; 

        }

        return response()->json(['cnt'=>count($notifications), 'notifications'=>$notifications]);

    }

    /**
     * @method vod_videos_owner_list()
     *
     * @uses used to get the details of the video
     *
     * @created vithya R
     * 
     * @updated vithya R
     *
     * @param integer vod_video_id
     *
     * @return  json response
     */
    
    public function vod_videos_owner_list(Request $request) {

        try {

            $vod_videos = VodVideo::where('user_id', $request->id)->VodRevenueResponse()->get();

            foreach ($vod_videos as $key => $vod_video_details) {
                
                $base_query = PayPerView::where('video_id', $vod_video_details->vod_video_id)->where('status', PAID_STATUS);

                $vod_video_details->total_revenue = $base_query->sum('amount') ?? 0.00;

                $vod_video_details->total_revenue_formatted = formatted_amount($vod_video_details->total_revenue);

                $vod_video_details->total_admin_amount = $base_query->sum('admin_amount') ?? 0.00;

                $vod_video_details->total_admin_amount_formatted = formatted_amount($vod_video_details->total_admin_amount);

                $vod_video_details->total_user_amount = $base_query->sum('user_amount') ?? 0.00;

                $vod_video_details->total_user_amount_formatted = formatted_amount($vod_video_details->total_user_amount);
            }
            

            $response_array = ['success' => true, 'data' => $vod_videos];

            return response()->json($response_array, 200);

        } catch(Exception $e) {

            $response_array = ['success' => false, 'error' => $e->getMessage(), 'error_code' => $e->getCode()];

            return response()->json($response_array, 200); 

        }
    
    }

    /**
     * @method vod_videos_owner_view()
     *
     * @uses used to get the details of the video
     *
     * @created vithya R
     * 
     * @updated vithya R
     *
     * @param integer vod_video_id
     *
     * @return  json response
     */
    
    public function vod_videos_owner_view(Request $request) {

        try {

            $vod_video_details = VodVideo::where('user_id', $request->id)->where('id', $request->vod_video_id)->VodRevenueResponse()->first();

            if(!$vod_video_details) {

                throw new Exception(tr('no_vod_video_found'), 101);            
            }

            $base_query = PayPerView::where('video_id', $request->vod_video_id)->where('status', PAID_STATUS);

            $vod_video_details->total_revenue = $base_query->sum('user_amount') ?? 0.00;

            $vod_video_details->total_revenue_formatted = formatted_amount($vod_video_details->total_revenue);

            $vod_video_details->total_admin_amount = $base_query->sum('admin_amount') ?? 0.00;

            $vod_video_details->total_admin_amount_formatted = formatted_amount($vod_video_details->total_admin_amount);

            $vod_video_details->total_user_amount = $base_query->sum('user_amount') ?? 0.00;

            $vod_video_details->total_user_amount_formatted = formatted_amount($vod_video_details->total_user_amount);

            $vod_video_details->today_revenue = $base_query->whereDate('created_at', '=',DB::raw('CURDATE()'))->sum('user_amount') ?? 0.00;

            $vod_video_details->today_revenue_formatted = formatted_amount($vod_video_details->today_revenue);

            $response_array = ['success' => true, 'data' => $vod_video_details];

            return response()->json($response_array, 200);

        } catch(Exception $e) {

            $response_array = ['success' => false, 'error' => $e->getMessage(), 'error_code' => $e->getCode()];

            return response()->json($response_array, 200); 

        }
    }

    /**
     * Function Name : vod_payment_apple_pay()
     * 
     * Pay the payment for Pay per view through stripe
     *
     * @param object $request - Admin video id
     * 
     * @return response of success/failure message
     */
    public function vod_payment_apple_pay(Request $request) {

        try {

            DB::beginTransaction();

             $validator = Validator::make($request->all(), [
                'coupon_code' => 'nullable|exists:coupons,coupon_code,status,'.COUPON_ACTIVE,
                'payment_mode' => 'required|in:'.APPLE_PAY,
                'token_id' => 'required',  
                'video_id'=>'required|exists:vod_videos,id,publish_status,'.VIDEO_PUBLISHED.',admin_status,'.VOD_APPROVED_BY_USER.',status,'.VOD_APPROVED_BY_ADMIN          
            ], array(
                    'coupon_code.exists' => tr('coupon_code_not_exists'),
                    'video_id.exists' => tr('video_not_exists'),
                ));

            if($validator->fails()) {

                $errors = implode(',', $validator->messages()->all());
                
                $response_array = ['success' => false, 'error_messages' => $errors, 'error_code' => 101];

                throw new Exception($errors);

            }

            $userModel = User::find($request->id);

            if (!$userModel) {

                throw new Exception(tr('no_user_detail_found'));
            }   


            $video = VodVideo::find($request->video_id);

            if($video) {

                $total = $video->amount;

                $coupon_amount = 0;

                $coupon_reason = '';

                $is_coupon_applied = COUPON_NOT_APPLIED;

                if ($request->coupon_code) {

                    $coupon = Coupon::where('coupon_code', $request->coupon_code)->first();

                    if ($coupon) {
                        
                        if ($coupon->status == COUPON_INACTIVE) {

                            $coupon_reason = tr('coupon_inactive_reason');

                        } else {

                            $check_coupon = $this->check_coupon_applicable_to_user($userModel, $coupon)->getData();

                            if ($check_coupon->success) {

                                $is_coupon_applied = COUPON_APPLIED;

                                $amount_convertion = $coupon->amount;

                                if ($coupon->amount_type == PERCENTAGE) {

                                    $amount_convertion = round(amount_convertion($coupon->amount, $video->amount), 2);

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

                if ($total <= 0) {

                    $user_payment = new PayPerView;

                    $user_payment->payment_id = $is_coupon_applied ? 'COUPON-DISCOUNT' : FREE_PLAN;

                    $user_payment->user_id = $request->id;
                    $user_payment->video_id = $request->video_id;

                    $user_payment->status = PAID_STATUS;

                    $user_payment->is_watched = NOT_YET_WATCHED;

                    $user_payment->ppv_date = date('Y-m-d H:i:s');

                    if ($video->type_of_user == NORMAL_USER) {

                        $user_payment->type_of_user = tr('normal_users');

                    } else if($video->type_of_user == PAID_USER) {

                        $user_payment->type_of_user = tr('paid_users');

                    } else if($video->type_of_user == BOTH_USERS) {

                        $user_payment->type_of_user = tr('both_users');
                    }


                    if ($video->type_of_subscription == ONE_TIME_PAYMENT) {

                        $user_payment->type_of_subscription = tr('one_time_payment');

                    } else if($video->type_of_subscription == RECURRING_PAYMENT) {

                        $user_payment->type_of_subscription = tr('recurring_payment');

                    }

                    $user_payment->payment_mode = CARD;

                    // Coupon details

                    $user_payment->is_coupon_applied = $is_coupon_applied;

                    $user_payment->coupon_code = $request->coupon_code ? $request->coupon_code : '';

                    $user_payment->coupon_amount = $coupon_amount;

                    $user_payment->ppv_amount = $video->amount;

                    $user_payment->amount = $total;

                    $user_payment->coupon_reason = $is_coupon_applied == COUPON_APPLIED ? '' : $coupon_reason;

                    $user_payment->save();

                    // Commission Spilit 

                    if($video->amount > 0) { 

                        // Do Commission spilit  and redeems for moderator

                        Log::info("ppv_commission_spilit started");

                        UserRepo::ppv_commission_split($video->id , $user_payment->id , "");

                        Log::info("ppv_commission_spilit END"); 
                        
                    }

                    \Log::info("ADD History - add_to_redeem");

                    $data = ['id'=> $request->id, 'token'=> $userModel->token , 'payment_id' => $user_payment->payment_id];

                    $response_array = array('success' => true, 'message'=>tr('payment_success'),'data'=> $data);

                } else {

                    // Get the key from settings table

                    $stripe_secret_key = Setting::get('stripe_secret_key');
                    
                    if($stripe_secret_key) {

                        \Stripe\Stripe::setApiKey($stripe_secret_key);

                    } else {

                        $response_array = array('success' => false, 'error_messages' => Helper::get_error_message(902) , 'error_code' => 902);

                        throw new Exception(Helper::get_error_message(902));
                        
                    }

                    try {

                       $user_charge =  \Stripe\Charge::create(array(
                          "amount" => $total * 100,
                          "currency" => "usd",
                          "source" => $request->token_id,
                        ));

                       $payment_id = $user_charge->id;
                       $amount = $user_charge->amount/100;
                       $paid_status = $user_charge->paid;
                       
                       if($paid_status) {

                            $user_payment = new PayPerView;
                            $user_payment->payment_id  = $payment_id;
                            $user_payment->user_id = $request->id;
                            $user_payment->video_id = $request->video_id;
                            $user_payment->payment_mode = CARD;
                        

                            $user_payment->status = PAID_STATUS;

                            $user_payment->is_watched = NOT_YET_WATCHED;

                            $user_payment->ppv_date = date('Y-m-d H:i:s');

                            if ($video->type_of_user == NORMAL_USER) {

                                $user_payment->type_of_user = tr('normal_users');

                            } else if($video->type_of_user == PAID_USER) {

                                $user_payment->type_of_user = tr('paid_users');

                            } else if($video->type_of_user == BOTH_USERS) {

                                $user_payment->type_of_user = tr('both_users');
                            }


                            if ($video->type_of_subscription == ONE_TIME_PAYMENT) {

                                $user_payment->type_of_subscription = tr('one_time_payment');

                            } else if($video->type_of_subscription == RECURRING_PAYMENT) {

                                $user_payment->type_of_subscription = tr('recurring_payment');

                            }

                            // Coupon details

                            $user_payment->is_coupon_applied = $is_coupon_applied;

                            $user_payment->coupon_code = $request->coupon_code ? $request->coupon_code : '';

                            $user_payment->coupon_amount = $coupon_amount;

                            $user_payment->ppv_amount = $video->amount;

                            $user_payment->amount = $total;

                            $user_payment->coupon_reason = $is_coupon_applied == COUPON_APPLIED ? '' : $coupon_reason;
                                                  
                            $user_payment->save();

                            // Commission Spilit 

                            if($video->amount > 0) { 

                                // Do Commission spilit  and redeems for moderator

                                Log::info("ppv_commission_spilit started");

                                UserRepo::ppv_commission_split($video->id , $user_payment->id , "");

                                Log::info("ppv_commission_spilit END");
                                
                            }

                        
                            $data = ['id'=> $request->id, 'token'=> $userModel->token , 'payment_id' => $payment_id];

                            $response_array = array('success' => true, 'message'=>tr('payment_success'),'data'=> $data);

                        } else {

                            $response_array = array('success' => false, 'error_messages' => Helper::get_error_message(902) , 'error_code' => 902);

                            throw new Exception(tr('no_vod_video_found'));

                        }
                    
                    } catch(\Stripe\Error\RateLimit $e) {

                        throw new Exception($e->getMessage(), 903);

                    } catch(\Stripe\Error\Card $e) {

                        throw new Exception($e->getMessage(), 903);

                    } catch (\Stripe\Error\InvalidRequest $e) {
                        // Invalid parameters were supplied to Stripe's API
                       
                        throw new Exception($e->getMessage(), 903);

                    } catch (\Stripe\Error\Authentication $e) {

                        // Authentication with Stripe's API failed

                        throw new Exception($e->getMessage(), 903);

                    } catch (\Stripe\Error\ApiConnection $e) {

                        // Network communication with Stripe failed

                        throw new Exception($e->getMessage(), 903);

                    } catch (\Stripe\Error\Base $e) {
                      // Display a very generic error to the user, and maybe send
                        
                        throw new Exception($e->getMessage(), 903);

                    } catch (Exception $e) {
                        // Something else happened, completely unrelated to Stripe

                        throw new Exception($e->getMessage(), 903);

                    } catch (\Stripe\StripeInvalidRequestError $e) {

                            Log::info(print_r($e,true));

                        throw new Exception($e->getMessage(), 903);
                        
                    
                    }


                }

            
            } else {

                $response_array = array('success' => false , 'error_messages' => tr('no_vod_video_found'));

                throw new Exception(tr('no_vod_video_found'));
                
            }


            DB::commit();

            return response()->json($response_array,200);

        } catch (Exception $e) {

            DB::rollback();

            $message = $e->getMessage();

            $code = $e->getCode();

            $response_array = ['success'=>false, 'error_messages'=>$message, 'error_code'=>$code];

            return response()->json($response_array);

        }
        
    }

}