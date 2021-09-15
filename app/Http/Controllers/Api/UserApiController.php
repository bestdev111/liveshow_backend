<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Validator, Log, Hash, Setting, DB, Exception, File;

use App\Repositories\CommonRepository as CommonRepo;

use App\Repositories\UserRepository as UserRepo;

use App\Repositories\VodRepository as VideoRepo;

use App\Repositories\StreamerGalleryRepository as StreamerGalleryRepo;

use App\Repositories\PaymentRepository as PaymentRepo;

use App\Helpers\Helper;

use App\Settings, App\Page;

use App\NotificationTemplate, App\UserNotification;

use App\User, App\Admin, App\Viewer;

use App\Follower, App\BlockList;

use App\Card, App\Subscription, App\UserSubscription;

use App\Redeem, App\RedeemRequest;

use App\VodVideo, App\PayPerView;

use App\Coupon, App\UserCoupon;

use App\LiveVideo, App\LiveVideoPayment, App\ChatMessage;

use App\LiveGroup, App\LiveGroupMember;

use App\CustomLiveVideo;

class UserApiController extends Controller {
    
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
     * @method register()
     *
     * @uses To Register a new user
     *
     * @created vithya R
     * 
     * @updated vithya R
     *
     * @param object $request - User Details
     *
     * @return user details
     */
    public function register(Request $request) {

        try {
            
            DB::beginTransaction();

            // Basic validation start

            $basic_rules = [
                    'device_type' => 'required|in:'.DEVICE_ANDROID.','.DEVICE_IOS.','.DEVICE_WEB,
                    'device_token' => 'required',
                    'login_by' => 'required|in:manual,facebook,google,apple',
                    'login_type' => 'in:'.CREATOR.','.VIEWER
                    ];

            Helper::custom_validator($request->all(), $basic_rules, $custom_errors = []);
            
            // Basic validation end

            // social validation start

            $login_by = $request->login_by;

            $allowed_social_login = ['facebook','google','apple'];

            if(in_array($request->login_by, $allowed_social_login)) {

                $other_rules = [
                        'social_unique_id' => 'required',
                        'name' => 'required|max:255',
                        'email' => 'required|email|max:255',
                        'mobile' => 'digits_between:6,13',
                        'picture' => '',
                        'gender' => 'in:male,female,others',
                        ];

            } else {

                $other_rules = [
                        'name' => 'required|max:255',
                        'email' => 'required|email|max:255|unique:users,email',
                        'password' => 'required|min:6|confirmed',
                        'picture' => 'mimes:jpeg,jpg,bmp,png',
                        ];

            }

            Helper::custom_validator($request->all(), $other_rules, $custom_errors = []);
            
            // social validation end

            $user_details = User::where('email', $request->email)->first();

            $new_user = DEFAULT_FALSE;

            $request->login_type = $request->login_type ? $request->login_type : VIEWER;

            $is_content_creator = $request->login_type == CREATOR ? CREATOR_STATUS : VIEWER_STATUS;

            // Creating the user
            if(!$user_details) {

                $new_user = DEFAULT_TRUE;

                $user_details = new User;

                $user_details->picture = asset('images/default-profile.jpg');

                $user_details->chat_picture = asset('images/default-profile.jpg');

                $user_details->cover = asset('images/cover.jpg');

                // Check the default subscription and save the user type 

                user_type_check($user_details->id);

                register_mobile($request->device_type); // @todo

                $user_details->is_content_creator = $is_content_creator;

            }

            $user_details->name = $request->name;

            $user_details->email = $request->email;

            $user_details->mobile = $request->mobile ?? '';

            if($request->has('password')) {

                $user_details->password = Hash::make($request->password);
            }

            $check_device_exist = User::where('device_token', $request->device_token)->first();

            if($check_device_exist) {

                $check_device_exist->device_token = "";

                $check_device_exist->save();

            }

            $user_details->device_token = $request->device_token ?? "";

            $user_details->device_type = $request->device_type ?? DEVICE_WEB;

            $user_details->login_by = $request->login_by ?? 'manual';

            $user_details->social_unique_id = $request->social_unique_id ?? '';

            if($new_user){

                $user_details->picture = asset('placeholder.png');

            }

            $user_details->timezone = $request->timezone ?? 'Asia/Kolkata';

            $user_details->paypal_email = $request->paypal_email ?? '';

            $user_details->login_status = NO;

            if($request->login_by == "manual") {

                if($request->hasFile('picture')) {

                    $user_details->picture = Helper::storage_upload_file($request->file('picture'), USER_PATH);

                }

            } else {

                if($new_user && $request->has('picture')) {

                    $user_details->picture = $request->picture ?:asset('placeholder.png');

                }

                /*
                 * Check the logged user as viewer or content creator
                 *
                 * If the user registered as content creator and trying to login with Viewer means we need restrict the login.
                 *
                 * For viewer - vice versa
                 */

                // If he is loggin properly we will redirect iinto his profile

                if($user_details->is_content_creator != $is_content_creator) {

                    $message = api_error(124); $code = 124;

                    // User is content creator but he logging as viewer means will through error

                    if ($user_details->is_content_creator == YES && !$is_content_creator) {

                        $message = api_error(125); $code = 125;
                    }

                    // User is viewer but he logging as content creator means will through error

                    if ($user_details->is_content_creator == NO && $is_content_creator) {

                        $message = api_error(126); $code = 126;

                    }

                   throw new Exception($message, $code);
                    
                }

            }
                 
            if($user_details->save()) {

                if($new_user && $user_details->login_by == 'manual') {

                    $email_data['name'] = $user_details->name;

                    $email_data['subject'] = tr('user_welcome_title').' '.Setting::get('site_name');

                    $email_data['page'] = "emails.user.welcome";

                    $email_data['data'] = $user_details;

                    $email_data['email'] = $user_details->email;

                    dispatch(new \App\Jobs\SendEmailJob($email_data));


                }

                DB::commit();

                if($user_details->is_verified == USER_EMAIL_NOT_VERIFIED && Setting::get('email_verify_control') == YES) {

                    $response = ['success' => false, 'error' => api_error(1001), 'error_code' => 1001]; 

                    return response()->json($response, 200);
                    
                }

                $user_details->login_status = YES;

                $user_details->save();

                DB::commit();

                $data = User::CommonResponse()->where('id', $user_details->id)->first();

                $blocked_user_ids = Helper::get_bloked_users($user_details->id);

                $data->total_live_videos = Helper::total_live_videos($user_details->id, $blocked_user_ids);

                $data->total_followers = Helper::total_followers($user_details->id, $blocked_user_ids);

                $data->total_followings = Helper::total_followings($user_details->id, $blocked_user_ids);

                $data->is_user_live = Helper::is_user_live($user_details->id);

                $message = api_success(116); $code = 116;

                return $this->sendResponse($message, $code, $data);

            } else {

                throw new Exception(api_error(123), 123);
                
            }

        } catch (Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());

        }
    
    }

    /**
     * @method login()
     *
     * @uses To authenticate the user is exists or not
     *
     * @created vithya R
     * 
     * @updated vithya R
     * 
     * @param Object $request - User Details
     *
     * @return user details
     */
    public function login(Request $request) {

        try {

            DB::beginTransaction();

            $request->request->add(['login_type'=>$request->login_type ?? VIEWER]);

            // Basic validation start

            $basic_rules = [
                    'device_token' => 'required',
                    'device_type' => 'required|in:'.DEVICE_ANDROID.','.DEVICE_IOS.','.DEVICE_WEB,
                    'login_by' => 'in:manual,facebook,google,apple',
                    'login_type'=>'required|in:'.CREATOR.','.VIEWER
                    ];

            Helper::custom_validator($request->all(), $basic_rules, $custom_errors = []);
            
            // Basic validation end

            // Other validation start

            $other_rules = [
                    'email' => 'required|email|exists:users,email',
                    'password' => 'required',
                    ];

            Helper::custom_validator($request->all(), $other_rules, $custom_errors = []);
            
            // Other validation end
                

            $user_details = User::where('email', '=', $request->email)->first();
            
            $email_active = DEFAULT_TRUE;

            if(!$user_details) {

                throw new Exception(api_error(1002), 1002);
            }

            // For demo users - no need to check the login status

            $demo_users = Setting::get('demo_users') ?? 'user@streamnow.com,test@streamnow.com';

            $demo_users = explode(',', $demo_users); 

            if(in_array($user_details->email, $demo_users)) {

                $user_details->login_status = 0;

                $user_details->save();

            }

            if($user_details->login_status != 0 && $user_details->token_expiry > time()) {
                
                throw new Exception(api_error(127), 127);

            }

            /*
             * Check the logged user as viewer or content creator
             *
             * If the user registered as content creator and trying to login with Viewer means we need restrict the login.
             *
             * For viewer - vice versa
             */

            $is_content_creator = $request->login_type == CREATOR ? YES : NO;

            // If he is loggin properly we will redirect iinto his profile

            if($user_details->is_content_creator != $is_content_creator) {

                $message = api_error(124); $code = 124;

                // User is content creator but he logging as viewer means will through error

                if ($user_details->is_content_creator == YES && !$is_content_creator) {

                    $message = api_error(125); $code = 125;
                }

                // User is viewer but he logging as content creator means will through error

                if ($user_details->is_content_creator == NO && $is_content_creator) {

                    $message = api_error(126); $code = 126;

                }

               throw new Exception($message, $code);
                
            }

            if($user_details->is_verified == USER_EMAIL_NOT_VERIFIED && Setting::get('email_verify_control') == YES) { 

                Helper::check_email_verification("" , $user_details, $error);

                $email_active = DEFAULT_FALSE;
                
            }

            if(!$email_active) {

                throw new Exception(api_error(1001), 1001);
            }

            if(in_array($user_details->status , [USER_DECLINED , USER_PENDING])) {
                
                $response = ['success' => false , 'error' => api_error(1000) , 'error_code' => 1000];

                DB::commit();

                return response()->json($response, 200);
               
            }

            if(Hash::check($request->password, $user_details->password)){

            } else {

                throw new Exception(api_error(108), 108);
                
            }
            
            $user_details->token_expiry = Helper::generate_token_expiry();

            // Save device details
            $user_details->device_token = $request->device_token ?? "";

            $user_details->device_type = $request->device_type;

            $user_details->login_by = $request->login_by;

            $user_details->timezone = $request->timezone ?? 'Asia/Kolkata';

            $user_details->login_status = YES;

            if($user_details->save()) {

                $data = User::CommonResponse()->where('id', $user_details->id)->first();

                $blocked_user_ids = Helper::get_bloked_users($user_details->id);

                $data->total_live_videos = Helper::total_live_videos($user_details->id, $blocked_user_ids);

                $data->total_followers = Helper::total_followers($user_details->id, $blocked_user_ids);

                $data->total_followings = Helper::total_followings($user_details->id, $blocked_user_ids);

                $data->is_user_live = Helper::is_user_live($user_details->id);

                DB::commit();

                return $this->sendResponse($message = api_success(101), $code = 101, $data);

            }

            throw new Exception(api_error(128), 128);


        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());

        }
    
    }

    /**
     * @method forgot_password
     *
     * @uses If user forgot their password , they can make use of it.
     *
     * @created vithya R
     * 
     * @updated vithya R
     * 
     * @param Email Id $request - Given User mail id
     *
     * @return Success/failure message
     */
    public function forgot_password(Request $request) {

        try {

            DB::beginTransaction();

            // Check email configuration and email notification enabled by admin

            if(Setting::get('email_notification') != YES || envfile('MAIL_USERNAME') == "" || envfile('MAIL_PASSWORD') == "" ) {

                throw new Exception(api_error(106), 106);
                
            }

            // Validation start

            $rules = ['email' => 'required|email|exists:users,email',];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);
            
            // Validation end


            $user_details = User::where('email', $request->email)->first();

            if(!$user_details) {

                throw new Exception(api_error(1002), 1002);
            }

            if($user_details->login_by != "manual") {

                throw new Exception(api_error(116), 116);
                
            }

            // check email verification

            if($user_details->is_verified == USER_EMAIL_NOT_VERIFIED) {

                throw new Exception(api_error(1008), 1008);
            }

            // Check the user approve status

            if(in_array($user_details->status , [USER_DECLINED , USER_PENDING])) {

                throw new Exception(api_error(1000), 1000);
            }

            $new_password = Helper::generate_password();

            $user_details->password = Hash::make($new_password);

            $email_data['subject'] =  Setting::get('site_name').' '.tr('forgot_email_title');

            $email_data['page'] = "emails.user.forgot-password";

            $email_data['user']  = $user_details;

            $email_data['email'] = $user_details->email;

            $email_data['name'] = $user_details->name;

            $email_data['password'] = $new_password;

            $this->dispatch(new \App\Jobs\SendEmailJob($email_data)); // @todo

            // $email_send = Helper::send_email($email_data['page'], $email_data['subject'],$user_details->email,$email_data)->getData();

            if(!$user_details->save()) {

                throw new Exception("Something went wrong", 103);

            }

            DB::commit();

            return $this->sendResponse($message = api_success(102), $code = 102);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());

        }
    
    }

    /**
     * @method change_password()
     *
     * @uses To change the password of the particular signed in user
     *
     * @created vithya R
     *
     * @updated vithya R
     * 
     * @param Object $request - User Details
     *
     * @return success/failure Message
     */
    public function change_password(Request $request) {

        try {
            
            DB::beginTransaction();

            // Validation start

            $rules = ['password' => 'required|confirmed|min:6','old_password' => 'required|min:6'];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);

            $user_details = User::find($request->id);

            if(!$user_details) {

                throw new Exception(api_error(1002), 1002);
            }

            if($user_details->login_by != "manual") {

                throw new Exception(api_error(118), 118);
                
            }

            if(Hash::check($request->old_password,$user_details->password)) {

                $user_details->password = Hash::make($request->password);
                
                if($user_details->save()) {

                    DB::commit();

                    return $this->sendResponse(api_success(104), $success_code = 104, $data = []);
                
                } else {

                    throw new Exception(api_error(103), 103);   
                }

            } else {

                throw new Exception(api_error(108) , 108);
            }


        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());

        }

    }

    /**
     * @method profile()
     *
     * @uses To display user details based on user id
     *
     * @created vithya R
     *
     * @updated vithya R
     * 
     * @param Object $request - User Details
     *
     * @return success/failure Message
     */
    public function profile(Request $request) {

        try {
            
            $user_details = User::where('id' , $request->id)->CommonResponse()->first();

            if(!$user_details) { 

                throw new Exception(api_error(1002) , 1002);
            }

            $blocked_user_ids = Helper::get_bloked_users($request->id);

            $user_details->total_live_videos = Helper::total_live_videos($request->id, $blocked_user_ids);

            $user_details->total_followers = Helper::total_followers($request->id, $blocked_user_ids);

            $user_details->total_followings = Helper::total_followings($request->id, $blocked_user_ids);

            $user_details->is_user_live = Helper::is_user_live($request->id);

            return $this->sendResponse($message = "", $code = "", $user_details);

        } catch(Exception $e) {
            
            return $this->sendError($e->getMessage(), $e->getCode());
        }        
    
    }

    /**
     * @method update_profile()
     *
     * @uses To update user details based on user id
     *
     * @created vithya R
     *
     * @updated vithya R
     * 
     * @param object $request - User Details
     *
     * @return user details
     */
    public function update_profile(Request $request) {

        try {
            
            DB::beginTransaction();

            // Validation start

            $rules = [
                    'name' => 'required|max:255',
                    'description' => 'max:255',
                    'email' => 'email|unique:users,email,'.$request->id.'|max:255',
                    'mobile' => 'nullable|digits_between:6,13',
                    'picture' => $request->id ? 'mimes:jpeg,bmp,png' : "",
                    'cover' => $request->id ? 'mimes:jpeg,bmp,png' : "",
                    'device_token' => '',
                    ];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);

            // Validation end

            $user_details = User::find($request->id);

            if(!$user_details) {

                throw new Exception(api_error(1002), 1002);
                
            }

            $user_details->name = $request->name ?? $user_details->name;

            $user_details->email = $request->email ?? $user_details->email;

            $user_details->mobile = $request->mobile ?? $user_details->mobile;

            $user_details->gender = $request->gender ?? $user_details->gender;
                    
            $user_details->description = $request->description ?? $user_details->description;

            $user_details->paypal_email = $request->paypal_email ?? ($user_details->paypal_email ?? '');

            // Upload picture chat picture will save inside upload avatar function, using this third parameter
            
            if ($request->hasFile('picture') != "") {

                Helper::storage_delete_file($user_details->picture, USER_PATH); // Delete the old pic

                Helper::delete_avatar(USER_CHAT_PATH, $user_details->chat_picture); // Delete the old pic

                $user_details->picture = Helper::storage_upload_file($request->file('picture'), USER_PATH);
            }

            // Upload picture

            if ($request->hasFile('cover')) {

                Helper::storage_delete_file($user_details->cover, USER_PATH);

                $user_details->cover = Helper::storage_upload_file($request->file('cover'), USER_PATH);
            }

            if($user_details->save()) {

                $data = User::CommonResponse()->where('id', $user_details->id)->first();

                DB::commit();

                return $this->sendResponse($message = api_success(131), $code = 131, $data);

            }

            throw new Exception(api_error(128), 128);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }
    
    }

    /**
     * @method delete_account()
     *
     * @uses To delete user account if he dont want the profile
     *
     * @created vithya R
     *
     * @updated vithya R
     * 
     * @param string $request - Request Password
     *
     * @return user details
     */
    public function delete_account(Request $request) {

        try {

            DB::beginTransaction();

            // Validation start

            $request->request->add([
                'login_by' => $this->loginUser ? $this->loginUser->login_by : LOGIN_BY_MANUAL
            ]);

            $rules = [
                'login_by' => 'required|in:manual,facebook,google,apple',
            ];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);

            // Validation end

            $user_details = User::find($request->id);

            if(!$user_details) {

                throw new Exception(api_error(1002), 1002);
                
            }

            // The password is not required when the user is login from social. If manual means the password is required

            if($user_details->login_by == LOGIN_BY_MANUAL) {

                if(!Hash::check($request->password, $user_details->password)) {

                    $is_delete_allow = NO ;

                    $error = api_error(108);
         
                    throw new Exception($error , 108);
                    
                }
            
            }

            if($user_details->delete()) {

                DB::commit();

                $message = api_success(103);

                return $this->sendResponse($message, $code = 103, $data = []);

            } else {

                throw new Exception(api_error(119), 119);
            }

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());

        }

    }

    /**
     * @method logout()
     *
     * @uses Delete logged device while logout user
     *
     * @created vithya R
     *
     * @updated vithya R
     * 
     * @param interger $request - User Id
     *
     * @return boolean  succes/failure message
     */
    public function logout(Request $request) {

        try {

            DB::beginTransaction();

            $user_details = User::find($request->id);

            if(!$user_details) {

                throw new Exception(api_error(1002), 1002);
                
            }

            $user_details->login_status = 0;

            $user_details->save();

            DB::commit();

            return $this->sendResponse(api_success(106), 106);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }  

    /**
     * @method become_creator()
     *
     * @uses To change the viewer into creator
     *
     * @created vithya R
     *
     * @updated vithya R 
     *
     * @param integer $request - User id,token
     *
     * @return response of json details
     */
    public function become_creator(Request $request) {

        try {

            $user_details = User::find($request->id);

            if($user_details->is_content_creator == CREATOR_STATUS) {

                throw new Exception(api_error(133), 133);
                
            }

            DB::beginTransaction();

            $user_details->is_content_creator = CREATOR_STATUS;

            $user_details->save();

            DB::commit();

            $data['user_id'] = $request->id; 

            $data['is_content_creator'] = CREATOR_STATUS;   

            return $this->sendResponse(api_success(115), 115, $data);
            
        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method cards_list()
     *
     * @uses Listout the user card details
     *
     * @created Bhawya
     *
     * @updated Bhawya
     *
     * @param object $request
     * 
     * @return response of details
     */
    public function cards_list(Request $request) {

        try {
            
            $user_cards = Card::where('user_id' , $request->id)
                ->select('id as card_id', 'customer_id', 'last_four' ,'card_type', 'card_token', 'is_default', 'card_holder_name')
                ->get();

            $get_payment_modes = Settings::whereIn('key', ['paypal', 'card'])->where('value' , YES)->get();
    
            $payment_modes = [];

            foreach ($get_payment_modes as $key => $mode) {

                $card_data = [];

                $card_data['name'] = strtoupper($mode->key);

                $card_data['payment_mode'] = $mode->key;

                $card_data['is_default'] = $this->loginUser->payment_mode == $mode->key ? YES : NO;

                array_push($payment_modes , $card_data);
            }

            $data['payment_modes'] = $payment_modes;   

            $data['cards'] = $user_cards ?? []; 

            return $this->sendResponse($message = "", $code = "", $data);
            
        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method cards_add()
     *
     * @uses used to add card to the user
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param card_token
     * 
     * @return JSON Response
     */
    public function cards_add(Request $request) {

        try {

            if(Setting::get('stripe_secret_key')) {

                \Stripe\Stripe::setApiKey(Setting::get('stripe_secret_key'));

            } else {

                throw new Exception(api_error(121), 121);

            }

            // Validation start

            $rules = ['card_token' => 'required'];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);

            // Validation end
            
            $user_details = User::find($request->id);

            if(!$user_details) {

                throw new Exception(api_error(1002), 1002);
                
            }

            DB::beginTransaction();

            // Get the key from settings table
            
            $customer = \Stripe\Customer::create([
                    "card" => $request->card_token,
                    "email" => $user_details->email,
                    "description" => "Customer for ".Setting::get('site_name'),
                ]);

            if($customer) {

                $customer_id = $customer->id;

                $card_details = new Card;

                $card_details->user_id = $request->id;

                $card_details->customer_id = $customer_id;

                $card_details->card_token = $customer->sources->data ? $customer->sources->data[0]->id : "";

                $card_details->card_type = $customer->sources->data ? $customer->sources->data[0]->brand : "";

                $card_details->last_four = $customer->sources->data[0]->last4 ? $customer->sources->data[0]->last4 : "";

                $card_details->card_holder_name = $request->card_holder_name ?: $this->loginUser->name;

                // Check is any default is available

                $check_card_details = Card::where('user_id',$request->id)->count();

                $card_details->is_default = $check_card_details ? NO : YES;

                if($card_details->save()) {

                    if($user_details) {

                        $user_details->card_id = $check_card_details ? $user_details->card_id : $card_details->id;

                        $user_details->save();
                    }

                    $data = Card::where('id' , $card_details->id)->CommonResponse()->first();

                    DB::commit();

                    return $this->sendResponse(api_success(105), 105, $data);

                } else {

                    throw new Exception(api_error(123), 123);
                    
                }
           
            } else {

                throw new Exception(api_error(121) , 121);
                
            }

        } catch(Stripe_CardError | Stripe_InvalidRequestError | Stripe_AuthenticationError | Stripe_ApiConnectionError | Stripe_Error $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode() ?: 101);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode() ?: 101);
        }

    }

    /**
     * @method cards_delete()
     *
     * @uses Used to delete the users card
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param object $request
     * 
     * @return json with boolean output
     */
    public function cards_delete(Request $request) {

        try {

            DB::beginTransaction();

            // validation start

            $rules = [
                    'card_id' => 'required|integer|exists:cards,id,user_id,'.$request->id,
                    ];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);
            
            // validation end

            $user_details = User::find($request->id);

            if(!$user_details) {

                throw new Exception(api_error(1002), 1002);
            }

            Card::where('id', $request->card_id)->delete();

            if($user_details->payment_mode = CARD) {

                // Check he added any other card

                if($check_card = Card::where('user_id' , $request->id)->first()) {

                    $check_card->is_default =  DEFAULT_TRUE;

                    $user_details->card_id = $check_card->id;

                    $check_card->save();

                } else { 

                    $user_details->payment_mode = COD;

                    $user_details->card_id = DEFAULT_FALSE;
                
                }
           
            }

            // Check the deleting card and default card are same

            if($user_details->card_id == $request->card_id) {

                $user_details->card_id = DEFAULT_FALSE;

                $user_details->save();
            }
            
            $user_details->save();
                
            DB::commit();

            return $this->sendResponse(api_success(109), 109, $data = []);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method cards_default()
     *
     * @uses update the selected card as default
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param integer id
     * 
     * @return JSON Response
     */
    public function cards_default(Request $request) {

        try {

            DB::beginTransaction();

            // validation start

            $rules = [
                    'card_id' => 'required|integer|exists:cards,id,user_id,'.$request->id,
                    ];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);
            
            // validation end

            $user_details = User::find($request->id);

            if(!$user_details) {

                throw new Exception(api_error(1002), 1002);
            }
        
            $old_default_cards = Card::where('user_id' , $request->id)->where('is_default', YES)->update(['is_default' => NO]);

            $user_cards = Card::where('id' , $request->card_id)->update(['is_default' => YES]);

            $user_details->card_id = $request->card_id;

            $user_details->save();

            DB::commit();

            return $this->sendResponse(api_success(201), 201);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method users_search()
     *
     * @uses used to search users by name
     *
     * @created vithya R 
     *
     * @updated vithya R
     *
     * @param 
     *
     * @return json response
     */
    public function users_search(Request $request) {

        try {

            // validation start

            $rules = ['key' => 'required'];
            
            $custom_errors = ['key.required' => 'Please enter the username'];

            Helper::custom_validator($request->all(), $rules, $custom_errors);
            
            // validation end

            $blocked_user_ids = Helper::get_bloked_users($request->id);

            $users = User::where('users.name', 'like', "%".$request->key."%")
                        ->select('users.id as user_id', 'users.unique_id as user_unique_id','users.name as user_name', 'users.picture as user_picture', 'users.email as email','users.chat_picture')
                        ->whereNotIn('users.id', $blocked_user_ids)
                        ->skip($this->skip)->take($this->take)
                        ->get();

            return $this->sendResponse($message = '', $code = '', $users);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());

        }
    
    }

    /**
     * @method live_groups_index()
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
    public function live_groups_index(Request $request) {

        try {

            $groups_query = LiveGroup::where('live_groups.status', LIVE_GROUP_APPROVED);

            $groups_query = $groups_query->where('live_groups.user_id' , $request->id)
                        ->leftJoin('live_group_members' , 'live_groups.id' , '=', 'live_group_members.live_group_id' )
                        ->orWhere('live_group_members.member_id', $request->id);

            $groups = $groups_query->baseResponse()->groupBy('live_groups.id')->skip($this->skip)->take($this->take)->get();

            foreach($groups as $key => $group_details) {

                $group_details->total_members = LiveGroupMember::where('live_group_id' , $group_details->live_group_id)->count();

                $group_details->is_owner = $request->id == $group_details->owner_id ? LIVE_GROUP_OWNER_YES : LIVE_GROUP_OWNER_NO;
                
                $actions_status = $request->id == $group_details->owner_id ? YES : NO;

                $actions = ['is_edit_group' => $actions_status, 'is_delete_group' => $actions_status, 'is_add_remove_group' => $actions_status];

                $group_details->actions = $actions;
            }

            $data['groups'] = $groups;

            $data['total_groups'] = $groups_query->get()->count();

            return $this->sendResponse($message = "", $code = "", $data);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method live_groups_save()
     *
     * @uses store/update the group details
     *
     * @created vithya R
     *
     * @updated vithya R
     *
     * @param Form data
     * 
     * @return JSON Response
     */

    public function live_groups_save(Request $request) {

        try {

            DB::beginTransaction();

            // validation start

            $rules = [
                    'live_group_id' => 'exists:live_groups,id,user_id,'.$request->id,
                    'name' => 'required|min:2|max:100',
                    'description' => 'max:255',
                    'picture' => 'mimes:jpeg,bmp,png|required_if:live_group_id,==,""',
                    ];
            $custom_errors = [
                'live_group_id' => api_error(139),
            ];

            Helper::custom_validator($request->all(), $rules, $custom_errors);
            
            // validation end

            if(!$request->live_group_id) {

                $group_details = new LiveGroup;

                $group_details->created_by = USER;

            } else {

                $group_details = LiveGroup::where('live_groups.id' , $request->live_group_id)->where('live_groups.user_id' , $request->id)->first(); 

                $group_details->created_by = $group_details->created_by ?? USER;

                if($request->file('picture')) {

                    Helper::storage_delete_file($group_details->picture,COMMON_IMAGE_PATH);
                }

            }

            $group_details->user_id = $request->id;

            $group_details->name = $request->name ?? "";

            $group_details->description = $request->description ?? "";

            if($request->file('picture')) {

                $group_details->picture = Helper::storage_upload_file($request->file('picture'),COMMON_IMAGE_PATH);
            }

            $group_details->status = LIVE_GROUP_APPROVED;

            if($group_details->save()) {

                DB::commit();

                $data = $group_details->id;

                $code = $request->live_group_id ? 123 : 122;
    
                return $this->sendResponse(api_success($code), $code, $data);
            }

            throw new Exception(Helper::error_message(907), 907);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }
    
    }

    /**
     * @method live_groups_view()
     *
     * @uses used to get the selected group details
     *
     * @created vithya R 
     *
     * @updated vithya R
     *
     * @param integer live_group_id
     *
     * @return json response
     */
    
    public function live_groups_view(Request $request) {

        try {

            // validation start

            $rules = [
                    'live_group_id' => 'required|integer|exists:live_groups,id',
                    ];
            $custom_errors = [
                'live_group_id' => api_error(139),
            ];

            Helper::custom_validator($request->all(), $rules, $custom_errors);
            
            // validation end

            $group_details = LiveGroup::where('live_groups.id', $request->live_group_id)->baseResponse()->first();

            if(!$group_details) {

                throw new Exception(api_error(139), 139);
                
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

                    throw new Exception(api_error(148), 148);
                }

                // Case 3: If Member - check the groups approve decline status

                if($group_details->live_group_status != LIVE_GROUP_APPROVED) {

                    throw new Exception(api_error(148), 148);
                    
                }
            
            }

            $members = LiveGroupMember::where('live_group_id' , $request->live_group_id)->commonResponse()->skip($this->skip)->take($this->take)->get();

            foreach ($members as $key => $member_details) {

                // check the member is streaming from this group

                $check_is_user_live = LiveVideo::where('is_streaming', DEFAULT_TRUE)
                    ->where('status', DEFAULT_FALSE)
                    ->where('type', TYPE_PUBLIC)
                    ->where('live_group_id' , $request->live_group_id)
                    ->where('user_id',$member_details->member_id)
                    ->count();

                $member_details->is_user_live = $check_is_user_live ? true : false;

                $member_details->is_remove_member = $request->id == $group_details->owner_id ? YES : NO;
            
            }

            $group_details->total_members = $members->count();

            $group_details->is_owner = $request->id == $group_details->owner_id ? LIVE_GROUP_OWNER_YES : LIVE_GROUP_OWNER_NO;

            $actions_status = $request->id == $group_details->owner_id ? YES : NO;

            $actions = ['is_edit_group' => $actions_status, 'is_delete_group' => $actions_status, 'is_add_remove_group' => $actions_status];

            $data['actions'] = $actions;

            $data['group_details'] = $group_details;

            $data['members'] = $members;

            return $this->sendResponse($message = "", $code = "", $data);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method live_groups_delete()
     *
     * @uses used to delete the selected group 
     *
     * @created vithya R 
     *
     * @updated vithya R
     *
     * @param integer live_group_id
     *
     * @return json response
     */
    public function live_groups_delete(Request $request) {

        try {

            DB::beginTransaction();

            // validation start

            $rules = [
                    'live_group_id' => 'required|integer|exists:live_groups,id',
                    ];
            $custom_errors = [
                'live_group_id' => api_error(139),
            ];

            Helper::custom_validator($request->all(), $rules, $custom_errors);
            
            // validation end

            $group_details = LiveGroup::where('live_groups.id',$request->live_group_id)->first();

            if(!$group_details) {

                throw new Exception(api_error(139), 139);
                
            }
            
            if($group_details->user_id != $request->id) {

                throw new Exception(Helper::error_message(148), 148);
            }

            if($group_details->delete()) {

                DB::commit();

                $data['live_group_id'] = $request->live_group_id;

                return $this->sendResponse(api_success(121), 121, $data);

            }

            throw new Exception(api_error(141), 141);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method live_groups_search_members()
     *
     * @uses used to add a member to the selected group
     *
     * @created vithya R 
     *
     * @updated vithya R
     *
     * @param integer live_group_id, integer member_id
     *
     * @return json response
     */
    public function live_groups_members_search(Request $request) {

        try {

            // validation start

            $rules = [
                    'key' => 'required',
                    'live_group_id' => 'required|integer|exists:live_groups,id,user_id,'.$request->id,

                    ];
            $custom_errors = [
                'key.required' => 'Please enter the username',
            ];

            Helper::custom_validator($request->all(), $rules, $custom_errors);
            
            // validation end

            $group_details = LiveGroup::where('live_groups.id',$request->live_group_id)->first();

            if(!$group_details) {

                throw new Exception(api_error(139), 139);
                
            }

            $group_member_ids = LiveGroupMember::where('live_group_id', $request->live_group_id)->where('owner_id', $request->id)->pluck('member_id');

            $users = User::where('users.name', 'like', "%".$request->key."%")
                        ->select('users.id as user_id', 'users.unique_id as user_unique_id','users.name as user_name', 'users.picture as user_picture', 'users.email as email','users.chat_picture')
                        ->whereNotIn('users.id', $group_member_ids)
                        ->skip($this->skip)->take($this->take)
                        ->get();

            return $this->sendResponse($message = '', $code = '', $users);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());

        }
    
    }

    /**
     * @method live_groups_members_add()
     *
     * @uses used to add a member to the selected group
     *
     * @created vithya R 
     *
     * @updated vithya R
     *
     * @param integer live_group_id, integer member_id
     *
     * @return json response
     */
    public function live_groups_members_add(Request $request) {

        try {

            DB::beginTransaction();

            // validation start

            $rules = [
                    'live_group_id' => 'required|integer|exists:live_groups,id',
                    'member_id' => 'required|integer|exists:users,id',
                    ];
            $custom_errors = [
                'live_group_id' => api_error(139),
                'member_id.exists' => api_error(140)
            ];

            Helper::custom_validator($request->all(), $rules, $custom_errors);
            
            // validation end

            $group_details = LiveGroup::where('live_groups.id',$request->live_group_id)->first();

            if(!$group_details) {

                throw new Exception(api_error(139), 139);
                
            }

            /**
             | CASE 1:  Owner can't join to their group 
             |
             | Case 2:  Owner only can add members to the group. 
             |          The Other member of the group can't add members
             |
            */

            // CASE 1: Owner can't join to their group

            if($request->id == $group_details->user_id && $request->member_id == $group_details->user_id) {

                throw new Exception(api_error(145), 145);
                
            }

            // Case 2: Owner only can add members to the group. The Other member of the group can't members

            if($request->id != $group_details->user_id && $request->id != $request->member_id) {
               
                throw new Exception(api_error(146), 146);

            }

            $member_details = User::find($request->member_id);

            if($member_details->status != DEFAULT_TRUE) {

                throw new Exception(api_error(142), 142);
                
            }

            // Check the member added in the selected group

            $group_member_count = LiveGroupMember::where('member_id' , $request->member_id)->where('live_group_id', $request->live_group_id)->count();

            if($group_member_count) {

                throw new Exception(api_error(143), 143);

            }

            $group_member_details = new LiveGroupMember;

            $group_member_details->live_group_id = $request->live_group_id;

            $group_member_details->owner_id = $group_details->user_id;

            $group_member_details->member_id = $request->member_id;

            $group_member_details->status = APPROVED;

            $group_member_details->added_by = $request->id == $group_details->user_id ? 'owner' : 'joined';
                    

            if($group_member_details->save()) {

                // Save Notification

                $owner_details = User::find($group_details->user_id);

                $notification = NotificationTemplate::getRawContent(USER_GROUP_ADD, $owner_details);

                $content = $notification ? $notification : USER_GROUP_ADD;

                UserNotification::save_notification($request->member_id, $content, $request->live_group_id, USER_GROUP_ADD, $request->id);

                DB::commit();

                $data['live_group_id'] = $request->live_group_id;

                $data['member_id'] = $request->member_id;

                $data['is_member'] = LIVE_GROUP_MEMBER_YES;

                return $this->sendResponse(api_success(119, $member_details->username ?? 'user'), 119, $data);

            }

            throw new Exception(api_error(141), 141);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());

        }
    
    }

    /**
     * @method live_groups_members_remove()
     *
     * @uses used to remove a member to the selected group
     *
     * @created vithya R 
     *
     * @updated vithya R
     *
     * @param integer live_group_id
     *
     * @return json response
     */
    public function live_groups_members_remove(Request $request) {

        try {

            DB::beginTransaction();

            // validation start

            $rules = [
                    'live_group_id' => 'required|integer|exists:live_groups,id',
                    'member_id' => 'required|integer|exists:users,id',
                    ];
            $custom_errors = [
                'live_group_id' => api_error(139),
                'member_id.exists' => api_error(140)
            ];

            Helper::custom_validator($request->all(), $rules, $custom_errors);
            
            // validation end

            $group_details = LiveGroup::where('live_groups.id',$request->live_group_id)->first();


            if(!$group_details) {

                throw new Exception(api_error(139), 139);
                
            }

            /**
             | CASE 1: Owner can't remove by their own 
             |
             | Case 2: Owner only can remove members to the group and the member themself left from the group
             |
            */

            // CASE 1: Owner can't remove by their own 

            if($request->id == $group_details->user_id && $request->member_id == $group_details->user_id) {

                throw new Exception(api_error(145), 145);
                
            }

            // Case 2: Owner only can remove members to the group and the member themself can't leave from the group

            if($request->id != $group_details->user_id) {

                throw new Exception(api_error(147), 147);

            }

            $member_details = User::find($request->member_id);

            // Check the member added in the selected group

            $group_member_details = LiveGroupMember::where('member_id', $request->member_id)->where('live_group_id', $request->live_group_id)->first();

            if(!$group_member_details) {

                throw new Exception(api_error(144), 144);

            }

            if($group_member_details->delete()) {

                DB::commit();

                $data = $request->all();

                return $this->sendResponse(api_success(120, $member_details->username ?? 'user'), 120, $data);

            }

            throw new Exception(api_error(141), 141);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());

        }

    }

    /**
     * @method vod_videos_owner_dashboard()
     *
     * @uses update the selected card as default
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param integer id
     * 
     * @return JSON Response
     */
    public function vod_videos_owner_dashboard(Request $request) {

        try {

            $base_query = VodVideo::where('user_id', $request->id);

            $ppv_base_query = PayPerView::leftJoin('vod_videos', 'vod_videos.id', '=', 'pay_per_views.video_id')->where('vod_videos.user_id', $request->id);

            $data = new \stdClass;

            $data->total_videos = $base_query->count();

            $data->total_published_videos = $base_query->where('status', VOD_APPROVED_BY_USER)->count() ?? 0;

            $data->total_declined_videos =  VodVideo::where('user_id', $request->id)->where('status', VOD_DECLINED_BY_USER)->count() ?? 0;

            $data->total_admin_approved_videos = VodVideo::where('user_id', $request->id)->where('admin_status', VOD_APPROVED_BY_ADMIN)->count() ?? 0;

            $data->total_admin_declined_videos =  VodVideo::where('user_id', $request->id)->where('admin_status', VOD_DECLINED_BY_ADMIN)->count() ?? 0;

            $data->total_revenue = $ppv_base_query->sum('pay_per_views.user_amount') ?? 0.00;

            $data->total_revenue_formatted = formatted_amount($data->total_revenue);

            $data->today_revenue = $ppv_base_query->whereDate('pay_per_views.created_at', '=',DB::raw('CURDATE()'))->sum('pay_per_views.user_amount') ?? 0.00;

            $data->today_revenue_formatted = formatted_amount($data->total_revenue);

            return $this->sendResponse($message = "", $code = "", $data);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method vod_videos_owner_list()
     *
     * @uses update the selected card as default
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param integer id
     * 
     * @return JSON Response
     */
    public function vod_videos_owner_list(Request $request) {

        try {

            $vod_videos = VodVideo::where('vod_videos.user_id', $request->id)
                                ->VodResponse()                    
                                ->skip($this->skip)->take($this->take)
                                ->get();

            foreach ($vod_videos as $key => $vod_video) {

                $vod_video->publish_time_formatted = common_date($vod_video->publish_time, $this->timezone);
            }

            return $this->sendResponse($message = "", $code ="", $vod_videos);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method vod_videos_owner_view()
     *
     * @uses update the selected card as default
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param integer id
     * 
     * @return JSON Response
     */
    public function vod_videos_owner_view(Request $request) {

        try {

            $vod_video_details = VodVideo::where('user_id', $request->id)->VodResponse()->first();

            if($vod_video_details) {

                $vod_video_details->publish_time_formatted = common_date($vod_video_details->publish_time, $this->timezone);
            }

            return $this->sendResponse($message = "", $code ="", $vod_video_details);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method vod_videos_owner_save()
     *
     * @uses update the selected card as default
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param integer id
     * 
     * @return JSON Response
     */
    public function vod_videos_owner_save(Request $request) {

        try {

            $user_details = $this->loginUser;

            if($user_details->user_type == NON_SUBSCRIBED_USER) {

                throw new Exception(api_error(132), 132);
                
            }

            DB::beginTransaction();

            $request->request->add(['user_id' => $request->id,'created_by' => CREATOR, 'video_id' => $request->vod_video_id ?? '']);

            $response = VideoRepo::vod_videos_save($request)->getData();

            if($response->success) {

                DB::commit();

                return response()->json($response);

            } else {

                throw new Exception($response->error_messages, $response->error_code);
                
            }

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method vod_videos_owner_delete()
     *
     * @uses update the selected card as default
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param integer id
     * 
     * @return JSON Response
     */
    public function vod_videos_owner_delete(Request $request) {

        try {

            DB::beginTransaction();

            // validation start

            $rules = [
                    'vod_video_id' => 'required|exists:vod_videos,id,user_id,'.$request->id,
                    ];
            $custom_errors = [
                'vod_video_id' => api_error(130),
            ];

            Helper::custom_validator($request->all(), $rules, $custom_errors);
            
            // validation end

            $vod_video_details = VodVideo::find($request->vod_video_id);

            if(!$vod_video_details) {

                throw new Exception(api_error(129), 129);
            }

            $vod_video_details->delete();

            DB::commit();

            return $this->sendResponse(api_success(112), 112);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }
    
    /**
     * @method vod_videos_owner_publish_status()
     *
     * @uses update the selected card as default
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param integer id
     * 
     * @return JSON Response
     */
    public function vod_videos_owner_publish_status(Request $request) {

        try {

            DB::beginTransaction();

            // validation start

            $rules = [
                    'vod_video_id'=>'required|exists:vod_videos,id,user_id,'.$request->id,
                    ];
            $custom_errors = [
                'vod_video_id' => api_error(130),
            ];

            Helper::custom_validator($request->all(), $rules, $custom_errors);
            
            // validation end

            $vod_video_details = VodVideo::find($request->vod_video_id);

            if(!$vod_video_details) {

                throw new Exception(api_error(129), 129);
            }
        
            $vod_video_details->status = $vod_video_details->status == VOD_APPROVED_BY_USER ? VOD_DECLINED_BY_USER : VOD_APPROVED_BY_USER;

            $vod_video_details->save();

            DB::commit();

            $code = $vod_video_details->status == VOD_APPROVED_BY_USER ? 113 : 114;

            $message = api_success($code);

            $data['vod_video_id'] = $request->vod_video_id;

            $data['status'] = $vod_video_details->status;

            return $this->sendResponse($message, $code, $data);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method vod_videos_owner_set_ppv()
     *
     * @uses update the selected card as default
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param integer id
     * 
     * @return JSON Response
     */
    public function vod_videos_owner_set_ppv(Request $request) {

        try {

            DB::beginTransaction();

            // validation start

            $rules = [
                    'vod_video_id' => 'required|exists:vod_videos,id,user_id,'.$request->id,
                    'amount' => 'required|numeric|min:0.1|max:100000',
                    'type_of_subscription' => 'required|in:'.ONE_TIME_PAYMENT.','.RECURRING_PAYMENT,
                    // 'type_of_user'=>'in:'.NORMAL_USER.','.PAID_USER.','.BOTH_USERS,
                    ];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);
            
            // validation end

            $vod_video_details = VodVideo::find($request->vod_video_id);

            if(!$vod_video_details) {

                throw new Exception(api_error(129), 129);
            }

            $vod_video_details->amount = $request->amount ?? 0.00;

            $vod_video_details->type_of_user = BOTH_USERS;

            $vod_video_details->type_of_subscription = $request->type_of_subscription ?? ONE_TIME_PAYMENT;

            $vod_video_details->is_pay_per_view = PPV_ENABLED;

            $vod_video_details->save();

            DB::commit();

            $data = VodVideo::VodResponse()->where('vod_videos.id', $request->vod_video_id)->first();

            return $this->sendResponse(api_success(111), 111, $data);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method vod_videos_owner_remove_ppv()
     *
     * @uses update the selected card as default
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param integer id
     * 
     * @return JSON Response
     */
    public function vod_videos_owner_remove_ppv(Request $request) {

        try {

            DB::beginTransaction();

            // validation start

            $rules = [
                    'vod_video_id' => 'required|exists:vod_videos,id,user_id,'.$request->id,
                    ];
            $custom_errors = [
                'vod_video_id' => api_error(130),
            ];

            Helper::custom_validator($request->all(), $rules, $custom_errors);
            
            // validation end

            $vod_video_details = VodVideo::find($request->vod_video_id);

            if(!$vod_video_details) {

                throw new Exception(api_error(129), 129);
            }

            $vod_video_details->amount = 0.00;

            $vod_video_details->type_of_user = $vod_video_details->type_of_subscription = 0;

            $vod_video_details->is_pay_per_view = PPV_DISABLED;

            $vod_video_details->save();

            DB::commit();

            $data = VodVideo::VodResponse()->where('vod_videos.id', $request->vod_video_id)->first();

            return $this->sendResponse(api_success(111), 111, $data);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method vod_videos_list()
     *
     * @uses update the selected card as default
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param integer id
     * 
     * @return JSON Response
     */
    public function vod_videos_list(Request $request) {

        try {

            $block_user_ids = Helper::get_bloked_users($request->id);

            $vod_videos = VodVideo::Approved()->VodResponse()
                            ->whereNotIn('user_id', $block_user_ids)
                            ->skip($this->skip)->take($this->take)
                            ->orderBy('vod_videos.updated_at', 'desc')
                            ->get();

            foreach ($vod_videos as $key => $vod_video_details) {

                // @todo need to do the Recurring payment

                $vod_video_details->is_user_needs_to_pay = Helper::vod_video_payment_status($request->id, $vod_video_details);
                
                $vod_video_details->publish_time_formatted = common_date($vod_video_details->publish_time, $this->timezone);
            }

            return $this->sendResponse($message = "", $code = "", $vod_videos);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method vod_videos_view()
     *
     * @uses update the selected card as default
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param integer id
     * 
     * @return JSON Response
     */
    public function vod_videos_view(Request $request) {

        try {

            $vod_video_details = VodVideo::where('vod_videos.id', $request->vod_video_id)->Approved()->VodResponse()->first();

            if(!$vod_video_details) {

                throw new Exception(api_error(129), 129);

            }
                
            $vod_video_details->publish_time_formatted = common_date($vod_video_details->publish_time, $this->timezone);

            $vod_video_details->is_needs_to_pay = Helper::vod_video_payment_status($request->id, $vod_video_details);

            return $this->sendResponse($message = "", $code ="", $vod_video_details);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method vod_videos_suggestions()
     *
     * @uses update the selected card as default
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param integer id
     * 
     * @return JSON Response
     */
    public function vod_videos_suggestions(Request $request) {

        try {

            $block_user_ids = Helper::get_bloked_users($request->id);

            $vod_video_ids = $request->vod_video_id ? [$request->vod_video_id] : [];

            $vod_videos = VodVideo::Approved()->VodResponse()
                    ->whereNotIn('user_id', $block_user_ids)
                    ->whereNotIn('vod_videos.id', $vod_video_ids)
                    ->skip($this->skip)->take($this->take)
                    ->orderBy(DB::raw('RAND()'))
                    ->get();

            foreach ($vod_videos as $key => $vod_video_details) {

                // @todo need to do the Recurring payment
                
                $vod_video_details->publish_time_formatted = common_date($vod_video_details->publish_time, $this->timezone);

                $vod_video_details->is_needs_to_pay = Helper::vod_video_payment_status($request->id, $vod_video_details);

            }

            return $this->sendResponse($message = "", $code = "", $vod_videos);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method vod_videos_ppv_payment()
     *
     * @uses update the selected card as default
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param integer id
     * 
     * @return JSON Response
     */
    public function vod_videos_ppv_payment(Request $request) {

        try {

            DB::beginTransaction();

            // Validation start

            $rules = [
                    'vod_video_id' => 'required|exists:vod_videos,id',
                    'payment_id' => $request->payment_mode != CARD ? 'required' : "",
                    'coupon_code' => 'nullable|exists:coupons,coupon_code',
                    ];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);
            
            // Validation end

            $vod_video_details = VodVideo::where('vod_videos.id', $request->vod_video_id)->Approved()->first();

            if(!$vod_video_details) {

                throw new Exception(api_error(129), 129);

            }

            $is_needs_to_pay = Helper::vod_video_payment_status($request->id, $vod_video_details);

            if($is_needs_to_pay == NO) {

                throw new Exception(api_error(134), 134);
                
            }

            // Check the payment modes.

            if($request->payment_mode == CARD) {

            }

            // Store the payment details

            return $this->sendResponse($message = "", $code ="", $vod_video_details);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /** 
     * @method vod_videos_check_coupon_code()
     *
     * @uses check the coupon code is valid
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

    public function vod_videos_check_coupon_code(Request $request) {

        try {

            // Validation start

            $rules = [
                    'vod_video_id' => 'required|exists:vod_videos,id',
                    'coupon_code' => 'nullable|exists:coupons,coupon_code',
                    ];

            $custom_errors = ['vod_video_id' => api_error(129)];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);
            
            // Validation end

            // Check the live video is streaming

            $vod_video_details = VodVideo::where('id',  $request->vod_video_id)->Approved()->first();

            if(!$vod_video_details) {

                throw new Exception(api_error(129), 129);
                
            }

            // check the video payment status || whether user already paid

            if($vod_video_details->is_pay_per_view == NO || $vod_video_details->amount <= 0 ) {

                throw new Exception(api_error(167), 167);
                
            }

            $is_needs_to_pay = Helper::vod_video_payment_status($request->id, $vod_video_details);

            if($is_needs_to_pay == NO) {

                throw new Exception(api_error(134), 134);
                
            }

            // Coupon code availablity and calculator

            $coupon_response = PaymentRepo::coupon_code_check_availablity($request)->getData();

            if($coupon_response->success == false) {
                throw new Exception($coupon_response->error, $coupon_response->error_code);
            }

            $coupon_code_details = $coupon_response->data;

            $calculator_response = PaymentRepo::coupon_code_calcualtor($coupon_code_details, $vod_video_details->amount)->getData();

            if($coupon_response->success == false) {
                
                throw new Exception($coupon_response->error, $coupon_response->error_code);
            }

            $data = $calculator_response->data;

            $data->vod_video_id = $request->vod_video_id;

            return $this->sendResponse($message="", $code="", $data);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method vod_videos_payment_by_card()
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

    public function vod_videos_payment_by_card(Request $request) {

        try {

            DB::beginTransaction();

            // Validation start

            $rules = [
                    'vod_video_id' => 'required|exists:vod_videos,id',
                    'coupon_code' => 'nullable|exists:coupons,coupon_code',
                    ];

            $custom_errors = ['vod_video_id' => api_error(129)];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);
            
            // Validation end

            // Check the live video is streaming

            $vod_video_details = VodVideo::where('id',  $request->vod_video_id)->Approved()->first();

            if(!$vod_video_details) {

                throw new Exception(api_error(129), 129);
                
            }

            // check the video payment status || whether user already paid

            if($vod_video_details->is_pay_per_view == NO || $vod_video_details->amount <= 0) {

                $code = 136;

                goto successReponse;
                
            }

            $is_needs_to_pay = Helper::vod_video_payment_status($request->id, $vod_video_details);

            if($is_needs_to_pay == NO) {

                $code = 136;

                goto successReponse;
                
            }

            $request->request->add(['payment_mode' => CARD]);

            $total = $user_pay_amount = $vod_video_details->amount ?? 0.00;

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

                    $is_coupon_code_applied = YES;

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
                
                $card_payment_response = PaymentRepo::vod_videos_payment_by_stripe($request, $vod_video_details)->getData();

                if($card_payment_response->success == false) {

                    throw new Exception($card_payment_response->error, $card_payment_response->error_code);
                    
                }

                $card_payment_data = $card_payment_response->data;

                $request->request->add(['paid_amount' => $card_payment_data->paid_amount, 'payment_id' => $card_payment_data->payment_id, 'paid_status' => $card_payment_data->paid_status]);

            }

            $payment_response = PaymentRepo::vod_videos_payment_save($request, $vod_video_details)->getData();

            if($payment_response->success) {
                
                DB::commit();

                $code = 137;

            } else {

                throw new Exception($payment_response->error, $payment_response->error_code);
            }          

            successReponse:

            $data['vod_video_id'] = $request->vod_video_id;

            $data['payment_mode'] = CARD;

            return $this->sendResponse(api_success($code), $code, $data);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method vod_videos_payment_by_paypal()
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

    public function vod_videos_payment_by_paypal(Request $request) {

        try {

           DB::beginTransaction();

            // Validation start

            $rules = [
                    'vod_video_id' => 'required|exists:vod_videos,id',
                    'coupon_code' => 'nullable|exists:coupons,coupon_code',
                    ];

            $custom_errors = ['vod_video_id' => api_error(129)];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);
            
            // Validation end

            // Check the live video is streaming

            $vod_video_details = VodVideo::where('id',  $request->vod_video_id)->Approved()->first();

            if(!$vod_video_details) {

                throw new Exception(api_error(129), 129);
                
            }

            // check the video payment status || whether user already paid

            if($vod_video_details->is_pay_per_view == NO || $vod_video_details->amount <= 0) {

                $code = 136;

                goto successReponse;
                
            }

            $is_needs_to_pay = Helper::vod_video_payment_status($request->id, $vod_video_details);

            // dd($is_needs_to_pay);

            if($is_needs_to_pay == NO) {

                $code = 136;

                goto successReponse;
                
            }

            $request->request->add(['payment_mode' => CARD]);

            $total = $user_pay_amount = $vod_video_details->amount ?? 0.00;

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

            $payment_response = PaymentRepo::vod_videos_payment_save($request, $vod_video_details)->getData();

            if($payment_response->success) {
                
                DB::commit();

                $code = 137;

            } else {

                throw new Exception($payment_response->error, $payment_response->error_code);
                
            }            

            successReponse:

            $data['vod_video_id'] = $request->vod_video_id;

            $data['payment_mode'] = PAYPAL;

            return $this->sendResponse(api_success($code), $code, $data);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /**
     * @method redeems_index
     *
     * @uses Used to get the wallet details
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param
     *
     * @return
     */

    public function redeems_index(Request $request) {

        try {

            DB::beginTransaction();

            $redeem_details = Redeem::where('user_id', $request->id)->first();

            if(!$redeem_details) {

                $redeem_details = new Redeem;

                $redeem_details->user_id = $request->id;

                $redeem_details->total = $redeem_details->paid = $redeem_details->remaining = 0.00;

                $redeem_details->save();

                DB::commit();

            }

            $data = new \stdClass;

            $data->redeem_details = $redeem_details; // @todo change the value

            $request->request->add(['skip' => $this->skip, 'take' => $this->take, 'timezone' => $request->timezone]);

            $data->payments = UserRepo::redeem_requests_list_response($request);

            return $this->sendResponse($message = "", $code = 200, $data);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }
    
    }

    /**
     * @method redeems_requests
     *
     * @uses Used to redeem request created by the user
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param
     *
     * @return
     */

    public function redeems_requests(Request $request) {

        $request->request->add(['skip' => $this->skip, 'take' => $this->take, 'timezone' => $request->timezone]);

        $data = UserRepo::redeem_requests_list_response($request);

        return $this->sendResponse($message = "", $code = 200, $data);
    
    }

    /** 
     * @method redeems_requests_send()
     *
     * @uses Provider Send Redeem request to Admin
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param - 
     * 
     * @return JSON response
     *
     */

    public function redeems_requests_send(Request $request) {

        try {


            DB::beginTransaction();

            $redeem_details = Redeem::where('user_id', $request->id)->first();

            if(!$redeem_details) {

                throw new Exception(api_error(6003), 6003);
                
            }

            $minimum_redeem = Setting::get('minimum_redeem', 0);

            if($redeem_details->remaining < $minimum_redeem) {

                throw new Exception(api_error(135), 135);
                
            }

            $redeem_request = new RedeemRequest;

            $redeem_request->user_id = $request->id;

            $redeem_request->request_amount = $redeem_details->remaining;

            $redeem_request->status = REDEEM_REQUEST_PROCESSING;

            if($redeem_request->save()) {

                $redeem_details->remaining -= $redeem_details->remaining;

                $redeem_details->save();
                
                DB::commit();

                return $this->sendResponse($message = api_success(117), $code = 117);
            
            }

            throw new Exception(api_error(136), 136);
            
        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method redeems_requests_cancel()
     *
     * @uses cancel redeem request which created by provider
     *
     * @created Vidhya R
     *
     * @updated Vidhya R
     *
     * @param - integer redeem_request_id
     * 
     * @return JSON response
     *
     */

    public function redeems_requests_cancel(Request $request) {

        try {

            // Validation start

            $rules = [
                    'redeem_request_id' => 'required|exists:redeem_requests,id,user_id,'.$request->id
                ];

            $custom_errors = ['exists' => api_error(137)];

            Helper::custom_validator($request->all(), $rules, $custom_errors);

            // Validation end

            DB::beginTransaction();

            $redeem_details = Redeem::where('user_id', $request->id)->first();

            $redeem_request = RedeemRequest::find($request->redeem_request_id);

            if(!$redeem_details || !$redeem_request) {

                throw new Exception(api_error(137), 137);
                
            }

            // Check the status of the request

            if(in_array($redeem_request->status, [REDEEM_REQUEST_PAID, REDEEM_REQUEST_CANCEL])) {

                throw new Exception(api_error(138), 138);

            }

            $redeem_details->remaining += $redeem_request->request_amount;

            $redeem_details->save();

            // Update the redeeem request Status

            $redeem_request->status = REDEEM_REQUEST_CANCEL;

            $redeem_request->save();

            DB::commit();

            return $this->sendResponse($message = api_success(118), $code = 118);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method livetv_owner_list()
     *
     * @uses get the owner live tv list
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

    public function livetv_owner_list(Request $request) {

        try {

            $query = CustomLiveVideo::where('custom_live_videos.user_id', $request->id)->OwnerResponse()->orderBy('custom_live_videos.created_at', 'desc');

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
     * @method livetv_owner_view()
     *
     * @uses get the owner live tv list
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

    public function livetv_owner_view(Request $request) {

        try {

            $custom_live_video_details = CustomLiveVideo::where('custom_live_videos.user_id', $request->id)
                    ->where('custom_live_videos.id', $request->custom_live_video_id)
                    ->OwnerResponse()
                    ->first();

            return $this->sendResponse($message = '' , $code = '', $custom_live_video_details);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /**
     * @method livetv_owner_save()
     *
     * @uses update the selected card as default
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param integer id
     * 
     * @return JSON Response
     */
    public function livetv_owner_save(Request $request) {

        try {

            $user_details = $this->loginUser;

            // Validation start

            $rules = [
                    'title' => 'required|max:255',
                    'description' => 'required',
                    'rtmp_video_url'=>'required|max:255',
                    'hls_video_url'=>'required|max:255',
                    'image' => 'mimes:jpeg,jpg,png',
                    'custom_live_video_id' => 'exists:custom_live_videos,id'
                ];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);

            // Validation end

            DB::beginTransaction();

            $custom_live_video = ($request->custom_live_video_id) ? CustomLiveVideo::find($request->custom_live_video_id) : new CustomLiveVideo;

            $custom_live_video->user_id = $request->id;
            
            $custom_live_video->title = $request->title ?? $custom_live_video->title;

            $custom_live_video->description = $request->description ?? $custom_live_video->description;

            $custom_live_video->rtmp_video_url = $request->rtmp_video_url ?? $custom_live_video->rtmp_video_url;

            $custom_live_video->hls_video_url = $request->hls_video_url ?? $custom_live_video->hls_video_url;

            $custom_live_video->status = !$request->custom_live_video_id ? APPROVED : $custom_live_video->status;

            if($request->hasFile('image')) {

                if($request->id) {

                    Helper::storage_delete_file($custom_live_video->image,LIVETV_IMAGE_PATH);

                }

                $custom_live_video->image = Helper::storage_upload_file($request->image,LIVETV_IMAGE_PATH);
            }
                
            if($custom_live_video->save()) {

                DB::commit();

                $code = $request->custom_live_video_id ? 128 : 127 ;

                $data['custom_live_video_id'] = $custom_live_video->id;

                $data['status'] = $custom_live_video->status;

                return $this->sendResponse(api_success($code), $code, $data);

            }

            throw new Exception(api_error(150), 150);
            
        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /**
     * @method livetv_owner_delete()
     *
     * @uses update the selected card as default
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param integer id
     * 
     * @return JSON Response
     */
    public function livetv_owner_delete(Request $request) {

        try {

            DB::beginTransaction();

            // validation start

            $rules = [
                    'custom_live_video_id' => 'required|exists:custom_live_videos,id,user_id,'.$request->id
                    ];
            $custom_errors = [
                'custom_live_video_id' => api_error(150),
            ];

            Helper::custom_validator($request->all(), $rules, $custom_errors);
            
            // validation end

            $custom_live_video_details = CustomLiveVideo::where('custom_live_videos.user_id', $request->id)->where('custom_live_videos.id',  $request->custom_live_video_id)->first();

            if(!$custom_live_video_details) {

                throw new Exception(api_error(150), 150);
            }

            $custom_live_video_details->delete();

            DB::commit();

            return $this->sendResponse(api_success(126), 126);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }
    
    /**
     * @method livetv_owner_status()
     *
     * @uses update the selected card as default
     *
     * @created Vithya R
     *
     * @updated Vithya R
     *
     * @param integer id
     * 
     * @return JSON Response
     */
    public function livetv_owner_status(Request $request) {

        try {

            DB::beginTransaction();

            // validation start

            $rules = [
                    'custom_live_video_id' => 'required|exists:custom_live_videos,id,user_id,'.$request->id
                    ];
            $custom_errors = [
                'custom_live_video_id' => api_error(150),
            ];

            Helper::custom_validator($request->all(), $rules, $custom_errors);
            
            // validation end

            $custom_live_video_details = CustomLiveVideo::where('custom_live_videos.user_id', $request->id)->where('custom_live_videos.id',  $request->custom_live_video_id)->first();

            if(!$custom_live_video_details) {

                throw new Exception(api_error(150), 150);
            }
        
            $custom_live_video_details->status = $custom_live_video_details->status == YES ? NO : YES;

            $custom_live_video_details->save();

            DB::commit();

            $code = $custom_live_video_details->status == YES ? 124 : 125;

            $data['custom_live_video_id'] = $request->custom_live_video_id;

            $data['status'] = $custom_live_video_details->status;

            return $this->sendResponse(api_success($code), $code, $data);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /** 
     * @method livetv_list()
     *
     * @uses get the owner live tv list
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

    public function livetv_list(Request $request) {

        try {

            $block_user_ids = Helper::get_bloked_users($request->id);

            $query = CustomLiveVideo::LiveVideoResponse()->has('user')->whereNotIn('custom_live_videos.id', $block_user_ids)->orderBy('custom_live_videos.created_at', 'desc');

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
     * @method livetv_suggestions()
     *
     * @uses get the owner live tv list
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

    public function livetv_suggestions(Request $request) {

        try {

            $block_user_ids = Helper::get_bloked_users($request->id);

            $query = CustomLiveVideo::LiveVideoResponse()->whereNotIn('custom_live_videos.id', $block_user_ids)->orderBy(DB::raw('RAND()'));

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
     * @method livetv_search()
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

    public function livetv_search(Request $request) {

        try {

            $base_query = CustomLiveVideo::LiveVideoResponse();

            $base_query = $base_query->where('title', 'like', "%".$request->key."%");

            $custom_live_videos = $base_query->skip($this->skip)->take($this->take)
                                ->orderBy('custom_live_videos.id', 'desc')
                                ->get();

            return $this->sendResponse($message = '', $code = '', $custom_live_videos);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method livetv_view()
     *
     * @uses get the owner live tv view
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

    public function livetv_view(Request $request) {

        try {

            $custom_live_video_details = CustomLiveVideo::where('custom_live_videos.id', $request->custom_live_video_id)
                    ->LiveVideoResponse()
                    ->first();

            return $this->sendResponse($message = '' , $code = '', $custom_live_video_details);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /**
     * @method subscriptions_index()
     *
     * @uses To display all the subscription plans
     *
     * @created vithya R
     *
     * @updated Vidhya R
     *
     * @param request id
     *
     * @return JSON Response
     */
    public function subscriptions_index(Request $request) {

        try {

            $base_query = Subscription::BaseResponse()->where('subscriptions.status' , APPROVED);

            $is_user_subscribed_free_plan = $this->loginUser->one_time_subscription ?? NO;

            if ($is_user_subscribed_free_plan) {

               $base_query->where('subscriptions.amount','>', 0);

            }

            $subscriptions = $base_query->orderBy('amount', 'asc')->get();

            return $this->sendResponse($message = '' , $code = '', $subscriptions);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }
    
    }

    /**
     * @method subscriptions_view()
     *
     * @uses get the selected subscription details
     *
     * @created vithya R
     *
     * @updated Vidhya R
     *
     * @param integer $subscription_id
     *
     * @return JSON Response
     */
    public function subscriptions_view(Request $request) {

        try {

            $subscription_details = Subscription::BaseResponse()->where('subscriptions.status' , APPROVED)->where('subscriptions.id', $request->subscription_id)->first();

            if(!$subscription_details) {
                throw new Exception(api_error(151), 151);   
            }

            return $this->sendResponse($message = '' , $code = '', $subscription_details);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }
    
    }

    /** 
     * @method subscriptions_check_coupon_code()
     *
     * @uses check the coupon code is available or not
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

    public function subscriptions_check_coupon_code(Request $request) {

        try {

            // Validation start

            $rules = [
                    'subscription_id' => 'required|exists:subscriptions,id',
                    'coupon_code' => 'nullable|exists:coupons,coupon_code',
                    ];

            $custom_errors = ['subscription_id' => api_error(151)];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);
            
            // Validation end

            // Check the subscription is available

            $subscription_details = Subscription::where('id',  $request->subscription_id)
                                    ->Approved()
                                    ->first();

            if(!$subscription_details) {

                throw new Exception(api_error(161), 161);
                
            }

            // Coupon code availablity and calculator

            $coupon_response = PaymentRepo::coupon_code_check_availablity($request)->getData();

            if($coupon_response->success == false) {
                throw new Exception($coupon_response->error, $coupon_response->error_code);
            }

            $coupon_code_details = $coupon_response->data;

            $calculator_response = PaymentRepo::coupon_code_calcualtor($coupon_code_details, $subscription_details->amount)->getData();

            if($coupon_response->success == false) {
                
                throw new Exception($coupon_response->error, $coupon_response->error_code);
            }

            $data = $calculator_response->data;

            $data->subscription_id = $request->subscription_id;

            return $this->sendResponse($message="", $code="", $data);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method subscriptions_payment_by_card()
     *
     * @uses pay for subscription using paypal
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

    public function subscriptions_payment_by_card(Request $request) {

        try {

            DB::beginTransaction();

            // Validation start

            $rules = [
                    'subscription_id' => 'required|exists:subscriptions,id',
                    'coupon_code' => 'nullable|exists:coupons,coupon_code',
                    ];

            $custom_errors = ['subscription_id' => api_error(151)];

            Helper::custom_validator($request->all(), $rules, $custom_errors);
            
            // Validation end

           // Check the subscription is available

            $subscription_details = Subscription::where('id',  $request->subscription_id)
                                    ->Approved()
                                    ->first();

            if(!$subscription_details) {

                throw new Exception(api_error(161), 161);
                
            }

            $is_user_subscribed_free_plan = $this->loginUser->one_time_subscription ?? NO;

            if($subscription_details->amount <= 0 && $is_user_subscribed_free_plan) {

                throw new Exception(api_error(176), 176);
                
            }

            $request->request->add(['payment_mode' => CARD]);

            $total = $user_pay_amount = $subscription_details->amount ?? 0.00;

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

                    $is_coupon_code_applied = YES;
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
            
            if($user_pay_amount > 0) {

                // Check the user have the cards

                $card_details = Card::where('user_id', $request->id)->where('is_default', YES)->first();

                // If the user doesn't have cards means the payment will switch to COD

                if(!$card_details) {

                    throw new Exception(api_error(163), 163); 

                }

                $request->request->add(['customer_id' => $card_details->customer_id]);
                
                $card_payment_response = PaymentRepo::subscriptions_payment_by_stripe($request, $subscription_details)->getData();

                if($card_payment_response->success == false) {

                    throw new Exception($card_payment_response->error, $card_payment_response->error_code);
                    
                }

                $card_payment_data = $card_payment_response->data;

                $request->request->add(['paid_amount' => $card_payment_data->paid_amount, 'payment_id' => $card_payment_data->payment_id, 'paid_status' => $card_payment_data->paid_status]);

            }

            $payment_response = PaymentRepo::subscriptions_payment_save($request, $subscription_details)->getData();

            if($payment_response->success) {
                
                DB::commit();

                $code = 137;

                return $this->sendResponse(api_success($code), $code, $payment_response->data);

            } else {

                throw new Exception($payment_response->error, $payment_response->error_code);
                
            }
        
        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method subscriptions_payment_by_paypal()
     *
     * @uses pay for subscription using paypal
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

    public function subscriptions_payment_by_paypal(Request $request) {

        try {

            DB::beginTransaction();

            // Validation start

            $rules = [
                    'subscription_id' => 'required|exists:subscriptions,id',
                    'coupon_code' => 'nullable|exists:coupons,coupon_code',
                    'payment_id' => 'required',
                    ];

            $custom_errors = ['subscription_id' => api_error(151)];

            Helper::custom_validator($request->all(), $rules, $custom_errors = []);
            
            // Validation end

           // Check the subscription is available

            $subscription_details = Subscription::where('id',  $request->subscription_id)
                                    ->Approved()
                                    ->first();

            if(!$subscription_details) {

                throw new Exception(api_error(161), 161);
                
            }

            $is_user_subscribed_free_plan = $this->loginUser->one_time_subscription ?? NO;

            if($subscription_details->amount <= 0 && $is_user_subscribed_free_plan) {

                throw new Exception(api_error(176), 176);
                
            }

            $request->request->add(['payment_mode' => PAYPAL]);

            $total = $user_pay_amount = $subscription_details->amount ?? 0.00;

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
                'payment_id' => $request->payment_id,
            ]);

            $payment_response = PaymentRepo::subscriptions_payment_save($request, $subscription_details)->getData();

            if($payment_response->success) {
                
                DB::commit();

                $code = 137;

                return $this->sendResponse(api_success($code), $code, $payment_response->data);

            } else {

                throw new Exception($payment_response->error, $payment_response->error_code);
                
            }
        
        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /**
     * @method subscriptions_history()
     *
     * @uses get the selected subscription details
     *
     * @created vithya R
     *
     * @updated Vidhya R
     *
     * @param integer $subscription_id
     *
     * @return JSON Response
     */
    public function subscriptions_history(Request $request) {

        try {

            $user_subscriptions = UserSubscription::BaseResponse()->where('user_id' , $request->id)->skip($this->skip)->take($this->take)->orderBy('user_subscriptions.id', 'desc')->get();

            foreach ($user_subscriptions as $key => $value) {

                $value->plan_text = formatted_plan($value->plan ?? 0);

                $value->expiry_date = common_date($value->expiry_date, $this->timezone, 'M, d Y');

                $value->show_autorenewal_options = 
                $value->show_autorenewal_pause_btn = 
                $value->show_autorenewal_enable_btn = HIDE;

                if($key == 0) {

                    $value->show_autorenewal_options = ($value->status && $value->subscription_amount > 0)? SHOW : HIDE;

                    if($value->show_autorenewal_options == SHOW) {

                        $value->show_autorenewal_pause_btn = $value->is_cancelled == AUTORENEWAL_ENABLED ? HIDE : SHOW;

                        $value->show_autorenewal_enable_btn = $value->show_autorenewal_pause_btn ? NO : YES;
                    }

                }
            
            }

            return $this->sendResponse($message = '' , $code = '', $user_subscriptions);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }
    
    }

    /**
     * @method subscriptions_autorenewal_status
     *
     * @uses To prevent automatic subscriptioon, user have option to cancel subscription
     *
     * @created vithya R
     *
     * @updated vithya R
     *
     * @param 
     *
     * @return json reponse
     */
    public function subscriptions_autorenewal_status(Request $request) {

        try {

            DB::beginTransaction();

            $user_subscription_details = UserSubscription::where('user_subscriptions.id', $request->user_subscription_id)->where('status', DEFAULT_TRUE)->where('user_id', $request->id)->first();

            if(!$user_subscription_details) {

                throw new Exception(api_error(152), 152);   

            }

            // Check the subscription is already cancelled

            if($user_subscription_details->is_cancelled == AUTORENEWAL_CANCELLED) {

                $user_subscription_details->is_cancelled = AUTORENEWAL_ENABLED;

            } else {

                $user_subscription_details->is_cancelled = AUTORENEWAL_CANCELLED;

                $user_subscription_details->cancel_reason = $request->cancel_reason;

            }

            $user_subscription_details->save();

            DB::commit();

            $data['user_subscription_id'] = $request->user_subscription_id;

            $data['is_autorenewal_status'] = $user_subscription_details->is_cancelled;

            $code = $user_subscription_details->is_cancelled == AUTORENEWAL_CANCELLED ? 130 : 129;

            return $this->sendResponse(api_success($code) , $code, $data);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /** 
     * @method users_suggestions()
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

    public function users_suggestions(Request $request) {

        try {

            // omit the live videos - blocked by you, who blocked you & your live videos

            $blocked_user_ids = Helper::get_bloked_users($request->id);

            // Followers ids
            $my_followings_ids = Follower::where('follower', $request->id)->get()->pluck('user_id')->toArray();
            
            $omit_user_ids = array_merge($blocked_user_ids, $my_followings_ids);

            $suggestion_query = User::whereNotIn('users.id', $omit_user_ids)
                                    ->where('users.id', '!=', $request->id)
                                    ->where('users.is_content_creator', CREATOR_STATUS)
                                    ->Approved()
                                    ->FollowResponse()
                                    ->skip($this->skip)
                                    ->take($this->take)
                                    ->orderBy('users.updated_at', 'desc');

            $is_content_creator = $this->loginUser->is_content_creator ?? CREATOR_STATUS;

            $suggestions = $suggestion_query->get();

            foreach ($suggestions as $key => $suggestion) {

                $blocked_user_ids = Helper::get_bloked_users($suggestion->user_id);

                $suggestion->total_followers = Helper::total_followers($suggestion->user_id, $blocked_user_ids);

                $suggestion->total_followings = Helper::total_followings($suggestion->user_id, $blocked_user_ids);

                $suggestion->is_owner = $request->id == $suggestion->follower ? YES : NO;

                $suggestion->show_follow = $suggestion->show_unfollow = $suggestion->show_block = $suggestion->show_unblock = NO;

                if($suggestion->is_owner == NO) {

                    $is_you_following = Helper::is_you_following($request->id, $suggestion->user_id);

                    $suggestion->show_follow = $is_you_following ? HIDE : SHOW;

                    $suggestion->show_unfollow = $is_you_following ? SHOW : HIDE;

                    $is_you_blocked = Helper::is_you_blocked($request->id, $suggestion->user_id);

                    $suggestion->show_block = $is_you_blocked ? HIDE : SHOW;

                    $suggestion->show_unblock = $is_you_blocked ? SHOW : HIDE;
                }
            }

            $data['suggestions'] = $suggestions;
            
            $data['total_live_videos'] = Helper::total_live_videos($request->id, $blocked_user_ids);

            $data['total_followers'] = Helper::total_followers($request->id, $blocked_user_ids);

            $data['total_followings'] = Helper::total_followings($request->id, $blocked_user_ids);


            return $this->sendResponse($message = '', $code = '', $data);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method users_follow()
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

    public function users_follow(Request $request) {

        try {

            DB::beginTransaction();

            // Validation start

            $rules = [
                    'user_id' => 'required|exists:users,id'
                    ];

            $custom_errors = ['user_id' => api_error(153)];

            Helper::custom_validator($request->all(), $rules, $custom_errors);
            
            // Validation end

            if($request->id == $request->user_id) {

                throw new Exception(api_error(154), 154);

            }

            $follow_user_details = User::where('id', $request->user_id)->Approved()->first();

            if(!$follow_user_details) {

                throw new Exception(api_error(153), 153);
            }

            // Check the user already following the selected users

            $follower_details = Follower::where('user_id', $request->user_id)->where('follower', $request->id)->where('status', YES)->first();

            if($follower_details) {

                throw new Exception(api_error(155), 155);

            }

            // The viewer can follow only content creators.

            if($this->loginUser->is_content_creator == NO && $follow_user_details->is_content_creator == NO) {

                throw new Exception(api_error(156), 156);
            }

            $follower = new Follower;

            $follower->user_id = $request->user_id;

            $follower->follower = $request->id;

            $follower->status = DEFAULT_TRUE;

            $follower->save();

            DB::commit();

            $data['user_id'] = $request->user_id;

            $data['is_follow'] = NO;

            return $this->sendResponse(api_success(132), $code = 132, $data);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method users_unfollow()
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

    public function users_unfollow(Request $request) {

        try {

            DB::beginTransaction();

            // Validation start

            $rules = ['user_id' => 'required|exists:users,id'];

            $custom_errors = ['user_id' => api_error(153)];

            Helper::custom_validator($request->all(), $rules, $custom_errors);
            
            // Validation end

            if($request->id == $request->user_id) {

                throw new Exception(api_error(157), 157);

            }

            // Check the user already following the selected users

            $follower_details = Follower::where('user_id', $request->user_id)->where('follower', $request->id)->where('status', YES)->delete();

            DB::commit();

            $data['user_id'] = $request->user_id;

            $data['is_follow'] = YES;

            return $this->sendResponse(api_success(133), $code = 133, $data);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method followers()
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

    public function followers(Request $request) {

        try {

            // omit the live videos - blocked by you, who blocked you & your live videos

            $blocked_user_ids = Helper::get_bloked_users($request->id);

            $users = Follower::CommonResponse()
                    ->where('user_id', $request->id)
                    ->whereNotIn('follower', $blocked_user_ids)
                    ->skip($this->skip)
                    ->take($this->take)
                    ->orderBy('followers.created_at', 'desc')
                    ->get();

            foreach ($users as $key => $user_details) {

                $user_details->total_followers = Helper::total_followers($user_details->user_id, $blocked_user_ids);

                $user_details->total_followings = Helper::total_followings($user_details->user_id, $blocked_user_ids);

                $user_details->is_owner = $request->id == $user_details->follower ? YES : NO;

                $is_you_following = Helper::is_you_following($request->id, $user_details->user_id);

                $user_details->show_follow = $is_you_following ? HIDE : SHOW;

                $user_details->show_unfollow = $is_you_following ? SHOW : HIDE;

                $is_you_blocked = Helper::is_you_blocked($request->id, $user_details->user_id);

                $user_details->show_block = $is_you_blocked ? HIDE : SHOW;

                $user_details->show_unblock = $is_you_blocked ? SHOW : HIDE;

            }

            return $this->sendResponse($message = "", $code = "", $users);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method followings()
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

    public function followings(Request $request) {

        try {

            // omit the live videos - blocked by you, who blocked you & your live videos

            $blocked_user_ids = Helper::get_bloked_users($request->id);

            // $blocked_user_ids 

            $users = Follower::FollowingResponse()
                    ->where('follower', $request->id)
                    ->whereNotIn('user_id', $blocked_user_ids)
                    ->skip($this->skip)
                    ->take($this->take)
                    ->orderBy('followers.created_at', 'desc')
                    ->get();

            foreach ($users as $key => $user_details) {

                $user_details->total_followers = Helper::total_followers($user_details->user_id, $blocked_user_ids);

                $user_details->total_followings = Helper::total_followings($user_details->user_id, $blocked_user_ids);

                $user_details->is_owner = $request->id == $user_details->user_id ? YES : NO;

                $is_you_following = Helper::is_you_following($request->id, $user_details->user_id);

                $user_details->show_follow = $is_you_following ? HIDE : SHOW;

                $user_details->show_unfollow = $is_you_following ? SHOW : HIDE;

                $is_you_blocked = Helper::is_you_blocked($request->id, $user_details->user_id);

                $user_details->show_block = $is_you_blocked ? HIDE : SHOW;

                $user_details->show_unblock = $is_you_blocked ? SHOW : HIDE;

            }

            return $this->sendResponse($message = "", $code = "", $users);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method users_block()
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

    public function users_block(Request $request) {

        try {

            DB::beginTransaction();

            // Validation start

            $rules = [
                    'user_id' => 'required|exists:users,id'
                    ];

            $custom_errors = ['user_id' => api_error(153)];

            Helper::custom_validator($request->all(), $rules, $custom_errors);
            
            // Validation end

            if($request->id == $request->user_id) {

                throw new Exception(api_error(158), 158);

            }

            // Check the user already following the selected users

            $check_block_list = BlockList::where('user_id', $request->id)
                                ->where('block_user_id', $request->user_id)
                                ->where('status', YES)
                                ->first();

            if($check_block_list) {
                throw new Exception(api_error(160), 160);
            }

            $block_list = new BlockList;

            $block_list->user_id = $request->id;

            $block_list->block_user_id = $request->user_id;

            $block_list->status = YES;

            $block_list->save();

            DB::commit();

            $data['user_id'] = $request->user_id;

            $data['is_block'] = NO;

            return $this->sendResponse(api_success(134), $code = 134, $data);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method users_unblock()
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

    public function users_unblock(Request $request) {

        try {

            DB::beginTransaction();

            // Validation start

            $rules = ['user_id' => 'required|exists:users,id'];

            $custom_errors = ['user_id' => api_error(153)];

            Helper::custom_validator($request->all(), $rules, $custom_errors);
            
            // Validation end

            if($request->id == $request->user_id) {

                throw new Exception(api_error(159), 159);

            }

            // Check the user already following the selected users

            $block_list = BlockList::where('user_id', $request->id)
                                ->where('block_user_id', $request->user_id)
                                ->delete();

            DB::commit();

            $data['user_id'] = $request->user_id;

            $data['is_block'] = YES;

            return $this->sendResponse(api_success(135), $code = 135, $data);

        } catch(Exception $e) {

            DB::rollback();

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method users_blocked_list()
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

    public function users_blocked_list(Request $request) {

        try {

            $block_lists = BlockList::where('user_id', $request->id)->CommonResponse()
                                ->skip($this->skip)->take($this->take)
                                ->orderBy('block_lists.created_at', 'desc')
                                ->get();

            return $this->sendResponse($message = "", $code = "", $block_lists);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }


    /**
     * @method galleries()
     *
     * @uses To load galleries based on user id
     *
     * @created vithya R
     *
     * @updated 
     *
     * @param model image object - $request
     *
     * @return response of succes failure 
     */
    public function galleries(Request $request) {

        try {

            $request->request->add(['user_id' => $request->id]);

            $response = StreamerGalleryRepo::streamer_galleries_list($request)->getData();

            if($response->success) {

                return $this->sendResponse($message = "", $code = "", $response->data);

            }

            throw new Exception($response->error_messages, $response->error_code);
                
        } catch (Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());

        }
    
    }

    /**
     * @method galleries_save()
     *
     * @uses To save gallery details of the streamer
     *
     * @created vithya R
     *
     * @updated 
     *
     * @param object $request - Model Object
     *
     * @return response of success / Failure
     */
    public function galleries_save(Request $request) {

        try {

            $request->request->add(['user_id' => $request->id]);

            $response = StreamerGalleryRepo::streamer_galleries_save($request)->getData();

            if($response->success) {

                return $this->sendResponse($response->message, $response->code ?? 200);

            }

            throw new Exception($response->error_messages, $response->error_code);
                
        } catch (Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());

        }

    }

    /**
     * @method galleries_delete()
     *
     * @uses To delete particular image based on id
     *
     * @created Vithya R
     *
     * @updated  
     *
     * @param model image object - $request
     *
     * @return response of succes failure 
     */
    public function galleries_delete(Request $request) {

        try {

            $request->request->add(['user_id' => $request->id]);

            $response = StreamerGalleryRepo::streamer_galleries_delete($request)->getData();

            if($response->success) {

                return $this->sendResponse($response->message, $response->code ?? 200);

            }

            throw new Exception($response->error_messages, $response->error_code);
                
        } catch (Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }
    
    }

    /**
     * @method other_profile
     *
     * @uses To view the user profile based on the other user
     *
     * @created vithya R
     *
     * @updated vithya R
     *
     * @param object $request Peer Id
     *
     * @return other profile details
     */
    public function other_profile(Request $request) {

        try {

            $user_details = User::where('unique_id' , $request->unique_id)->OtherResponse()->first();
    
            if(!$user_details) { 

                throw new Exception(api_error(153) , 153);
            }

            $blocked_user_ids = Helper::get_bloked_users($user_details->user_id);

            $user_details->total_followers = Helper::total_followers($user_details->user_id, $blocked_user_ids);

            $user_details->total_followings = Helper::total_followings($user_details->user_id, $blocked_user_ids);

            $user_details->is_user_live = Helper::is_user_live($user_details->user_id);

            $is_content_creator = $this->loginUser->is_content_creator ?? NO;

            if($is_content_creator == NO && $user_details->is_content_creator == NO) {

                $user_details->show_follow = $user_details->show_unfollow = HIDE;

                $user_details->show_block = $user_details->show_unblock = HIDE;

            } else {

                $is_you_following = Helper::is_you_following($request->id, $user_details->user_id);

                $user_details->show_follow = $is_you_following ? HIDE : SHOW;

                $user_details->show_unfollow = $is_you_following ? SHOW : HIDE;

                $is_you_blocked = Helper::is_you_blocked($request->id, $user_details->user_id);

                $user_details->show_block = $is_you_blocked ? HIDE : SHOW;

                $user_details->show_unblock = $is_you_blocked ? SHOW : HIDE;

            }
            
            return $this->sendResponse($message = "", $code = "", $user_details);

        } catch (Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }

    }

    /** 
     * @method other_profile_followers()
     *
     * @uses selected user followers list
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

    public function other_profile_followers(Request $request) {

        try {

            // omit the live videos - blocked by you, who blocked you & your live videos

            $other_blocked_user_ids = Helper::get_bloked_users($request->user_id);

            $logged_in_blocked_user_ids = Helper::get_bloked_users($request->id);

            $blocked_user_ids = array_merge($other_blocked_user_ids, $logged_in_blocked_user_ids);

            $users = Follower::FollowerResponse()
                    ->where('user_id', $request->user_id)
                    ->whereNotIn('follower', $blocked_user_ids)
                    ->skip($this->skip)
                    ->take($this->take)
                    ->orderBy('followers.created_at', 'desc')
                    ->get();

            foreach ($users as $key => $user_details) {

                $user_details->total_followers = Helper::total_followers($user_details->user_id, $blocked_user_ids);

                $user_details->total_followings = Helper::total_followings($user_details->user_id, $blocked_user_ids);

                $user_details->is_owner = $request->id == $user_details->follower ? YES : NO;


                $user_details->show_follow = $user_details->show_unfollow = $user_details->show_block = $user_details->show_unblock = NO;

                if($user_details->is_owner == NO) {

                    $is_you_following = Helper::is_you_following($request->id, $user_details->user_id);

                    $user_details->show_follow = $is_you_following ? HIDE : SHOW;

                    $user_details->show_unfollow = $is_you_following ? SHOW : HIDE;

                    $is_you_blocked = Helper::is_you_blocked($request->id, $user_details->user_id);

                    $user_details->show_block = $is_you_blocked ? HIDE : SHOW;

                    $user_details->show_unblock = $is_you_blocked ? SHOW : HIDE;
                }

            }

            return $this->sendResponse($message = "", $code = "", $users);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }

    /** 
     * @method other_profile_followings()
     *
     * @uses selected user followings list
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

    public function other_profile_followings(Request $request) {

        try {

            // omit the live videos - blocked by you, who blocked you

            $blocked_user_ids = Helper::get_bloked_users($request->user_id);

            $logged_in_blocked_user_ids = Helper::get_bloked_users($request->id);

            // $blocked_user_ids 

            $users = Follower::FollowingResponse()
                    ->where('follower', $request->user_id)
                    ->whereNotIn('user_id', $blocked_user_ids)
                    ->skip($this->skip)
                    ->take($this->take)
                    ->orderBy('followers.created_at', 'desc')
                    ->get();

            foreach ($users as $key => $user_details) {

                $user_details->total_followers = Helper::total_followers($user_details->user_id, $blocked_user_ids);

                $user_details->total_followings = Helper::total_followings($user_details->user_id, $blocked_user_ids);

                $user_details->is_owner = $request->id == $user_details->user_id ? YES : NO;

                $user_details->show_follow = $user_details->show_unfollow = $user_details->show_block = $user_details->show_unblock = NO;

                if($user_details->is_owner == NO) {

                    $is_you_following = Helper::is_you_following($request->id, $user_details->user_id);

                    $user_details->show_follow = $is_you_following ? HIDE : SHOW;

                    $user_details->show_unfollow = $is_you_following ? SHOW : HIDE;

                    $is_you_blocked = Helper::is_you_blocked($request->id, $user_details->user_id);

                    $user_details->show_block = $is_you_blocked ? HIDE : SHOW;

                    $user_details->show_unblock = $is_you_blocked ? SHOW : HIDE;
                }

            }

            return $this->sendResponse($message = "", $code = "", $users);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        
        }

    }


    /**
     * @method other_profile_galleries()
     *
     * @uses To load galleries based on user id
     *
     * @created vithya R
     *
     * @updated 
     *
     * @param model image object - $request
     *
     * @return response of succes failure 
     */
    public function other_profile_galleries(Request $request) {

        try {

            $request->request->add(['user_id' => $request->user_id]);

            $response = StreamerGalleryRepo::streamer_galleries_list($request)->getData();

            if($response->success) {

                return $this->sendResponse($message = "", $code = "", $response->data);

            }

            throw new Exception($response->error_messages, $response->error_code);
                
        } catch (Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());

        }
    
    }

    /**
     * @method bell_notifications()
     *
     * @uses used to get the notifications for the selected user
     *
     * @created Vithya R
     *
     * @updated Vithya R 
     *
     * @param integer id, token
     *
     * @return json response 
     */
    public function bell_notifications(Request $request) {

        try {

            $notifications = UserNotification::where('user_notifications.user_id', $request->id)->leftJoin('users', 'users.id', '=', 'user_notifications.notifier_user_id')
                        ->CommonResponse()
                        ->skip($this->skip)
                        ->take($this->take)
                        ->orderBy('created_at', 'desc')
                        ->get();
                        
            UserNotification::where('user_notifications.user_id', $request->id)
                    ->where('user_notifications.status', UNREAD)->update(['status'=> READ]);

            return $this->sendResponse($message = "", $code = "", $notifications);

        } catch(Exception $e) {

            return $this->sendError($e->getMessage(), $e->getCode());
        }
    
    }

    /**
     * @method bell_notifications_count()
     *
     * @uses to get notification count of the user
     *
     * @created vithya R
     *
     * @updated vithya R
     *
     * @param
     *
     * @return json response 
     */
    public function bell_notifications_count(Request $request) {

        $count = UserNotification::where('user_notifications.user_id', $request->id)->where('user_notifications.status', UNREAD)->count();

        $data = ['count' => $count];

        return $this->sendResponse($message = "", $code = "", $data);

    }

}