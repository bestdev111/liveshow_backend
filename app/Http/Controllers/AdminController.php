<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Validator, Log, Hash, Auth, DB, Setting, Exception;

use App\Http\Requests;

use App\Helpers\Helper;

use App\Helpers\EnvEditorHelper;

use App\Repositories\VodRepository as VodRepo;

use App\Repositories\CommonRepository as CommonRepo;

use App\Repositories\StreamerGalleryRepository as StreamerGalleryRepo;

use App\Admin;

use App\User;

use App\Subscription;

use App\UserSubscription;

use App\LiveVideoPayment;

use App\LiveVideo;

use App\Page;

use App\Settings;

use App\RedeemRequest;

use App\Redeem;

use App\Coupon;

use App\Follower;

use App\Helpers\AppJwt;

use App\VodVideo;

use App\PayPerView;

use App\StreamerGallery;

use App\BlockList;

use App\LiveGroup, App\LiveGroupMember, App\CustomLiveVideo;

use App\Jobs\SendEmailJob;


class AdminController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $paginate_count;

    public function __construct()
    {
        $this->middleware('auth:admin');  

        $this->paginate_count = Setting::get('admin_take_count', 10);

    }

    public function dashboard() {

    	$admin = Admin::first();

        $admin->token = Helper::generate_token();
       
        $admin->token_expiry = Helper::generate_token_expiry();

        $admin->save();

        $user_count = User::count();

        $subscribers = UserSubscription::count();

        $live_videos = LiveVideo::count();

        $recent_videos = LiveVideo::take(12)->skip(0)->orderBy('created_at' , 'desc')->get();

        $get_registers = get_register_count();

        $recent_users = User::take(12)->skip(0)->orderBy('created_at' , 'desc')->get();

        $vod_video_payments = PayPerView::sum('amount') ?: 0;

        $subscription_payments = UserSubscription::sum('amount') ?: 0;

        $video_payments = LiveVideoPayment::sum('amount') ?: 0;

        $total_revenue = $video_payments + $subscription_payments + $vod_video_payments;
        

        $view = last_days(10);
       
        return view('admin.dashboard')
                    ->with('user_count' , $user_count)
                    ->with('subscribers' , $subscribers)
                    ->with('live_videos' , $live_videos)
                    ->with('total_revenue' , $total_revenue)
                    ->with('view' , $view)
                    ->with('recent_users' , $recent_users)
                    ->with('recent_videos' , $recent_videos)
                    ->with('get_registers' , $get_registers)
                    ->withPage('dashboard')
                    ->with('sub_page','');
    }

    public function profile() {

        $admin = Admin::first();

        return view('admin.profile')->with('admin' , $admin)->withPage('profile')->with('sub_page','');
    }

    public function profile_process(Request $request) {

        $demo_logins = explode(',', Setting::get('demo_users'));

        $validator = Validator::make($request->all() , [

            'mobile' => 'digits_between:6,13',
            'email' => 'email|unique:admins,email,'.$request->id.'|max:255',
            'email' => in_array($request->email, $demo_logins) ? 'email|unique:admins,email,'.$request->id.'|max:255' : 'email|unique:admins,email,'.$request->id.'|max:255',      
            'picture' => 'mimes:jpeg,jpg,bmp,png',
            ]);

        if($validator->fails()) {

            $errors = implode(',',$validator->messages()->all());

            return back()->with('flash_error', $errors);

        } else {
            
            $admin = Admin::find($request->id);
            
            $admin->name = $request->has('name') ? $request->name : $admin->name;

            $admin->email = $request->has('email') ? $request->email : $admin->email;

            $admin->mobile = $request->has('mobile') ? $request->mobile : $admin->mobile;

            $admin->gender = $request->has('gender') ? $request->gender : $admin->gender;

            $admin->address = $request->has('address') ? $request->address : $admin->address;

            if($request->hasFile('picture')) {

                Helper::storage_delete_file($admin->picture,ADMIN_FILE_PATH);

                $admin->picture = Helper::storage_upload_file($request->file('picture'), ADMIN_FILE_PATH);
            }
                
            $admin->remember_token = Helper::generate_token();
            $admin->save();

            return back()->with('flash_success', tr('profile_updated'));
            
        }
    
    }

    public function change_password(Request $request) {
        
        $validator = Validator::make($request->all(), [              
                'password' => 'required|confirmed|min:6',
                'old_password' => 'required',
                'id' => 'required|exists:admins,id'
            ]);

        if($validator->fails()) {

            $error_messages = implode(',',$validator->messages()->all());

            return back()->with('flash_error', $error_messages);

        } else {

            $admin = Admin::find($request->id);

            if(Hash::check($request->old_password,$admin->password)) {

                $admin->password = Hash::make($request->password);

                $admin->save();

                Auth::guard('admin')->logout();

                return redirect()->route('admin.login')->with('flash_success', tr('password_change_success'));
                
            } else {
                return back()->with('flash_error', tr('password_mismatch'));
            }
        }
    
    }

    /**
     * @method users_index()
     * 
     * @uses to list the users
     *
     * @created Maheswari S
     *
     * @updated Maheswari S
     *
     * @param - 
     *
     * @return users management view page
     */

    public function users_index(Request $request) {

        $total_users = User::orderBy('id','desc')->count();

        $total_approved = User::orderBy('id','desc')->Where('status', APPROVED)->count();

        $total_declined = User::orderBy('id','desc')->Where('status', DECLINED)->count();    

        $base_query = User::orderBy('created_at','desc');
        
        $user_type = "";

        if($request->search_key) {

            $base_query->where(function ($query) use ($request) {
                $query->where('name', "like", "%" . $request->search_key . "%");
                $query->orWhere('email', "like", "%" . $request->search_key . "%");
                $query->orWhere('mobile', "like", "%" . $request->search_key . "%");
            });
        }

        if($request->status!='' && $request->status!= BOTH_USERS) {

            $base_query->where('is_content_creator',$request->status);
                    
        }


        if($request->user_status!='') {

            switch ($request->user_status) {

                case SORT_BY_APPROVED:
                    $base_query = $base_query->where('users.status', USER_APPROVED);
                    break;

                case SORT_BY_DECLINED:
                    $base_query = $base_query->where('users.status', USER_DECLINED);
                    break;

                case SORT_BY_VERIFIED:
                    $base_query = $base_query->where('users.is_verified',APPROVED);
                    break;
                
                default:
                    $base_query = $base_query->where('users.is_verified',DECLINED);
                    break;
            }
        }
        
        if($request->user_type) {

            $subscribers = UserSubscription::where('status', APPROVED)->pluck('user_id');
            
            $user_type = $request->user_type;   

            $base_query = $base_query->whereIn('id', $subscribers);
        }

        if($request->has('sort')) {

            $base_query->when($request->sort == DECLINED, function ($query) use ($request) { 
                return $query->where('status', DECLINED);
            })
            ->when($request->sort == IS_CONTENT_CREATOR, function ($query) use ($request) { 
                return $query->where('is_content_creator', YES);
            });
        }


        $users = $base_query->paginate($this->paginate_count);
        

        $users->total_approved = $total_approved;
        
        $users->total_declined = $total_declined;

        $users->total_users = $total_users;

        $sub_page = $request->sub_page ? "view-users-".$request->sub_page:'view-users';

        return view('admin.users.index')
                    ->withPage('users')
                    ->with('sub_page',$sub_page)
                    ->with('data', $users)
                    ->with('search_key', $request->search_key)
                    ->with('sort', $request->sort??'')
                    ->with('page_title',$request->sub_page??'')
                    ->with('user_type', $user_type);
    }

    /**
    * @method user_block_list()
    *
    * @uses User blocked list, blocked you-> blocked for you, blocked other-> your blocked user list
    *
    * @created Shobana
    *
    * @updated Maheswaari 
    * 
    * @param Request user id
    *
    * @return blocked user list
    */

    public function user_block_list(Request $request) {

        /*if($request->blocked_by_me){

            $block_user = User::find($request->blocked_by_me);

            $users = $block_user->blockedUsersByme()->select('block_user_id as id')->get();

        } else{

            $block_user = User::find($request->blocked_by_others);

            $users = $block_user->blockedMeByOthers()->select('user_id as id')->get();
           
        }*/

        $block_user = User::find($request->id);

        if ($block_user) {

            $users = $block_user->blockedUsersByme()->select('block_user_id as id')->get();

        }

        $ids = [];
        
        foreach ($users as $key => $user) {

            $ids[] = $user->id;
        }

        $data = User::whereIn('id' , $ids)->orderBy('created_at' , 'desc')->get();

        return view('admin.users.blocklist')->withPage('users')
                        ->with('data' , $data)
                        ->with('block_user' , $block_user)
                        ->with('sub_page','view-users');
    }

    public function user_create() {

        return view('admin.users.create')->with('page' , 'users')
                    ->with('sub_page','add-user');
    }

    public function user_edit(Request $request) {

        $data = User::find($request->id);

        if($data){

            return view('admin.users.edit')
                    ->withData($data)
                    ->with('sub_page','view-users')
                    ->with('page' , 'users');
        } else{

            return back()->with('flash_error',tr('user_not_found'));

        }
    }

    public function user_save(Request $request) {

        try {

            DB::begintransaction();

            $demo_logins = explode(',', Setting::get('demo_users'));

            $validator = Validator::make($request->all() , [
                'name' => 'required|regex:/^[a-z\d\-.\s]+$/i| min:2|max:100',
                'email' => in_array($request->email, $demo_logins) ?
                    ($request->user_id ? 'required|email|unique:users,email,'.$request->user_id.',id' : 'required|email|unique:users,email,NULL,id') : ($request->user_id ? 'required|email|unique:users,email,'.$request->user_id.',id' : 'required|email|unique:users,email,NULL,id'),

                'password' => $request->user_id ? "" : 'required|min:6|confirmed',
                'mobile' => 'digits_between:6,13',
                'picture' => 'mimes:jpeg,jpg,bmp,png',
                'cover' => 'mimes:jpeg,jpg,bmp,png',
            ]);
       
            if($validator->fails()) {

                $errors = implode(',',$validator->messages()->all());

                return back()->with('flash_error', $errors)->withInput();

            }

            $user = $request->user_id ? User::find($request->user_id) : new User;

            $is_new_user = YES;

            if($user->id) {

                $message = tr('admin_update_user'); 

                $is_new_user = NO;

            } else {
                
                $user->is_verified = DEFAULT_TRUE;

                $message = tr('admin_add_user_success');

                $user->login_by = 'manual';

                $user->device_type = 'web';

                $name = $request->name ? str_replace(' ', '-', $request->name) : "";

                $user->unique_id = uniqid($name);
                
                $user->password = \Hash::make($request->password);

                $user->picture = Helper::web_url().'/images/default-profile.jpg';

                $user->chat_picture = Helper::web_url().'/images/default-profile.jpg';

                $user->cover = asset('images/cover.jpg');
            }  

            $user->name = $request->name ?: $user->name;

            $user->email = $request->email ?: $user->email;

            $user->description = $request->description ?: '';

            $user->mobile = $request->mobile ?: '';

            $user->token_expiry = Helper::generate_token_expiry();

            $user->status = DEFAULT_TRUE;

            $user->paypal_email = $request->paypal_email ?: ($user->paypal_email ?: '');

            $user->is_content_creator = $request->is_content_creator ? $request->is_content_creator : VIEWER_STATUS;
           

             // Upload picture
            if ($request->hasFile('picture')) {

                if ($request->user_id) {

                    Helper::storage_delete_file($user->picture, USER_PATH); // Delete the old pic
                }

                $user->picture = Helper::storage_upload_file($request->file('picture'), USER_PATH);

            }

            // Upload picture
            if ($request->hasFile('cover')) {
                
                if ($request->user_id) {

                    Helper::storage_delete_file($user->cover, USER_PATH); // Delete the old pic
                }

                $user->cover = Helper::storage_upload_file($request->file('cover'), USER_PATH);
            }


            if($is_new_user) {
                
                $email_data['name'] = $user->name;

                $email_data['subject'] = tr('user_welcome_title').' '.Setting::get('site_name');

                $email_data['page'] = "emails.admin_user_welcome";

                $email_data['data'] = $user;

                $email_data['email'] = $user->email;

                $email_data['password'] = $request->password;

                dispatch(new SendEmailJob($email_data));

                register_mobile('web');
            }
            
            if($user->save()) {

                if(!$is_new_user) {

                    $user->token = AppJwt::create(['id' => $user->id, 'email' => $user->email, 'role' => "model"]);

                    $user->save();

                }

                DB::commit(); 

                return redirect(route('admin.users.view', ['user_id' => $user->id]))->with('flash_success', $message);
            }

            throw new Exception(tr('user_not_found'));
            
        } catch(Exception $e){ 

            DB::rollback();

            return redirect()->back()->withInput()->with('flash_error', $e->getMessage());

        }   
    }


    public function user_delete(Request $request) {

        if($user = User::where('id',$request->id)->first()->delete()) {

            updated_register_count('web');

            return back()->with('flash_success',tr('admin_not_user_del'));
        }
        
        return back()->with('flash_error',tr('user_not_found'));        
    }

    public function user_view(Request $request) {


        $user = User::where('id',$request->user_id)
                    ->withCount('getFollowers')
                    ->withCount('getFollowing')
                    ->withCount('getLiveVideos')
                    ->withCount('getBlockUsers')
                    ->withCount('getPaymentvideos')
                    ->withCount('getFreevideos')
                    ->withCount('getVodvideos')
                    ->first();
        
        if(!$user) {
        
           return back()->with('flash_error',tr('user_not_found'));
        }

        $followers = Follower::select('user_id as id',
                        'users.name as name', 
                        'users.email as email', 
                        'users.picture',
                        'users.description' ,
                        'followers.follower as follower_id' ,
                        'followers.created_at as created_at'
                       )
                ->leftJoin('users' , 'users.id' ,'=' , 'followers.follower')
                ->where('user_id', $request->user_id)
                ->orderBy('created_at', 'desc')
                ->paginate($this->paginate_count);

        $followings = Follower::select('followers.follower as id',
                        'users.name as name', 
                        'users.email as email', 
                        'users.picture',
                        'users.description',
                        'users.id as follower_id' ,
                        'followers.created_at as created_at' 
                       )
                ->leftJoin('users' , 'users.id' ,'=' , 'followers.user_id')
                ->where('follower', $request->user_id)
                ->orderBy('created_at', 'desc')
                ->paginate($this->paginate_count);

        $live_videos = LiveVideo::where('user_id',$request->user_id)->orderBy('created_at' , 'desc')->paginate($this->paginate_count);
        
        $blockers = BlockList::select(
                        'users.name as name', 
                        'users.email as email', 
                        'users.picture',
                        'users.description',
                        'block_lists.block_user_id as block_user_id' ,
                        'block_lists.created_at as created_at' 
                       )
                ->leftJoin('users' , 'users.id' ,'=' , 'block_lists.user_id')
                ->where('user_id', $request->user_id)
                ->orderBy('created_at', 'desc')
                ->paginate($this->paginate_count);

        $video_list = VodVideo::vodResponse()
                ->orderBy('created_at','desc')
                ->where('user_id' , $request->user_id)
                ->paginate($this->paginate_count);
        
        return view('admin.users.view')
                    ->with('data' , $user)
                    ->with('followers', $followers)
                    ->with('followings', $followings)
                    ->with('videos', $live_videos)
                    ->with('blockers', $blockers)
                    ->with('vod_videos', $video_list)
                    ->withPage('users')
                    ->with('sub_page','view-users');
    }


    public function user_approve(Request $request) {

        $user = User::find($request->id);

        $user->status = $user->status ? DEFAULT_FALSE : DEFAULT_TRUE;

        $user->save();

        if($user->status ==1) {

            $message = tr('user_approve_success');
            
        } else {

            $message = tr('user_decline_success');
        }

        return back()->with('flash_success', $message);
    }


    /** 
     * User status change
     * 
     *
     */

    public function user_verify_status($id) {

        if($data = User::find($id)) {

            $data->is_verified  = $data->is_verified ? 0 : 1;

            $data->save();

            return back()->with('flash_success' , $data->status ? tr('user_verify_success') : tr('user_unverify_success'));

        } else {

            return back()->with('flash_error',tr('user_not_found'));
            
        }
    }

    public function subscriptions(Request $request) {

        $base_query = Subscription::orderBy('created_at','desc')->whereNotIn('status', [DELETE_STATUS]);

        if($request->search_key){
        
        $base_query = $base_query->where('subscriptions.title','LIKE','%'.$request->search_key.'%');

        }

        if($request->status!=''){

            $base_query->where('subscriptions.status',$request->status);
        }

        $data = $base_query->paginate($this->paginate_count);


        foreach ($data as $key => $value) {

            $value->total_subscriptions = $value->userSubscription()->groupBy('subscription_id')->count();
              
        } 


        return view('admin.subscriptions.index')
                    ->withPage('subscriptions')
                    ->with('sub_page','view-subscriptions')
                    ->with('data' , $data);        

    }


    public function user_subscriptions($id) {


        $payments = $free_subscription = []; 

        $user = "";

        if($id) {

            $user = User::find($id);

            $payments = UserSubscription::orderBy('created_at' , 'desc')
                        ->where('user_id' , $id)->get();

            $free_subscription = $payments->where('amount','=',0.00)->pluck('subscription_id') ?? [];

        }     
        
        $data = Subscription::orderBy('created_at','desc')->where('status', DEFAULT_TRUE)
                ->when($free_subscription, function ($q) use ($free_subscription) {
                    if($free_subscription->count() >= 1)
                    {
                        return $q->whereNotIn('id', $free_subscription);
                    }
                })->get();


        return view('admin.subscriptions.user_plans')
                        ->withPage('users')
                        ->with('sub_page','view-users')
                        ->with('subscriptions' , $data)
                        ->with('payments', $payments)
                        ->with('id', $id)
                        ->with('user',$user)
                        ->with('payments',$payments);
    }

    public function user_subscription_save(Request $request) {

        $previous_payment = UserSubscription::where('user_id', $request->u_id)->orderBy('created_at', 'desc')->first();

        $user_subscription = new UserSubscription();

        $user_subscription->subscription_id = $request->s_id;

        $user_subscription->user_id = $request->u_id;

        $user_subscription->amount = ($user_subscription->subscription) ? $user_subscription->subscription->amount  : 0;

        $user_subscription->subscription_amount = $user_subscription->amount ?? 0.00;

        $user_subscription->payment_id = ($user_subscription->amount > 0) ? uniqid(str_replace(' ', '-', 'PAY')) : 'Free Plan'; 

        $user_subscription->status = DEFAULT_TRUE;

        if ($previous_payment) {
            $user_subscription->expiry_date = date('Y-m-d H:i:s', strtotime("+{$user_subscription->subscription->plan} months", strtotime($previous_payment->expiry_date)));
        } else {
            $user_subscription->expiry_date = date('Y-m-d H:i:s',strtotime("+{$user_subscription->subscription->plan} months"));
        }


        if ($user_subscription->save())  {

            $user = User::find($user_subscription->user_id);

            if($user){

                $user->amount_paid += $user_subscription->amount;

                $user->expiry_date = $user_subscription->expiry_date;

                $user->no_of_days = 0;

                $now = time(); // or your date as well

                $end_date = strtotime($user->expiry_date);

                $datediff =  $end_date - $now;

                $user->no_of_days = ($user->expiry_date) ? floor($datediff / (60 * 60 * 24)) + 1 : 0;

                $user->user_type = SUBSCRIBED_USER;

                if ($user_subscription->amount == 0) {

                    $user->one_time_subscription = DEFAULT_TRUE;

                }

                if ($user->save()) {

                    return back()->with('flash_success', tr('subscription_applied_success'));

                }

            } else{

                return back()->with('flash_errors',tr('user_not_found'));
            }


        }

         return back()->with('flash_errors', tr('went_wrong'));

    }

    public function subscription_create() {

        return view('admin.subscriptions.create')
                ->with('page', 'subscriptions')
                ->with('sub_page','add-subscriptions');
    }

    public function subscription_edit($id) {

        try {

            $data = Subscription::find($id);

            if($data) {

                return view('admin.subscriptions.edit')
                    ->with('page', 'subscriptions ')
                    ->with('sub_page', 'view-subscription')
                    ->withData($data);
            }

            throw new Exception(tr('subscription_not_found'), 101);
            
        } catch(Exception $e) {

            return redirect()->route('admin.subscriptions.index')->with('flash_error', $e->getMessage());
        }

    }

    public function subscription_save(Request $request) {
            
        $validator = Validator::make($request->all(),[

                'id'=>$request->id ? 'required|exists:subscriptions,id' : "",
                'title' => $request->id ? 'unique:subscriptions,title,'.$request->id : 'unique:subscriptions,title|required|max:255',
                'plan' => 'required',
                'amount' => 'required',
        ]);

        if($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            return back()->with('flash_error', $error_messages);
        } 

        if($request->popular_status) {
            
            Subscription::where('popular_status', 1)->update(['popular_status' => 0]);
        }

        if($request->id != '') {

            $model = Subscription::find($request->id);
            $model->update($request->all());

        } else {

            $model = Subscription::create($request->all());
            $model->status = 1;
            $model->popular_status = $request->popular_status ? 1 : 0;
            $model->unique_id = $model->title;
            $model->save();
        }
        
        $message = $request->id ? tr('subscription_update_success') : tr('subscription_create_success');

        if($model) {

            return redirect()->route('admin.subscriptions.view', $model->id)
                        ->with('flash_success', $message);

        } else {
            
            return back()->with('flash_error',tr('subscription_not_found'));
        }        
    }

    /** 
     * 
     * Subscription View
     *
     */

    public function subscription_view($id) {
        
        $subscription_details = Subscription::find($id);

        if(!$subscription_details) {

            return back()->with('flash_error',tr('subscription_not_found'));
        }            

        $subscription_details->subscribers_count = $subscription_details->userSubscription->count();

        return view('admin.subscriptions.view')
                ->withPage('subscriptions')
                ->with('sub_page','view-subscriptions')
                ->with('data', $subscription_details);

    }


    public function subscription_delete(Request $request) {

        if($data = Subscription::where('id',$request->id)->first()) {

            $data->status = DELETE_STATUS;

            $data->save();

            return back()->with('flash_success',tr('subscription_delete_success'));

        } else {
            return back()->with('flash_error',tr('subscription_not_found'));
        }
        
    }

    /** 
     * Subscription status change
     * 
     *
     */

    public function subscription_status($id) {

        try {

            DB::beginTransaction();

            if($subscription_details = Subscription::find($id)) {

                $subscription_details->status  = $subscription_details->status == APPROVED ? DECLINED : APPROVED;

                $subscription_details->save();

                DB::commit();

                $message = $subscription_details->status == APPROVED ? tr('subscription_approve_success') : tr('subscription_decline_success');

                return back()->with('flash_success' , $message);

            } else {

                throw new Exception(tr('subscription_not_found'), 101);
                
            }

        } catch (Exception $e) {

            DB::rollback();

            return back()->with('flash_error', $e->getMessage());

        }
    }

    /** 
     * Subscription Popular status change
     * 
     *
     */

    public function subscription_popular_status($id) {

        if($data = Subscription::find($id)) {

            Subscription::where('popular_status' , 1)->update(['popular_status' => 0]);

            $data->popular_status  = $data->popular_status ? 0 : 1;

            $data->save();

            return back()->with('flash_success' , $data->popular_status ? tr('subscription_popular_success') : tr('subscription_remove_popular') );
                
        } else {
            return back()->with('flash_error',tr('subscription_not_found'));
        }
    }

    /** 
     * View list of users based on the selected Subscription
     *
     */

    public function subscription_users($id) {

        $total_users = User::orderBy('id','desc')->count();

        $total_approved = User::orderBy('id','desc')->Where('status', APPROVED)->count();

        $total_declined = User::orderBy('id','desc')->Where('status', DECLINED)->count();

        $subscription = Subscription::find($id);
        
        if(!$subscription){
            
            return back()->with('flash_error',tr('no_subscription_found'));
        }
       
        $users = UserSubscription::where('subscription_id', $id)->groupBy('user_id')->pluck('user_id')->toArray();
        
        $data = User::whereIn('id', $users)
                ->orderBy('created_at','desc')
                ->paginate($this->paginate_count);


        $data->total_approved = $total_approved;
        
        $data->total_declined = $total_declined;

        $data->total_users = $total_users;

        return view('admin.users.index')
                    ->withPage('users')
                    ->with('data', $data)
                    ->with('sub_page', 'view-users')                    
                    ->with('search_key', '')
                    ->with('sort', '')
                    ->with('subscription', $subscription);
    }

    public function subscription_payments(Request $request) {


        $base_query = UserSubscription::orderBy('created_at' , 'desc')
                            ->when($request->subscription_id, function ($query) use ($request) { 
                                    return $query->where('subscription_id', $request->subscription_id);
                                });

        if($request->paid_status!=''){

            $base_query->where('user_subscriptions.status',$request->paid_status);
        }
                        

        if($request->search_key) {

            $base_query->where(function ($query) use ($request) {
                $query->where('subscriptions.title','LIKE','%'.$request->search_key.'%');
                $query->orWhere('users.name','LIKE','%'.$request->search_key.'%');
                $query->orWhere('user_subscriptions.payment_id','LIKE','%'.$request->search_key.'%');
            });
        }

        if($request->payment_mode!=''){

            $base_query->where('user_subscriptions.payment_mode',$request->payment_mode);
        }
        
        $user_subscriptions = $base_query->commonResponse()->paginate($this->paginate_count);
                            

        return view('admin.payments.user-payments')
                    ->with('data' , $user_subscriptions)
                    ->with('page','payments')
                    ->with('sub_page','subscription_payments'); 
    }


    public function video_payments(Request $request) {

        $base_query = LiveVideoPayment::whereHas('user')->orderBy('created_at' , 'desc');

       
        if($request->paid_status!=''){

            $base_query->where('live_video_payments.status',$request->paid_status);
        }

        if($request->search_key) {

            $base_query->where(function ($query) use ($request) {
                $query->where('subscriptions.title','LIKE','%'.$request->search_key.'%');
                $query->orWhere('live_videos.title','LIKE','%'.$request->search_key.'%');
                $query->orWhere('live_video_payments.payment_id','LIKE','%'.$request->search_key.'%');
            });
        }

       

        if($request->payment_mode!=''){

            $base_query->where('live_video_payments.payment_mode',$request->payment_mode);
        }
       
        $payments = $base_query->commonResponse()->paginate($this->paginate_count);

        return view('admin.payments.video-payments')
                ->with('data' , $payments)
                ->with('page','payments')
                ->with('sub_page','video_payments'); 
    }
    

    /**
     * @method videos_index()
     *
     * @uses To list out LiveVideos
     *
     * @created Anjana H
     *
     * @updated Anjana H
     *
     * @param 
     * 
     * @return return view page
     *
     */
    public function videos_index(Request $request) {
        
        $base_query = LiveVideo::where('live_videos.status', DEFAULT_FALSE)
                        ->where('is_streaming', DEFAULT_TRUE)
                        ->orderBy('created_at', 'desc');

        if($request->search_key) {

            $base_query->where(function ($query) use ($request) {
                $query->where('title','LIKE','%'.$request->search_key.'%');
                $query->orWhere('users.name','LIKE','%'.$request->search_key.'%');
            });
        }

        if($request->payment_status!='') {

            $base_query->where('payment_status',$request->payment_status);
        }

        if($request->video_type) {

            $base_query->where('type',$request->video_type);
        }
        
        $live_videos = $base_query->commonResponse()->paginate($this->paginate_count);
        
        $live_videos->title = tr('live_streaming_videos');

        return view('admin.videos.index')
                    ->with('data', $live_videos)
                    ->with('page', 'live_videos')
                    ->with('is_streaming', DEFAULT_TRUE)
                    ->with('search_key', $request->search_key)
                    ->with('sub_page', 'view-live_videos_streaming'); 
    }

    public function videos_list(Request $request) {
        
        $base_query= LiveVideo::orderBy('created_at', 'desc');
        
        if($request->user_id) {

            $base_query = $base_query->where('user_id', $request->user_id);
        } 
      

        if($request->search_key) {

            $base_query->where(function ($query) use ($request) {
                $query->where('title','LIKE','%'.$request->search_key.'%');
                $query->orWhere('users.name','LIKE','%'.$request->search_key.'%');
            });
        }
        

        if($request->payment_status!='') {

            $base_query->where('payment_status',$request->payment_status);
        }

        if($request->video_type) {

            $base_query->where('type',$request->video_type);
        }
        
        if($request->broadcast_status!=''){

            if($request->broadcast_status < DEFAULT_FALSE){

                $base_query->where('is_streaming',DEFAULT_FALSE);

            }
            else{
                $base_query->where('is_streaming',DEFAULT_TRUE)->where('live_videos.status',$request->broadcast_status);

            }

        }




        $live_videos = $base_query->commonResponse()->paginate($this->paginate_count);

        $live_videos->title = tr('view_live_videos_history');

        return view('admin.videos.index')
                ->with('data', $live_videos)
                ->with('page', 'live_videos')
                ->with('is_streaming', DEFAULT_FALSE)
                ->with('search_key', $request->search_key)
                ->with('sub_page', 'view-live_videos'); 
    }


    public function videos_view(Request $request) {

        $model = LiveVideo::find($request->video_id);

        if($model){

            $video_url = "";

            $ios_video_url = "";

            if ($model->unique_id == 'sample') {

                $video_url = $model->video_url;

            } else {

                if ($model->video_url) {            

                    if($model->browser_name == DEVICE_IOS){

                       $video_url = CommonRepo::rtmpUrl($model);

                    }

                    //$video_url = CommonRepo::iosUrl($model);

                    $ios_video_url = CommonRepo::iosUrl($model);

                } else {

                    $video_url = "";

                }

            }


            $model->video_url = $video_url;
            
            return view('admin.videos.view')
                    ->with('data' , $model)
                    ->with('page','live_videos')
                    ->with('sub_page','view-live_videos')
                    ->with('ios_video_url', $ios_video_url); 
        } else{

            return back()->with('flash_error',tr('vidoes_not_found'));
        }
    }

    public function help() {

        return view('admin.static.help')->withPage('help')->with('sub_page' , "");
    }

    public function pages_index() {

        $pages = Page::orderBy('created_at' , 'desc')
                    ->paginate($this->paginate_count);

        return view('admin.pages.index')
                ->with('page',"pages")
                ->with('sub_page','view-pages')
                ->with('data',$pages);
    }

    /**
     * @method page_create()
     *
     * @uses to create page
     *
     */

    public function pages_create() {

        return view('admin.pages.create')
                ->with('page', 'pages')
                ->with('sub_page', 'add-pages');
    }

    public function pages_edit($id) {

        $pages = Page::find($id);

        if(!$pages) {
            
            return back()->with('flash_error', tr('page_details_not_found'));
        }

        return view('admin.pages.edit')
            ->with('page', 'pages')
            ->with('sub_page', 'view-pages')
            ->with('data', $pages);        
    }

    public function pages_view(Request $request) {

        $pages = Page::find($request->id);

        if(!$pages) {
            
            return back()->with('flash_error', tr('page_details_not_found'));
        }

        return view('admin.pages.view')
            ->with('page', 'pages')
            ->with('sub_page', 'view-pages')
            ->with('data',$pages);
    }

    public function pages_save(Request $request) {

        if($request->has('id')) {
            $validator = Validator::make($request->all() , array(
                'title' => '',
                'heading' => 'required',
                'description' => 'required'
            ));
        } else {
            $validator = Validator::make($request->all() , array(
                'type' => 'required',
                'title' => 'required|max:255|unique:pages,deleted_at,NULL',
                'heading' => 'required',
                'description' => 'required',
            ));
        }

        if($validator->fails()) {
            $error = implode(',',$validator->messages()->all());
            return back()->with('flash_error',$error);
        }

        if($request->has('id')) {
            
            $pages = Page::find($request->id);

            $messages = tr('page_update_success');

        } else {
            
            if(Page::count() < Setting::get('no_of_static_pages')) {

                if($request->type != 'others') {
                    
                    $check_page_type = Page::where('type',$request->type)->first();
                    
                    if($check_page_type){
                    
                        $messages = tr('page_exists').$request->type; 

                        return back()->with('flash_error', $messages);
                    }
                }                    
                
                $pages = new Page;

                $pages->status = APPROVED;

                $messages = tr('page_create_success');

                $check_page = Page::where('title',$request->title)->first();
                
                if($check_page) {

                    return back()->with('flash_error', tr('page_already_alert'));
                }

            } else {

                return back()->with('flash_error', tr('you_cannot_create_more'));
            }
            
        }

        if($pages) {

            $pages->type = $request->type ? $request->type : $pages->type;
            
            $pages->title = $request->title ? $request->title : $pages->title;
            
            $pages->heading = $request->heading ? $request->heading : $pages->heading;
            
            $pages->description = $request->description ? $request->description : $pages->description;
            
            $pages->save();

            // Dont change the below code. If any issue, get approval from vithya and change

            if(!in_array($request->type, ['about', 'privacy', 'terms', 'contact', 'help', 'faq'])) {

                $unique_id = routefreestring($request->heading ?? rand());

                $unique_id = in_array($unique_id, ['about', 'privacy', 'terms', 'contact', 'help', 'faq']) ? $unique_id : $unique_id;

            }

            $pages->unique_id = $unique_id ?? rand();

        }

        if($pages) {
            
            Helper::settings_generate_json();

            return redirect()->route('admin.pages.view',['id' =>$pages->id])->with('flash_success',tr('page_create_success'));
        } else {
            return back()->with('flash_error',tr('something_error'));
        }
        
    }

    public function pages_delete($id) {

        $page = Page::where('id',$id)->delete();

        if($page) {
            return back()->with('flash_success',tr('page_delete_success'));
        } else {
            return back()->with('flash_error',tr('something_error'));
        }
    }

    public function settings() {

        $settings = array();

        $result = EnvEditorHelper::getEnvValues();

        return view('admin.settings.settings')->with('settings' , $settings)->with('result', $result)->withPage('settings')->with('sub_page',''); 
    }

    public function settings_process(Request $request) {
       
        $settings = Settings::all();
        
        $check_streaming_url = "";
        

        if($settings) {

            foreach ($settings as $setting) {

                $key = $setting->key;
               
                if($setting->key == 'site_icon') {

                    if($request->hasFile('site_icon')) {
                        
                        if($setting->value) {
                            
                            Helper::storage_delete_file($setting->value, FILE_PATH_SITE);
                        }

                        $setting->value = Helper::storage_upload_file($request->file('site_icon'), FILE_PATH_SITE);
                    
                    }
                    
                } else if($setting->key == 'site_logo') {

                    if($request->hasFile('site_logo')) {

                        if($setting->value) {

                            Helper::storage_delete_file($setting->value, FILE_PATH_SITE);
                        }

                        $setting->value = Helper::storage_upload_file($request->file('site_logo'), FILE_PATH_SITE);
                    }

                } else if($setting->key == 'home_bg_image') {

                     $validator = Validator::make($request->all() , array(
                                'home_bg_image' => 'mimes:jpeg,jpg,bmp,png,webm',
                            ));                        

                    if($validator->fails()) {

                        $error = implode(',',$validator->messages()->all());

                        return back()->with('flash_error',$error);
                    } 

                    if($request->hasFile('home_bg_image')) {

                        if($setting->value) {

                            Helper::storage_delete_file($setting->value, FILE_PATH_SITE);
                        }

                        $setting->value = Helper::storage_upload_file($request->file('home_bg_image'), FILE_PATH_SITE);
                    }


                } else if($setting->key == 'common_bg_image') {

                    if($request->hasFile('common_bg_image')) {

                        if($setting->value) {

                            Helper::storage_delete_file($setting->value, FILE_PATH_SITE);
                        }

                        $setting->value = Helper::storage_upload_file($request->file('common_bg_image'), FILE_PATH_SITE);
                    }


                } else if($setting->key == 'admin_commission') {

                    if($request->has('admin_commission')) {

                        $setting->value = $request->admin_commission;

                        update_user_commission($request->admin_commission);
                    }


                }  else if($setting->key == 'admin_vod_commission') {

                    if($request->has('admin_vod_commission')) {

                        $setting->value = $request->admin_vod_commission;

                        update_user_vod_commission($request->admin_vod_commission);
                    }


                }  else if($setting->key == 'email_verify_control') {

                    if($request->email_verify_control != $setting->value) {

                    } else  {

                        if ($request->email_verify_control == 1) {

                            if(envfile('MAIL_USERNAME') &&  envfile('MAIL_PASSWORD')) {

                                $setting->value = $request->email_verify_control ? $request->email_verify_control : $setting->value;

                            } else {

                                return back()->with('flash_error', tr('configure_smtp'));
                            }

                        } else {

                            $setting->value = $request->email_verify_control ? $request->email_verify_control : 0;
                        }

                    }
                    
                }  else if($setting->key == 'email_notification') {

                    if($request->email_notification != $setting->value) {

                    } else  {

                        if ($request->email_notification == 1) {

                            if(envfile('MAIL_USERNAME') &&  envfile('MAIL_PASSWORD')) {

                                $setting->value = $request->email_notification ? $request->email_notification : $setting->value;

                            } else {

                                return back()->with('flash_error', tr('configure_smtp'));
                            }

                        } else {

                            $setting->value = $request->email_notification ? $request->email_notification : 0;
                        }

                    }

                }  else if($setting->key == 'user_fcm_sender_id') {
                    
                    $setting->value = $request->user_fcm_sender_id ? $request->user_fcm_sender_id : $setting->value;

                    \Enveditor::set("FCM_SENDER_ID", $request->user_fcm_sender_id);

                } else if($setting->key == 'user_fcm_server_key') {
                    
                    $setting->value = $request->user_fcm_server_key ? $request->user_fcm_server_key : $setting->value;

                    \Enveditor::set("FCM_SERVER_KEY", $request->user_fcm_server_key);

                } else if($setting->key == 'site_name') {

                    if($request->has('site_name')) {

                        $site_name  = preg_replace("/[^A-Za-z0-9]/", "", $request->site_name);

                        \Enveditor::set("SITENAME", $site_name);

                        $setting->value = $request->site_name;

                    }

                } else {

                    if (isset($_REQUEST[$key])) {

                        $setting->value = $request->$key;

                    }

                }

                $setting->save();

            
            }

        }

        Helper::settings_generate_json();
        
        $message = tr('settings_success')." ".$check_streaming_url;
        
        return back()->with('setting', $settings)->with('flash_success', $message);    
    
    }

    /**
     * @method save_common_settings
     * Save the values in env file
     *
     * @param object $request Post Attribute values
     * 
     * @return settings values
     */
    
    public function save_common_settings(Request $request) {
        
        $admin_id = \Auth::guard('admin')->user()->id;
  
        if($request->has('stripe_publishable_key')) {

            Settings::where('key' , 'stripe_publishable_key')->update(['value' => $request->stripe_publishable_key]);
        }

        if($request->has('stripe_secret_key')) {

            Settings::where('key' , 'stripe_secret_key')->update(['value' => $request->stripe_secret_key]);

        }

        if($request->has('admin_commission')) {

            if(Settings::where('key' , 'admin_commission')->update(['value' => $request->admin_commission])) {
                update_user_commission($request->admin_commission);

            }
        }


        if($request->has('admin_vod_commission')){

            if(Settings::where('key','admin_vod_commission')->update(['value'=>$request->admin_vod_commission])){

                update_user_vod_commission($request->admin_vod_commission);
            }
        }
        
        \Session::put('flash_success', tr('common_settings_success'));  

        foreach ($request->all() as $key => $data) {

            if (isset($_REQUEST[$key])) {

                \Enveditor::set($key,$data);

            }
        }

        $result = EnvEditorHelper::getEnvValues();

        Helper::settings_generate_json();

        return redirect(route('clear-cache'))->with('result' , $result)->with('flash_success' , tr('common_settings_success'));
    }

    /**
     *
     *
     */

    public function user_payout(Request $request) {

        $validator = Validator::make($request->all() , [
            'user_id' => 'required|exists:users,id',
            'amount' => 'required', 
            ]);

        if($validator->fails()) {

            return back()->with('flash_error' , $validator->messages()->all())->withInput();

        } else {

            $model = User::find($request->user_id);

            if($model) {

                if($request->amount <= $model->remaining_amount) {

                    $model->paid_amount = $model->paid_amount + $request->amount;

                    $model->remaining_amount =$model->remaining_amount - $request->amount;

                    $model->save();
    
                    return back()->with('flash_success' , tr('action_success'));

                } else {
                    return back()->with('flash_error' , tr('user_payout_greater_error'));
                }

            } else {

                return back()->with('flash_error' , tr('something_error'));

            }
        }

    }

    public function control() {

        return view('admin.settings.control')->withPage('control')->with('sub_page' , 'control');

    }

    public function revenue_system() {

        $total_sub_revenue = UserSubscription::sum('amount');

        $vod_video_payments = PayPerView::sum('amount') ?: 0;

        $video_payments = LiveVideoPayment::sum('amount') ?: 0;

        $total_revenue = $video_payments + $total_sub_revenue + $vod_video_payments;

        // Video Payments

        $live_video_amount = LiveVideoPayment::sum('amount');

        $video_amount = $live_video_amount ? $live_video_amount : 0;

        $live_user_amount = LiveVideoPayment::sum('user_amount');

        $user_amount = $live_user_amount ? $live_user_amount : 0;

        $live_admin_amount = LiveVideoPayment::sum('admin_amount');

        $admin_amount = $live_admin_amount ? $live_admin_amount : 0;

        // PPV (VOD)

        $vod_video_amount = PayPerView::sum('amount');

        $vod_amount = $vod_video_amount ? $vod_video_amount : 0;

        $vod_user_amount = PayPerView::sum('user_amount');

        $vod_user_amt = $vod_user_amount ? $vod_user_amount : 0;

        $vod_admin_amount = PayPerView::sum('admin_amount');

        $vod_admin_amt = $vod_admin_amount ? $vod_admin_amount : 0;

        return view('admin.payments.revenue-dashboard')
                ->with('total_revenue',$total_revenue)
                ->with('video_amount', $video_amount)
                ->with('user_amount', $user_amount)
                ->with('admin_amount', $admin_amount)
                ->with('page', 'payments')
                 ->with('vod_amount', $vod_amount)
                ->with('vod_user_amt', $vod_user_amt)
                ->with('vod_admin_amt', $vod_admin_amt)
                ->with('sub_page', 'revenue_system');
    }

    public function user_redeem_requests(Request $request,$id = "") {

        $base_query = RedeemRequest::leftJoin('users' , 'users.id' ,'=' , 'redeem_requests.user_id')
                     ->select('redeem_requests.*');
        
        $user = [];

        if($id) {
            $base_query = $base_query->where('user_id' , $id);

            $user = User::find($id);
        }

        if($request->search_key) {

            $base_query = $base_query
                    ->where('users.name','LIKE','%'.$request->search_key.'%')
                    ->orWhere('redeem_requests.payment_mode','LIKE','%'.$request->search_key.'%');
        }

        if($request->status!=''){
            $base_query->where('redeem_requests.status',$request->status);

        }

        $data = $base_query->orderBy('redeem_requests.updated_at' , 'desc')->paginate($this->paginate_count);

        return view('admin.users.redeems')
                ->withPage('redeems')
                ->with('sub_page' , 'redeems')
                ->with('data' , $data)
                ->with('user' , $user);
    
    }

    /**
     * @method users_redeems_payout_direct()
     * 
     * @uses to payout for the selected redeem request with direct payment
     *
     * @created Shobana
     *
     * @updated Shobana
     *
     * @param - 
     *
     * @return redirect to view page with success/failure message
     */

    public function users_redeems_payout_direct(Request $request) {

        $validator = Validator::make($request->all() , [
            'redeem_request_id' => 'required|exists:redeem_requests,id',
            'paid_amount' => 'required', 
        ]);

        if($validator->fails()) {

            return redirect()->route('admin.users.redeems')->with('flash_error' , $validator->messages()->all())->withInput();

        } else {

            $redeem_request_details = RedeemRequest::find($request->redeem_request_id);

            if($redeem_request_details) {

                if($redeem_request_details->status == REDEEM_REQUEST_PAID ) {

                    return redirect()->route('admin.users.redeems')->with('flash_error' , tr('redeem_request_status_mismatch'));

                } else {

                    $redeem_request_details->paid_amount = $redeem_request_details->paid_amount + $request->paid_amount;

                    $redeem_request_details->status = REDEEM_REQUEST_PAID;

                    $redeem_request_details->payment_mode = "direct";

                    $redeem_request_details->save();

                
                    $redeem = Redeem::where('user_id', $redeem_request_details->user_id)->first();

                    $redeem->paid += $request->paid_amount;

                    $redeem->remaining = $redeem->total - $redeem->paid;

                    $redeem->save();

                    if ($redeem_request_details->user) {

                        $redeem_request_details->user->paid_amount += $request->paid_amount;

                        $redeem_request_details->user->remaining_amount = $redeem->total - $redeem->paid;

                        $redeem_request_details->user->save();
                    
                    }

                    return redirect()->route('admin.users.redeems')->with('flash_success' , tr('action_success'));

                }

            } else {
                return redirect()->route('admin.users.redeems')->with('flash_error' , tr('something_error'));
            }
        }

    }

    /**
     * @method users_payout_invoice()
     * 
     * @uses to list the categories
     *
     * @created Shobana
     *
     * @updated Shobana
     *
     * @param - 
     *
     * @return redirect to view page with success/failure message
     */

    public function users_redeems_payout_invoice(Request $request) {

        
        $validator = Validator::make($request->all() , [
            'redeem_request_id' => 'required|exists:redeem_requests,id',
            'paid_amount' => 'required', 
            'user_id' => 'required'
            ]);

        if($validator->fails()) {

            return redirect()->route('admin.users.redeems')
                            ->with('flash_error' , implode(',', $validator->messages()->all()))
                            ->withInput();

        } else {

            $redeem_request_details = RedeemRequest::find($request->redeem_request_id);

            if($redeem_request_details) {

                if($redeem_request_details->status == REDEEM_REQUEST_PAID ) {

                    return redirect()->route('admin.users.redeems')->with('flash_error' , tr('redeem_request_status_mismatch'));

                } else {

                    $invoice_data['user_details'] = $user_details = User::find($request->user_id);

                    $invoice_data['redeem_request_id'] = $request->redeem_request_id;

                    $invoice_data['redeem_request_status'] = $redeem_request_details->status;

                    $invoice_data['user_id'] = $request->user_id;

                    $invoice_data['item_name'] = Setting::get('site_name')." - Checkout to"."$user_details ? $user_details->name : -";

                    $invoice_data['payout_amount'] = $request->paid_amount;

                    $data = json_decode(json_encode($invoice_data));

                    return view('admin.users.payout')->with('data' , $data)->withPage('users')->with('sub_page' , 'users');

                }
            
            } else {
                return redirect()->route('admin.users.redeems')->with('flash_error' , tr('redeem_not_found'));

            }
        }

    }

    /**
     * @method users_redeems_payout_response()
     * 
     * @uses to get the response from paypal checkout
     *
     * @created Shobana
     *
     * @updated Shobana
     *
     * @param - 
     *
     * @return redirect to view page with success/failure message
     */

    public function users_redeems_payout_response(Request $request) {

        $validator = Validator::make($request->all() , [
            'redeem_request_id' => 'required|exists:redeem_requests,id',
            ]);

        if($validator->fails()) {

            return redirect()->route('admin.users.redeems')->with('flash_error' , $validator->messages()->all())->withInput();

        } else {

            if($request->success == false) {

                return redirect()->route('admin.users.redeems')->with('flash_error' , tr('redeem_paypal_cancelled'));

            }

            $redeem_request_details = RedeemRequest::find($request->redeem_request_id);

            if($redeem_request_details) {

                if($redeem_request_details->status == REDEEM_REQUEST_PAID ) {

                    return redirect()->route('admin.users.redeems')->with('flash_error' , tr('redeem_request_status_mismatch'));

                } else {


                    $redeem_request_details->paid_amount = $redeem_request_details->paid_amount + $request->payment_gross;

                    $redeem_request_details->status = REDEEM_REQUEST_PAID;

                    $redeem_request_details->payment_mode = PAYPAL;

                    $redeem_request_details->save();

                
                    $redeem = Redeem::where('user_id', $redeem_request_details->user_id)->first();

                    $redeem->paid += $request->payment_gross;

                    $redeem->remaining = $redeem->total - $redeem->paid;

                    $redeem->save();

                    if ($redeem_request_details->user) {

                        $redeem_request_details->user->paid_amount += $request->payment_gross;

                        $redeem_request_details->user->remaining_amount = $redeem->total - $redeem->paid;

                        $redeem_request_details->user->save();
                    
                    }

                    return redirect()->route('admin.users.redeems')->with('flash_success' , tr('action_success'));

                }
            
            } else {
                return redirect()->route('admin.users.redeems')->with('flash_error' , tr('redeem_not_found'));

            }
        }

    }


    public function user_redeem_pay(Request $request) {

        $validator = Validator::make($request->all() , [
            'redeem_request_id' => 'required|exists:redeem_requests,id',
            'paid_amount' => 'required', 
            ]);

        if($validator->fails()) {

            return back()->with('flash_error' , $validator->messages()->all())->withInput();

        } else {

            $redeem_request_details = RedeemRequest::find($request->redeem_request_id);

            if($redeem_request_details) {

                if($redeem_request_details->status == REDEEM_REQUEST_PAID ) {

                    return back()->with('flash_error' , tr('redeem_request_status_mismatch'));

                }


                $message = tr('action_success');

                $redeem_amount = $request->paid_amount ? $request->paid_amount : 0;

                // Check the requested and admin paid amount is equal 

                if($request->paid_amount == $redeem_request_details->request_amount) {

                    $redeem_request_details->paid_amount = $redeem_request_details->paid_amount + $request->paid_amount;

                    $redeem_request_details->status = REDEEM_REQUEST_PAID;

                    $redeem_request_details->save();

                }


                else if($request->paid_amount > $redeem_request_details->request_amount) {

                    $redeem_request_details->paid_amount = $redeem_request_details->paid_amount + $redeem_request_details->request_amount;

                    $redeem_request_details->status = REDEEM_REQUEST_PAID;

                    $redeem_request_details->save();

                    $redeem_amount = $redeem_request_details->request_amount;

                } else {

                    /*$message = tr('redeems_request_admin_less_amount');

                    $redeem_amount = 0; // To restrict the redeeem paid amount update*/

                    $redeem_request_details->paid_amount = $redeem_request_details->paid_amount + $request->paid_amount;

                    $redeem_request_details->status = REDEEM_REQUEST_PAID;

                    $redeem_request_details->save();

                    $redeem_amount = $request->paid_amount;


                }

                $redeem_details = Redeem::where('user_id' , $redeem_request_details->user_id)->first();

                if($redeem_details) {

                    $redeem_details->paid = $redeem_details->paid + $redeem_amount;

                    $redeem_details->remaining = $redeem_details->total - $redeem_details->paid;

                    $redeem_details->save();
                }

                return back()->with('flash_success' , $message);

            } else {
                return back()->with('flash_error' , tr('something_error'));
            }

            // if($redeem_request_details) {

            //     if($redeem_request_details->status == REDEEM_REQUEST_PAID ) {

            //         return back()->with('flash_error' , tr('redeem_request_status_mismatch'));

            //     } else {

            //         $remaining_amount = $redeem_request_details->paid_amount - $request->paid_amount;

            //         $redeem_request_details->paid_amount = $redeem_request_details->paid_amount + $request->paid_amount;

            //         $redeem_request_details->status = REDEEM_REQUEST_PAID;

            //         $redeem_request_details->save();


            //         $redeem = Redeem::where('user_id' , $redeem_request_details->user_id)
            //                 ->select('id','total' , 'paid' , 'remaining' , 'status')->first();


            //         if ($redeem) {

            //             $redeem->paid += $request->paid_amount;

            //             $redeem->remaining = $redeem->total - $redeem->paid;

            //             $redeem->save();

            //         }

            //         return back()->with('flash_success' , tr('action_success'));

            //     }

            // } else {
            //     return back()->with('flash_error' , tr('something_error'));
            // }
        }

    }

    public function followers($id) {
        
        $followers = Follower::select('user_id as id',
                            'users.name as name', 
                            'users.email as email', 
                            'users.picture',
                            'users.description' ,
                            'followers.follower as follower_id' ,
                            'followers.created_at as created_at'
                           )
                    ->leftJoin('users' , 'users.id' ,'=' , 'followers.follower')
                    ->where('user_id', $id)
                    ->orderBy('created_at', 'desc')
                    ->get();

        $user = User::find($id);

        if($user){

            return view('admin.users.followers')
                ->with('model', $followers)
                ->with('user',$user)
                ->with('page','users')
                ->with('sub_page', 'users');
        } else{

            return back()->with('flash_error',tr('user_not_found'));
        }

    }


    public function followings($id) {
        
        $followings = Follower::select('followers.follower as id',
                            'users.name as name', 
                            'users.email as email', 
                            'users.picture',
                            'users.description',
                            'users.id as follower_id' ,
                            'followers.created_at as created_at' 
                           )
                    ->leftJoin('users' , 'users.id' ,'=' , 'followers.user_id')
                    ->where('follower', $id)
                    ->orderBy('created_at', 'desc')->get();

        $user = User::find($id);

        if($user){

            return view('admin.users.followings')
                ->with('model', $followings)
                ->with('user',$user)
                ->with('page','users')
                ->with('sub_page', 'users');
                
        } else{

            return back()->with('flash_error',tr('user_not_found'));
        }
    }


    public function clear_login(Request $request) {

        $user = User::find($request->id);

        if ($user) {

            $user->login_status = 0;

            $user->save();

            return back()->with('flash_success', tr('user_clear'));

        } else {

            return back()->with('flash_error', tr('user_not_found'));
        }


    }

    // Coupons

    /**
    * @method coupon_create()
    *
    * @uses Get the coupon add form fields
    *
    * @created Maheswari
    *
    * @updated Maheswari
    *
    * @param Get the route of add coupon form
    *
    * @return Html form page
    */
    public function coupon_create(){
        
       return view('admin.coupons.create')
                ->with('page','coupons')
                ->with('sub_page','add-coupons');
    }

    /**
    * @method coupon_save()
    *
    * @uses Save/Update the coupon details in database 
    *
    * @created Maheswari
    *
    * @updated Shobana Chandrasekar
    *
    * @param Request to all the coupon details
    *
    * @return add details for success message
    */
    public function coupon_save(Request $request){
        
        $validator = Validator::make($request->all(),[
            'id'=>'exists:coupons,id',
            'title'=>'required',
            'coupon_code'=>$request->id ? 'required|max:10|min:1|unique:coupons,coupon_code,'.$request->id : 'required|unique:coupons,coupon_code|min:1|max:10',
            'amount'=>'required|numeric|min:1|max:5000',
            'amount_type'=>'required',
            'expiry_date'=>'required|date_format:d-m-Y|after:today',
            'no_of_users_limit'=>'required|numeric|min:1|max:1000',
            'per_users_limit'=>'required|numeric|min:1|max:100',
        ]);
    

        if($validator->fails()){

            $error_messages = implode(',',$validator->messages()->all());

            return back()->with('flash_error',$error_messages);
        }
        if($request->id !='') {
           
            $coupon_detail = Coupon::find($request->id); 

            $message=tr('coupon_update_success');

        } else {

            $coupon_detail = new Coupon;

            $coupon_detail->status = DEFAULT_TRUE;

            $message = tr('coupon_add_success');
        }

        // Check the condition amount type equal zero mean percentage
        if($request->amount_type == PERCENTAGE){

            // Amount type zero must should be amount less than or equal 100 only
            if($request->amount <= 100){

                $coupon_detail->amount_type = $request->has('amount_type') ? $request->amount_type :0;
 
                $coupon_detail->amount = $request->has('amount') ?  $request->amount : '';

            } else{

                return back()->with('flash_error',tr('coupon_amount_lessthan_100'));
            }

        } else{

            // This else condition is absoulte amount 

            // Amount type one must should be amount less than or equal 5000 only
            if($request->amount <= 5000){

                $coupon_detail->amount_type=$request->has('amount_type') ? $request->amount_type : 1;

                $coupon_detail->amount=$request->has('amount') ?  $request->amount : '';

            } else{

                return back()->with('flash_error',tr('coupon_amount_lessthan_5000'));
            }
        }
        $coupon_detail->title=ucfirst($request->title);

        // Remove the string space and special characters
        $coupon_code_format  = preg_replace("/[^A-Za-z0-9\-]+/", "", $request->coupon_code);

        // Replace the string uppercase format
        $coupon_detail->coupon_code = strtoupper($coupon_code_format);

        // Convert date format year,month,date purpose of database storing
        $coupon_detail->expiry_date = date('Y-m-d',strtotime($request->expiry_date));
      
        $coupon_detail->description = $request->has('description')? $request->description : '' ;

        // Based no users limit need to apply coupons
        $coupon_detail->no_of_users_limit = $request->no_of_users_limit;

        $coupon_detail->per_users_limit = $request->per_users_limit;

        if($coupon_detail->save()){

            return redirect()->route('admin.coupon.view', $coupon_detail->id)->with('flash_success',$message);

        } else {

            return back()->with('flash_error',tr('coupon_not_found_error'));
        }
        
    }

    /**
    * @method coupon_index()
    *
    * @uses Get the coupon details for all 
    *
    * @created Maheswari
    *
    * @updated Maheswari
    *
    * @param Get the coupon list in table
    *
    * @return Html table from coupon list page
    */
    public function coupon_index(Request $request){

        $base_query = Coupon::orderBy('updated_at','desc');

        if($request->search_key) {

            $base_query = $base_query
                    ->where('coupon_code','LIKE','%'.$request->search_key.'%')
                    ->orWhere('coupons.title','LIKE','%'.$request->search_key.'%');
        }

        if($request->amount_type!=''){

            $base_query->where('amount_type',$request->amount_type);

        }

        if($request->status!=''){

            $base_query->where('status',$request->status);

        }

        $coupons = $base_query->paginate($this->paginate_count);


        return view('admin.coupons.index')
            ->with('coupons', $coupons)
            ->with('page','coupons')
            ->with('sub_page','view-coupons');        
    }

    /**
    * @method coupon_edit() 
    *
    * @uses Edit the coupon details and get the coupon edit form for 
    *
    * @created Maheswari
    *
    * @updated Maheswari
    *
    * @param Coupon id
    *
    * @return Get the html form
    */
    public function coupon_edit($id){

        if($id){

            $edit_coupon = Coupon::find($id);

            if($edit_coupon){

                return view('admin.coupons.edit')
                        ->with('edit_coupon',$edit_coupon)
                        ->with('page','coupons')
                        ->with('sub_page','add-coupons');

            } else{
                return back()->with('flash_error',tr('coupon_not_found_error'));
            }
        }else{

            return back()->with('flash_error',tr('coupon_id_not_found_error'));
        }
    }

    /**
    * @method coupon_delete()
    *
    * @uses Delete the particular coupon detail
    *
    * @created Maheswari
    *
    * @updated Maheswari
    *
    * @param Coupon id
    *
    * @return Deleted Success message
    */
    public function coupon_delete($id){

        if($id){

            $delete_coupon = Coupon::find($id);

            if($delete_coupon){

                $delete_coupon->delete();

                return back()->with('flash_success',tr('coupon_delete_success'));
            } else{

                return back()->with('flash_error',tr('coupon_not_found_error'));
            }

        } else{

            return back()->with('flash_error',tr('coupon_id_not_found_error'));
        }
    }

    /**
    * @method coupon_status_change()
    * 
    * @uses Coupon status for active and inactive update the status function
    *
    * @created Maheswari
    *
    * @updated Maheswari
    *
    * @param Request the coupon id
    *
    * @return Success message for active/inactive
    */
    public function coupon_status_change(Request $request){

        if($request->id){

            $coupon_status = Coupon::find($request->id);

            if($coupon_status) {

                $coupon_status->status = $request->status;

                $coupon_status->save();

            } else {

                return back()->with('flash_error',tr('coupon_not_found_error'));
            }

            if($request->status==DEFAULT_FALSE){

                $message = tr('coupon_decline_success');

            } 

            if($request->status==DEFAULT_TRUE){

                $message = tr('coupon_approve_success');
            }
            return back()->with('flash_success',$message);

        } else{

            return back()->with('flash_error',tr('coupon_id_not_found_error'));
        }
    }

    /**
    * @method coupon_view()
    *
    * @uses Get the particular coupon details for view page content
    *
    * @created Maheswari
    *
    * @updated Maheswaari
    *
    * @param Coupon id
    *
    * @return Html view page with coupon detail
    */
    public function coupon_view($id){

        if($id){

            $view_coupon = Coupon::find($id);

            if($view_coupon){

                return view('admin.coupons.view')
                    ->with('view_coupon',$view_coupon)
                    ->with('page','coupons')
                    ->with('sub_page','add-coupons');
            }

        } else{

            return back()->with('flash_error',tr('coupon_id_not_found_error'));
        }
    }


    /**
     * @method user_subscription_pause
     *
     * To prevent automatic subscriptioon, user have option to cancel subscription
     *
     * @created shobana Chandrasekar
     *
     * @updated
     *
     * @param object $request - USer details & payment details
     *
     * @return boolean response with message
     */
    public function user_subscription_pause(Request $request) {

        $user_payment = UserSubscription::where('user_id', $request->id)->where('status', PAID_STATUS)->orderBy('created_at', 'desc')->first();

        if($user_payment) {

            $user_payment->is_cancelled = AUTORENEWAL_CANCELLED;

            $user_payment->cancel_reason = $request->cancel_reason ? $request->cancel_reason : tr('admin_paused_subscription');

            $user_payment->save();

            return back()->with('flash_success', tr('cancel_subscription_success'));

        } else {

            return back()->with('flash_error', Helper::error_message(163));

        }        

    }

    /**
     * @method user_subscription_enable
     *
     * @uses To prevent automatic subscriptioon, user have option to cancel subscription
     *
     * @created shobana Chandrasekar
     *
     * @updated
     *
     * @param object $request - USer details & payment details
     *
     * @return boolean response with message
     */
    public function user_subscription_enable(Request $request) {

        $user_payment = UserSubscription::where('user_id', $request->id)->where('status', PAID_STATUS)->orderBy('created_at', 'desc')
            ->where('is_cancelled', AUTORENEWAL_CANCELLED)
            ->first();

        if($user_payment) {

            $user_payment->is_cancelled = AUTORENEWAL_ENABLED;

            $user_payment->save();

            return back()->with('flash_success', tr('autorenewal_enable_success'));

        } else {

            return back()->with('flash_error', Helper::error_message(163));

        }        

    }  

    /**
    * @method video_upload()
    *
    * @uses Get the video upload page
    *
    * @created shobana Chandrasekar
    *
    * @updated
    *
    * @param Get the route of  vidoe upload form
    *
    * @return Html form page
    */
    public function vod_videos_create() {

        // Select only content creators

        $users = User::select('id', 'name')
            ->where('status', USER_APPROVED)
            ->where('user_type', SUBSCRIBED_USER)->get();

        return view('admin.vod-videos.create')        
                ->with('page','vod_videos')                    
                ->with('sub_page', 'upload-vod_videos')    
                ->with('users', $users);
    }

   /**
    * @method video_save()
    * 
    * @uses upload the video in admin and save the video details
    *
    * @created Shobana Chandrasker
    *
    * @updated
    *
    * @param request the video details
    *
    * @return success message
    *
    */
    public function vod_videos_save(Request $request){

        $response = VodRepo::vod_videos_save($request)->getData();

        if($response->success){
            
            return redirect(route('admin.vod-videos.view', ['video_id' =>$response->data->vod_id]))->with('flash_success',$response->message);

        } else{

            return back()->with('flash_error',$response->error_messages);
        }

    }

   /**
    * @method vod_videos_index()
    *
    * @uses View the video details list in this page 
    *
    * @created Shobana Chandrasekar
    *
    * @updated Maheswari
    * 
    * @param get the all details in this list
    *
    * @return Html list page
    */
    public function vod_videos_index(Request $request) {
      
        $base_query = VodVideo::vodResponse()
                    ->orderBy('created_at','desc');

        if ($request->user_id) {

            $base_query->where('user_id', $request->user_id);
        }

        if($request->admin_status!=''){

            $base_query->where('admin_status', $request->admin_status);
        }

        if($request->payment_status!=''){

            if($request->payment_status == PPV_ENABLED){

                $base_query->where('vod_videos.amount','>',0);
            }
            else{
                $base_query->where('vod_videos.amount','<=',0);

            }

        }


        if($request->search_key) {

            $base_query->where(function ($query) use ($request) {
                $query->where('users.name','LIKE','%'.$request->search_key.'%');
                $query->orWhere('title','LIKE','%'.$request->search_key.'%');
            });
        }

        $vod_videos = $base_query->paginate($this->paginate_count);

        if($vod_videos) {

            return  view('admin.vod-videos.index')        
                ->with('page','vod_videos')                    
                ->with('sub_page', 'view-vod_videos')
                ->with('search_key', $request->search_key)
                ->with('video_list', $vod_videos);
                
        } else {

            return back()->with('flash_error',tr('video_not_found_error'));
        }
    }

   /**
    * @method vod_videos_edit()
    *
    * @uses Get the video upload page
    *
    * @created Shobana Chandrasekar
    *
    * @updated 
    *
    * @param Get the route of  vidoe upload form
    *
    * @return Html form page
    */
    public function vod_videos_edit($id) {

        $vod_videos_details = VodVideo::vodResponse()->find($id);

        if($vod_videos_details) {

            if($vod_videos_details->publish_time) {

                $vod_videos_details->publish_time = date('m/d/Y', strtotime($vod_videos_details->publish_time));
            }

            $vod_videos_details->publish_type = $vod_videos_details->publish_status == VIDEO_PUBLISHED ? PUBLISH_NOW : PUBLISH_LATER;

            $users = User::select('id', 'name')->where('status', USER_APPROVED)->where('user_type', SUBSCRIBED_USER)->get();

            return view('admin.vod-videos.edit')    
                ->with('page','vod_videos')                    
                ->with('sub_page', 'view-vod_videos')
                ->with('video_edit', $vod_videos_details)
                ->with('users', $users) ;

        } else {

            return back()->with('flash_error',tr('video_not_found_error'));
        }
    }

   /**
    * @method vod_videos_status_update()
    * 
    * @uses Change video status in approve/decline
    *
    * @created Shobana Chandrasekar
    *
    * @updated -
    *
    * @param Request the video id
    *
    * @return Success message 
    */
    public function vod_videos_status_update(Request $request){
        
        $response = VodRepo::vod_videos_status($request)->getData();

        if($response->success) {

            return back()->with('flash_success',$response->message);
        } 

        return back()->with('flash_error',$response->error_messages);
    }

   /**
    * @method vod_videos_publish()
    * 
    * @uses Do the video as published
    *
    * @created Shobana Chandrasekar
    *
    * @updated -
    *
    * @param Request the video id
    *
    * @return Success message 
    */
    public function vod_videos_publish(Request $request){
        
        $response = VodRepo::vod_videos_publish($request)->getData();

        if($response->success) {

            return back()->with('flash_success',$response->message); 
        } 

        return back()->with('flash_error',$response->error_messages);
        
    }


   /**
    * @method vod_videos_delete()
    *
    * Description : Delete the particular video
    *
    * @created Shobana Chandrasekar
    *
    * @updated -
    *
    * @param request video id
    *
    * @return success message
    */
    public function vod_videos_delete(Request $request) {

        $response = VodRepo::vod_videos_delete($request)->getData();

        if($response->success) {

            return back()->with('flash_success',$response->message);
        } 

        return back()->with('flash_error',$response->error_messages);
    }

    /**
    * @method vod_videos_view()
    *
    * @uses Added the ppv in particular video
    *
    * @created Shobana Chandrasekar
    *
    * @updated
    *
    * @param Request video id 
    *
    * @return success message
    */
    public function vod_videos_view(Request $request) {
        
        $view_video = VodVideo::find($request->video_id);
       
       
        if($view_video){

            return view('admin.vod-videos.view')   
                ->with('page','vod_videos')                    
                ->with('sub_page', 'view-vod_videos')
                ->with('video', $view_video);
        } 

        return back()->with('flash_error',tr('video_not_found_error'));
    }

    /**
    * @method vod_videos_ppv_create()
    *
    * Description : Added the ppv in particular video
    *
    * @created :Shobana Chandrasekar
    *
    * @updated : -
    *
    * @param Request video id 
    *
    * @return success message
    */
    public function vod_videos_ppv_create(Request $request){

        $response = VodRepo::vod_videos_set_ppv($request)->getData();
        
        if($response->success){

            return back()->with('flash_success',$response->message);
        }

        return back()->with('flash_error',$response->error_messages);
    }

    /**
    * @method vod_videos_ppv_delete()
    *
    * Description : Delete the ppv in particular video
    *
    * @created : Shobana Chandrasekar
    *
    * @updated : -
    *
    * @param Request video id 
    *
    * @return success message
    */
    public function vod_videos_ppv_delete(Request $request){

       $response = VodRepo::vod_videos_remove_ppv($request)->getData();

       if($response->success){

        return back()->with('flash_success',$response->message);

       } 

        return back()->with('flash_error',$response->error_messages);
    }

   /**
    * @method vod_payments_list()
    *
    * @uses Display the vod video payments list
    *
    * @created Shobana Chandrasekar
    *
    * @updated Maheswari
    *
    * @param get the all details in this list
    *
    * @return Html list page
    */
    public function vod_payments_list(Request $request) {

        $base_query = PayPerView::orderBy('pay_per_views.created_at','desc');

       
        if($request->search_key) {

            $base_query->where(function ($query) use ($request) {
                $query->where('users.name','LIKE','%'.$request->search_key.'%');
                $query->orWhere('pay_per_views.payment_id','LIKE','%'.$request->search_key.'%');
                $query->orWhere('vod_videos.title','LIKE','%'.$request->search_key.'%');
            });
        }
        
        if($request->paid_status!=''){

         $base_query->where('pay_per_views.status',$request->paid_status);

        }

        if($request->payment_mode!=''){

            $base_query->where('pay_per_views.payment_mode',$request->payment_mode);
        }

        

        $vod_payments = $base_query->commonResponse()->paginate(10);

        if($vod_payments){

            return  view('admin.payments.vod-payments')
                        ->with('vod_payments', $vod_payments)
                        ->with('page', 'payments')
                        ->with('sub_page', 'vod-payments');
        } 

        return back()->with('flash_error',tr('vod_payment_error'));
    }   


    /**
    * @method vod_payments_view()
    *
    * @uses Display the vod video payments list
    *
    * @created Shobana Chandrasekar
    *
    * @updated Maheswari
    *
    * @param get the all details in this list
    *
    * @return Html list page
    */
    public function vod_payments_view(Request $request) {

        $vod_payments = PayPerView::find($request->vod_payment_id);
        
        if($vod_payments) {

            return  view('admin.payments.vod-payments_view')
                        ->with('vod_payments', $vod_payments)
                        ->with('page', 'payments')
                        ->with('sub_page', 'vod-payments');
        } 

        return back()->with('flash_error',tr('vod_payment_error'));
    }

    /**
     * @method become_creator()
     *
     * @uses To change the viewer into creator
     *
     * @created Shobana Chandrasekar
     *
     * @updated
     *
     * @param integer $request - User id,token
     *
     * @return response of json details
     */
    public function become_creator(Request $request) {

        $user_details = User::find($request->id);

        if ($user_details) {

            // Check the user registered as content creator /viewer

            if ($user_details->is_content_creator == CREATOR_STATUS) {

                return back()->with('flash_error', tr('registered_as_content_creator'));

            } else {

                $user_details->is_content_creator = CREATOR_STATUS;

                $user_details->save();

                return back()->with('flash_success', tr('become_creator')); 

            }         

        } 
        
        return back()->with('flash_error', tr('user_not_found'));
    }

    /**
     * @method streamer_galleries_upload()
     *
     * To display a upload form
     *
     * @created Shobana Chandrasekar
     *
     * @updated 
     *
     * @param 
     *
     * @return response of html page
     */
    public function streamer_galleries_upload(Request $request) {

        $user_details = User::find($request->user_id);
        
        if ($user_details) {

            return view('admin.users.streamer_galleries.upload')
                ->with('page', 'users')
                ->with('sub_page', 'view-users')
                ->with('user_details', $user_details);

        } else {

            return back()->with('flash_error', tr('user_not_found'));
        }

    }

    /**
     * @method streamer_galleries_save()
     *
     * To save gallery details of the streamer
     *
     * @created - Shobana Chandrasekar
     *
     * @updated - - 
     *
     * @param object $request - Model Object
     *
     * @return response of success / Failure
     */
    public function streamer_galleries_save(Request $request) {

        try {

            $response = StreamerGalleryRepo::streamer_galleries_save($request)->getData();

            if ($response->success) {

                return back()->with('flash_success', $response->message);

            } else {

                throw new Exception($response->error_messages, $response->error_code);
                
            }

        } catch (Exception $e) {

            return back()->with('flash_error', $e->getMessage());

        }

    }

    /**
     * @method streamer_galleries_list()
     *
     * To load galleries based on user id
     *
     * @created - Shobana Chandrasekar
     *
     * @updated - - 
     *
     * @param model image object - $request
     *
     * @return response of succes failure 
     */
    public function streamer_galleries_list($user_id, Request $request) {

        try {

            $request->request->add([
                'user_id'=>$user_id
            ]);

            $validator = Validator::make( $request->all(), array(
                        'user_id'=>'required|exists:users,id',
                    ),[
                        'user_id.exists'=>tr('user_not_found'),
                    ]
            );
            
            if($validator->fails()) {

                $error_messages = implode(',', $validator->messages()->all());

                throw new Exception($error_messages, 101);                
            } 

            $user_details = User::find($request->user_id);

            $streamer_galleries = StreamerGallery::select('id as gallery_id', 'image', \DB::raw('DATE_FORMAT(created_at , "%e %b %y %r") as created_date'))
                ->where('user_id', $request->user_id)
                ->orderBy('created_at', 'desc')
                ->paginate($this->paginate_count);

            return view('admin.users.streamer_galleries.index')
                    ->with('page', 'users')
                    ->with('sub_page', 'view-users')
                    ->with('streamer_galleries', $streamer_galleries)
                    ->with('user_details', $user_details);

        } catch (Exception $e) {

            $response_array = ['success'=>false, 'error_messages'=>$e->getMessage(), 'error_code'=>$e->getCode()];

            return back()->with('flash_error', $e->getMessage());

        }
    }

    /**
     * @method streamer_galleries_delete()
     *
     * To delete particular image based on id
     *
     * @created - Shobana Chandrasekar
     *
     * @updated - - 
     *
     * @param model image object - $request
     *
     * @return response of succes failure 
     */
    public function streamer_galleries_delete(Request $request) {

        try {

            $response = StreamerGalleryRepo::streamer_galleries_delete($request)->getData();

            if ($response->success) {

                return back()->with('flash_success', $response->message);

            } else {

                throw new Exception($response->error_messages, $response->error_code);
                
            }

        } catch (Exception $e) {

            $response_array = ['success'=>false, 'error_messages'=>$e->getMessage(), 'error_code'=>$e->getCode()];

            return back()->with('flash_error', $e->getMessage());

        }
    }

    /**
     * @method automatic_subscribers
     *
     * To list out automatic subscribers
     *
     * @created By - shobana
     *
     * @updated by - -
     *
     * @param integer $id - User id (Optional)
     * 
     * @return - response of array of automatic subscribers
     *
     */
    public function automatic_subscribers() {

        $datas = UserSubscription::select(DB::raw('max(user_subscriptions.id) as user_subscription_id'),'user_subscriptions.*')
                        ->leftjoin('subscriptions', 'subscriptions.id','=' ,'subscription_id')
                        ->where('subscriptions.amount', '>', 0)
                        ->where('user_subscriptions.status', PAID_STATUS)
                      //  ->where('user_subscriptions.is_cancelled', AUTORENEWAL_ENABLED)
                        ->groupBy('user_subscriptions.user_id')
                       ->orderBy('user_subscriptions.created_at' , 'desc')
                       ->get();
        $payments = [];

        $amount = 0;

        foreach ($datas as $key => $value) {

            $value = UserSubscription::find($value->user_subscription_id);

            if($value) {

                if ($value->is_cancelled == AUTORENEWAL_ENABLED) {

                    if ($value->subscription) {

                        $amount += $value->subscription ? $value->subscription->amount : 0;

                    }
                    
                    $payments[] = [

                        'id'=> $value->user_subscription_id,

                        'user_id'=>$value->user_id,

                        'subscription_id'=>$value->subscription_id,

                        'payment_id'=>$value->payment_id,

                        'amount'=>$value->subscription ? $value->subscription->amount : '',

                        'payment_mode'=>$value->payment_mode,

                        'expiry_date'=>date('d-m-Y h:i a', strtotime($value->expiry_date)),

                        'user_name' => $value->user ? $value->user->name : tr('user_not_available'),

                        'subscription_name'=>$value->subscription ? $value->subscription->title : '',

                        'unique_id'=>$value->subscription ? $value->subscription->unique_id : '',

                    ];

                }

            } else {

                Log::info('Subscription not found');
            }

        }

        $payments =json_decode(json_encode($payments));

        return view('admin.subscriptions.subscribers.automatic')
                        ->withPage('subscriptions')
                        ->with('amount', $amount)
                        ->with('sub_page','automatic')->with('payments', $payments);        

    }

    /**
     * @method cancelled_subscribers
     *
     * To list out cancelled subscribers
     *
     * @created By - shobana
     *
     * @updated by - -
     *
     * @param integer $id - User id (Optional)
     * 
     * @return - response of array of cancelled subscribers
     *
     */
    public function cancelled_subscribers() {

        $datas = UserSubscription::select(DB::raw('max(user_subscriptions.id) as user_subscription_id'),'user_subscriptions.*')
                        ->where('user_subscriptions.status', PAID_STATUS)
                        ->leftjoin('subscriptions', 'subscriptions.id','=' ,'subscription_id')
                        ->where('user_subscriptions.is_cancelled', AUTORENEWAL_CANCELLED)
                        ->groupBy('user_subscriptions.user_id')
                        ->orderBy('user_subscriptions.created_at' , 'desc')
                        ->get();

        $payments = [];

        foreach ($datas as $key => $value) {

            $value = UserSubscription::find($value->user_subscription_id);

            if ($value) {
            
                $payments[] = [

                    'id'=> $value->user_subscription_id,

                    'user_id'=>$value->user_id,

                    'subscription_id'=>$value->subscription_id,

                    'payment_id'=>$value->payment_id,

                    'amount'=>$value->subscription ? $value->subscription->amount : '',

                    'payment_mode'=>$value->payment_mode,

                    'expiry_date'=>$value->expiry_date,

                    'user_name' => $value->user ? $value->user->name : tr('user_not_available'),

                    'subscription_name'=>$value->subscription ? $value->subscription->title : '',

                    'unique_id'=>$value->subscription ? $value->subscription->unique_id : '',

                    'cancel_reason'=>$value->cancel_reason

                ];

            }

        }

        $payments =json_decode(json_encode($payments));

        return view('admin.subscriptions.subscribers.cancelled')
                        ->withPage('subscriptions')
                        ->with('sub_page','cancelled')->with('payments', $payments);      

    }



    /**
     * @method live_videos_delete()
     *
     * To delete a live streaming video which is stopped by the user
     *
     * @created shobana chandrasekar
     *
     * @updated by - 
     *
     * @param integer $request - Video id
     *
     * @return repsonse of success/failure message
     */
    public function live_videos_delete(Request $request) {

        try {

            $video = LiveVideo::find($request->id);
            
            if($video->status== VIDEO_STREAMING_ONGOING){

                return back()->with('flash_error', tr('broadcast_video_delete_failure'));
            }

            if ($video) {                

                $video->delete();

            } else {

                throw new Exception(tr('live_video_not_found'));
                
            }

            return back()->with('flash_success', tr('live_video_delete_success'));

        } catch(Exception $e) {

            return back()->with('flash_error', $e->getMessage());

        }

    } 

    /**
    * @method ios_control()
    *
    * @uses To update the ios payment subscription status
    *
    * @param settings key value
    *
    * @created Maheswari
    *
    * @updated Maheswari
    *
    * @return response of success / failure message.
    */
    public function ios_control(){

        if(Auth::guard('admin')->check()){

            return view('admin.settings.ios-control')->with('page','ios-control');

        } else {

            return back();
        }
    }

    /**
    * @method ios_control()
    *
    * @uses To update the ios settings value
    *
    * @param settings key value
    *
    * @created Maheswari
    *
    * @updated Maheswari
    *
    * @return response of success / failure message.
    */
    public function ios_control_save(Request $request){

        if(Auth::guard('admin')->check()){

            $settings = Settings::get();

            foreach ($settings as $key => $setting_details) {

                # code...

                $current_key = "";

                $current_key = $setting_details->key;
                
                    if($request->has($current_key)) {

                        $setting_details->value = $request->$current_key;
                    }

                $setting_details->save();
            }

            return back()->with('flash_success',tr('settings_success'));

        } else {

            return back();
        }
    }

    /**
     * @method live_groups_index()
     *
     * @uses to list groups based on the user id or all groups
     *
     * @created Shobana
     *
     * @updated Anjana
     *
     * @param integer user_id (optional)
     *
     * @return view page 
     */
    public function live_groups_index(Request $request) {

        try {

            $user_details = [];

            $base_query = LiveGroup::orderBy('live_groups.updated_at' , 'desc');

            if($request->search_key) {

                $base_query = $base_query->where('users.name','LIKE','%'.$request->search_key.'%')
                        ->orWhere('live_groups.name','LIKE','%'.$request->search_key.'%');
            }

            // To display owned groups by the login user
            if($request->user_id) {

                $base_query = $base_query->where('live_groups.user_id' , $request->user_id)
                        ->leftJoin('live_group_members' , 'live_groups.id' , '=', 'live_group_members.live_group_id' )
                        ->orWhere('live_group_members.member_id' , $request->user_id);

                $user_details = User::find($request->user_id);
            }

            $groups = $base_query->baseResponse()->paginate(10);

            foreach($groups as $group){

                $group->members_count = LiveGroupMember::where('live_group_id',$group->live_group_id)->count();
            
            }

            $groups_data = $groups;
            
            return view('admin.live_groups.index')
                        ->with('page' , 'live_groups')
                        ->with('sub_page', '')
                        ->with('data', $groups)
                        ->with('search_key', $request->search_key)
                        ->with('user_details', $user_details);

        } catch(Exception $e) {

            return redirect()->route('admin.live_groups.index')->with('flash_error', $e->getMessage());

        }

    }

    /**
     * @method live_groups_delete()
     *
     * @uses to delete the selected group 
     *
     * @created  Shobana
     *
     * @updated 
     *
     * @param integer live_group_id
     *
     * @return redirect to index page with success message
     */
    public function live_groups_delete(Request $request) {

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

                $group_details = LiveGroup::where('id',$request->live_group_id)->first();

                if(!$group_details) {

                    throw new Exception(Helper::error_message(908), 908);
                }

                if($group_details->delete()) {

                    return redirect()->route('admin.live_groups.index')->with('flash_success' , Helper::get_message(135));

                } else {

                    throw new Exception(Helper::error_message(907), 907);
                }

            }

            return response()->json($response_array,200);

        } catch(Exception $e) {

            $error_message = $e->getMessage();

            $error_code = $e->getCode();

            return redirect()->route('admin.live_groups.index')->with('flash_error' , $error_message);

        }

    }

    /**
     * @method live_groups_view()
     *
     * @uses to get the selected group details
     *
     * @created  Shobana
     *
     * @updated 
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

                $members = LiveGroupMember::where('live_group_id' , $request->live_group_id)->commonResponse()->get();

                $group_details->total_members = $members->count();

                return view('admin.live_groups.view')->withPage('live_groups')
                        ->with('sub_page' , 'live_groups_view')
                        ->with('group_details' , $group_details)
                        ->with('members' , $members);
            }

            return response()->json($response_array,200);

        } catch(Exception $e) {

            $error_message = $e->getMessage();

            $error_code = $e->getCode();

            return redirect()->route('admin.live_groups.index')->with('flash_error' , $error_message);

        }

    }

    /**
     * @method custom_live_videos()
     *
     * @created shobana
     *
     * @updated
     *
     * @return Live videos details with view page
     */
    public function custom_live_videos(Request $request) {

        $base_query = CustomLiveVideo::orderBy('custom_live_videos.created_at','desc');

        if($request->search_key) {

            $base_query->where(function ($query) use ($request) {
                $query->where('users.name','LIKE','%'.$request->search_key.'%');
                $query->orWhere('custom_live_videos.title','LIKE','%'.$request->search_key.'%');
            });
        }

        if($request->status!=''){

            $base_query->where('custom_live_videos.status',$request->status);
        }
        
        $custom_live_videos = $base_query->has('user')->commonResponse()->paginate($this->paginate_count);

        return view('admin.custom_live_videos.index')
                    ->withPage('custom_live_videos')
                    ->with('sub_page', 'view-custom_live_videos')
                    ->with('search_key', $request->search_key)
                    ->with('live_tv_videos', $custom_live_videos);
    }

    /**
     * @method create_live_video()
     *
     * @created shobana
     *
     * @updated -
     *
     * @return create live video page
     */

    public function custom_live_videos_create() {

        $model = new CustomLiveVideo;

        $users = User::select('name', 'id')->where('is_content_creator', CREATOR_STATUS)->get();

        return view('admin.custom_live_videos.create')
                ->withPage('custom_live_videos')
                ->with('sub_page', 'add-custom_live_videos')
                ->withModel($model)
                ->with('users', $users);
    }

    /**
     * @method custom_live_videos_edit()
     *
     * @created shobana
     *
     * @updated
     *
     * @return edit live video page with selected record
     */
    public function custom_live_videos_edit(Request $request) {

        $model = CustomLiveVideo::find($request->id);

        if(!$model) {
            return redirect()->route('admin.custom.live')
                ->with('flash_error', tr('custom_live_video_not_found'));
        }

        $users = User::select('name', 'id')->where('is_content_creator', CREATOR_STATUS)->get();

        return view('admin.custom_live_videos.edit')        
                    ->withPage('custom_live_videos')
                    ->with('sub_page', 'view-custom_live_videos')
                    ->withModel($model)          
                    ->with('users', $users);
    }

    /**
     * @method custom_live_videos_save()
     *
     * @created shobana
     *
     * @updated
     *
     * @return Save the form data of the live video
     */
    public function custom_live_videos_save(Request $request) {

        $response = CommonRepo::save_custom_live_video($request)->getData();

        if($response->success) {

            return redirect(route('admin.custom.live.view', $response->data->id))->with('flash_success', $response->message);

        } else {

            return back()->with('flash_error', $response->message);

        }

    }

    /**
     * @method custom_live_videos_change_status()
     *
     * @created shobana
     *
     * @updated -
     *
     * @return Update the status of the live video.
     */
    public function custom_live_videos_change_status(Request $request) {

        $model = CustomLiveVideo::find($request->id);

        if(!$model) {

            return redirect()->route('admin.custom.live')->with('flash_error' , tr('custom_live_video_not_found'));
        }

        $model->status = $model->status ?  DEFAULT_FALSE : DEFAULT_TRUE;

        $model->save();

        $message = $model->status ? tr('live_custom_video_approved_success') : tr('live_custom_video_declined_success');

        return back()->with('flash_success', $message);

    }

    /**
     * @method custom_live_videos_delete()
     *
     * @created shobana
     *
     * @updated
     *
     * @return delete the selected record
     */
    public function custom_live_videos_delete(Request $request) {
        
        if($model = CustomLiveVideo::where('id',$request->id)->first()) {

            if ($model->delete()) {
                
                return back()->with('flash_success',tr('live_custom_video_delete_success'));   
            }
        }

        return back()->with('flash_error',tr('something_error'));
    }

    /**
     * @method custom_live_videos_view()
     *     
     * @created shobana
     *
     * @updated
     *
     * @return view the selected record
     */

    public function custom_live_videos_view($id) {

        if($model = CustomLiveVideo::find($id)) {

            return view('admin.custom_live_videos.view')
                        ->withPage('custom_live_videos')
                        ->with('sub_page','view-custom_live_videos')
                        ->with('video' , $model);

        } else {

            return back()->with('flash_error',tr('custom_live_video_not_found'));
        }
        
    }

    /**
     * @method admins_create()
     *
     * To create a admin only super admin can access this option
     * 
     * @created - Shobana Chandrasekar
     *
     * @updated -  
     *
     * @param object $request - -
     *
     * @return response of html page with details
     */
    public function admins_create(Request $request) {

        $model = new Admin();

        return view('admin.admins.create')
                ->with('model', $model)
                ->with('page', 'admins')
                ->with('sub_page', 'create-admins');
    }

    /**
     * @method admins_edit()
     *
     * To edit a admin based on admin id only super admin can access this option
     * 
     * @created - Shobana Chandrasekar
     *
     * @updated -  
     *
     * @param object $request - Admin Id
     *
     * @return response of html page with details
     */
    public function admins_edit(Request $request) {

        $model = Admin::find($request->id);

        if ($model) {

            return view('admin.admins.edit')
                ->with('model', $model)
                ->with('page', 'admins')
                ->with('sub_page', 'create-admins');

        } else {

            return back()->with('flash_error', tr('admin_not_found'));
        }

    }

    /**
     * @method admins_view()
     *
     * To view a admin based on admin id only super admin can access this option
     * 
     * @created - Shobana Chandrasekar
     *
     * @updated -  
     *
     * @param object $request - Admin Id
     *
     * @return response of html page with details
     */
    public function admins_view(Request $request) {

        $model = Admin::find($request->id);

        if ($model) {

            return view('admin.admins.view')
                ->with('model', $model)
                ->with('page', 'admins')
                ->with('sub_page', 'admins-index');

        } else {

            return back()->with('flash_error', tr('admin_not_found'));

        }
    }


    /**
     * @method admins_delete()
     *
     * To delete a admin based on admin id. only super admin can access this option
     * 
     * @created - Shobana Chandrasekar
     *
     * @updated -  
     *
     * @param object $request - Admin Id
     *
     * @return response of html page with details
     */
    public function admins_delete(Request $request) {

        $model = Admin::find($request->id);

        if ($model) {

            if ($model->delete()) {

                return back()->with('flash_success', tr('admin_delete_success'));

            } else {

                return back()->with('flash_error', tr('admin_delete_failure'));

            }

        } else {

            return back()->with('flash_error', tr('admin_not_found'));

        }
    }

    /**
     * @method admins_status()
     *
     * To change the status of the admin, based on admin id. only super admin can access this option
     * 
     * @created - Shobana Chandrasekar
     *
     * @updated -  
     *
     * @param object $request - Admin Id
     *
     * @return response of html page with details
     */
    public function admins_status(Request $request) {

        $model = Admin::find($request->id);

        if ($model) {

            $model->is_activated = $model->is_activated ? ADMiN_DECLINE_STATUS : ADMIN_APPROVE_STATUS;

            if ($model->save()) {

                if ($model->status) {

                    return back()->with('flash_success', tr('admin_approve_success'));

                } else {

                    return back()->with('flash_success', tr('admin_decline_success'));

                }   

            } else {

                return back()->with('flash_error', tr('admin_not_saved'));

            }

        } else {

            return back()->with('flash_error', tr('admin_not_found'));

        }
    }

    /**
     * @method admins_index()
     *
     * To list out admins (only super admin can access this option)
     * 
     * @created - Shobana Chandrasekar
     *
     * @updated -  
     *
     * @param object $request - 
     *
     * @return response of html page with details
     */
    public function admins_index(Request $request) {

        $data = Admin::orderBy('created_at', 'desc')->paginate($this->paginate_count);

        return view('admin.admins.index')
                ->with('data', $data)
                ->with('page', 'admins')
                ->with('sub_page', 'admins-index');
        
    }

    /**
     * @method admins_save()
     *
     * To save the admin details
     * 
     * @created - Shobana Chandrasekar
     *
     * @updated -  
     *
     * @param object $request - Admin Id
     *
     * @return response of html page with details
     */
    public function admins_save(Request $request) {

        $demo_logins = explode(',', Setting::get('demo_users'));

        $validator = Validator::make( $request->all(),array(
                'name' => 'regex:/^[a-zA-Z]*$/|max:100',
                'email' => in_array($request->email, $demo_logins) ?
                    ($request->id ? 'required|email|unique:users,email,'.$request->id.',id' : 'required|email|unique:users,email,NULL,id') : ($request->id ? 'required|email|unique:users,email,'.$request->id.',id' : 'required|email|unique:users,email,NULL,id'),
                'mobile' => 'digits_between:4,16',
                'address' => 'max:300',
                'id' => 'exists:admins,id',
                'picture' => 'mimes:jpeg,jpg,png',
                'description'=>'required|max:255',
                'password' => $request->id ? '' : 'required|min:6|confirmed',
            )
        );
        
        if($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            return back()->with('flash_error', $error_messages);

        } else {

            $admin = $request->id ? Admin::find($request->id) : new Admin;

            if ($admin) {

                $admin->name = $request->has('name') ? $request->name : $admin->name;

                $admin->email = $request->has('email') ? $request->email : $admin->email;

                $admin->mobile = $request->has('mobile') ? $request->mobile : $admin->mobile;

                $admin->description = $request->description ? $request->description : '';

                if($request->hasFile('picture')) {

                    if($request->id){

                        Helper::delete_picture($admin->picture, "/uploads/");

                    }

                    $admin->picture = Helper::normal_upload_picture($request->picture);

                }
                    
                if (!$admin->id) {

                    $new_password = $request->password;
                    
                    $admin->password = Hash::make($new_password);

                }

                $admin->token = Helper::generate_token();

                $admin->timezone = $request->timezone;

                $admin->token_expiry = Helper::generate_token_expiry();

                $admin->is_activated = 1;

                if($admin->save()) {

                    return back()->with('flash_success', tr('admin_save_success'));

                } else {

                    return back()->with('flash_error', tr('admin_not_saved'));

                }
                  
            } else {

                return back()->with('flash_error', tr('admin_not_found'));
            }

        }
    
    }


    /**
     * @method sub_admins_create()
     *
     * To create a sub admin only admin can access this option
     * 
     * @created - Shobana Chandrasekar
     *
     * @updated -  
     *
     * @param object $request - -
     *
     * @return response of html page with details
     */
    public function sub_admins_create(Request $request) {

        $model = new Admin();

        return view('admin.sub_admins.create')
                ->with('model', $model)
                ->with('page', 'sub-admins')
                ->with('sub_page', 'sub-create-admins');
    }

    /**
     * @method sub_admins_edit()
     *
     * To edit a sub admin based on subadmin id only  admin can access this option
     * 
     * @created - Shobana Chandrasekar
     *
     * @updated -  
     *
     * @param object $request - sub Admin Id
     *
     * @return response of html page with details
     */
    public function sub_admins_edit(Request $request) {

        $model = Admin::find($request->id);

        if ($model) {

            return view('admin.sub_admins.edit')
                ->with('model', $model)
                ->with('page', 'sub-admins')
                ->with('sub_page', 'sub-create-admins');

        } else {

            return back()->with('flash_error', tr('sub_admin_not_found'));
        }

    }

    /**
     * @method sub_admins_view()
     *
     * To view a sub admin based on sub admin id only admin can access this option
     * 
     * @created - Shobana Chandrasekar
     *
     * @updated -  
     *
     * @param object $request - Sub Admin Id
     *
     * @return response of html page with details
     */
    public function sub_admins_view(Request $request) {

        $model = Admin::find($request->id);

        if ($model) {

            return view('admin.sub_admins.view')
                ->with('model', $model)
                ->with('page', 'sub-admins')
                ->with('sub_page', 'sub-admins-index');

        } else {

            return back()->with('flash_error', tr('sub_admin_not_found'));

        }
    }


    /**
     * @method sub_admins_delete()
     *
     * To delete a sub admin based on sub admin id. only admin can access this option
     * 
     * @created - Shobana Chandrasekar
     *
     * @updated -  
     *
     * @param object $request - Sub Admin Id
     *
     * @return response of html page with details
     */
    public function sub_admins_delete(Request $request) {

        $model = Admin::find($request->id);

        if ($model) {

            if ($model->delete()) {

                return back()->with('flash_success', tr('sub_admin_delete_success'));

            } else {

                return back()->with('flash_error', tr('sub_admin_delete_failure'));

            }

        } else {

            return back()->with('flash_error', tr('sub_admin_not_found'));

        }
    }

    /**
     * @method sub_admins_status()
     *
     * To change the status of the sub admin, based on sub admin id. only admin can access this option
     * 
     * @created - Shobana Chandrasekar
     *
     * @updated -  
     *
     * @param object $request - SubAdmin Id
     *
     * @return response of html page with details
     */
    public function sub_admins_status(Request $request) {

        $model = Admin::find($request->id);

        if ($model) {

            $model->is_activated = $model->is_activated ? ADMiN_DECLINE_STATUS : ADMIN_APPROVE_STATUS;

            if ($model->save()) {

                if ($model->status) {

                    return back()->with('flash_success', tr('sub_admin_approve_success'));

                } else {

                    return back()->with('flash_success', tr('sub_admin_decline_success'));

                }   

            } else {

                return back()->with('flash_error', tr('sub_admin_not_saved'));

            }

        } else {

            return back()->with('flash_error', tr('sub_admin_not_found'));

        }
    }

    /**
     * @method sub_admins_index()
     *
     * To list out subadmins (only admin can access this option)
     * 
     * @created - Shobana Chandrasekar
     *
     * @updated -  
     *
     * @param object $request - 
     *
     * @return response of html page with details
     */
    public function sub_admins_index(Request $request) {

        $data = Admin::orderBy('created_at', 'desc')->paginate($this->paginate_count);

        return view('admin.sub_admins.index')
                ->with('data', $data)
                ->with('page', 'sub-admins')
                ->with('sub_page', 'sub-admins-index');
        
    }

    /**
     * @method sub_admins_save()
     *
     * To save the sub admin details
     * 
     * @created - Shobana Chandrasekar
     *
     * @updated -  
     *
     * @param object $request - Sub Admin Id
     *
     * @return response of html page with details
     */
    public function sub_admins_save(Request $request) {

        $demo_logins = explode(',', Setting::get('demo_users'));

        $validator = Validator::make( $request->all(),array(
                'name' => 'regex:/^[a-zA-Z]*$/|max:100',
                'email' => in_array($request->email, $demo_logins) ?
                    ($request->id ? 'required|email|unique:users,email,'.$request->id.',id' : 'required|email|unique:users,email,NULL,id') : ($request->id ? 'required|email|unique:users,email,'.$request->id.',id' : 'required|email|unique:users,email,NULL,id'),
                'mobile' => 'digits_between:4,16',
                'address' => 'max:300',
                'id' => 'exists:admins,id',
                'picture' => 'mimes:jpeg,jpg,png',
                'description'=>'required|max:255',
                'password' => $request->id ? '' : 'required|min:6|confirmed',
            )
        );
        
        if($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            return back()->with('flash_error', $error_messages);

        } else {

            $admin = $request->id ? Admin::find($request->id) : new Admin;

            if ($admin) {

                $admin->name = $request->has('name') ? $request->name : $admin->name;

                $admin->email = $request->has('email') ? $request->email : $admin->email;

                $admin->mobile = $request->has('mobile') ? $request->mobile : $admin->mobile;

                $admin->description = $request->description ? $request->description : '';

                if($request->hasFile('picture')) {

                    if($request->id){

                        Helper::delete_picture($admin->picture, "/uploads/");

                    }

                    $admin->picture = Helper::normal_upload_picture($request->picture);

                }
                    
                if (!$admin->id) {

                    $new_password = $request->password;
                    
                    $admin->password = Hash::make($new_password);

                }

                $admin->token = Helper::generate_token();

                $admin->timezone = $request->timezone;

                $admin->token_expiry = Helper::generate_token_expiry();

                $admin->is_activated = 1;

                if($admin->save()) {

                    return back()->with('flash_success', tr('sub_admin_save_success'));

                } else {

                    return back()->with('flash_error', tr('sub_admin_not_saved'));

                }
                  
            } else {

                return back()->with('flash_error', tr('sub_admin_not_found'));
            }

        }
    
    }


     /**
     * @method user_subscription_payments_view()
     * 
     * @uses used to list the user_subscription_payments_view
     *
     * @created Ganesh
     *
     * @updated Ganesh
     *
     * @param
     *
     * @return view page
     */
    public function user_subscription_payments_view(Request $request) {
        
        $payments = UserSubscription::where('user_subscriptions.id',$request->user_subscription_id)
                   ->commonResponse()
                   ->first();
                   
        if(!$payments){

            return redirect()->back()->with('flash_error',tr('payment_not_found_error'));
        }

        return view('admin.payments.user-payments-view')
                ->with('payments',$payments)
                ->withPage('payments')
                ->with('sub_page','subscription_payments'); 
       
    }


    /**
     * @method live_video_payments_view()
     * 
     * @uses used to list the live_video_payments_view
     *
     * @created Ganesh
     *
     * @updated Ganesh
     *
     * @param
     *
     * @return view page
     */
    public function live_video_payments_view(Request $request) {

        $payments = LiveVideoPayment::where('live_video_payments.id',$request->video_payment_id)
        ->commonResponse()
        ->first();

        if(!$payments){

            return redirect()->back()->with('flash_error',tr('payment_not_found_error'));

        }

        return view('admin.payments.video-payments-view')
                ->with('payments',$payments)
                ->withPage('payments')
                ->with('sub_page','video_payments'); 
       
       
    }


    /**
     * @method users_bulk_action()
     * 
     * @uses To delete,approve,decline multiple users
     *
     * @created Sakthi
     *
     * @updated 
     *
     * @param 
     *
     * @return success/failure message
     */
    public function users_bulk_action(Request $request) {

        $action_name = $request->action_name ;
        $user_ids = explode(',', $request->selected_users);

        try {

            DB::beginTransaction();

            if($action_name == 'bulk_delete'){

                User::whereIn('id', $user_ids)->chunk(100, function ($users) {

                    foreach ($users as $key => $user_details) {

                        updated_register_count('web');

                        $user_details->delete();
                    } 
                });

                    $message =  tr('admin_users_delete_success');
                    DB::commit();

            }elseif($action_name == 'bulk_approve'){

                $user_details =  User::whereIn('id', $user_ids)->update(['status' => APPROVED]);

                $message =  tr('admin_users_approve_success');

                DB::commit();


            }elseif($action_name == 'bulk_decline'){

                $user_details =  User::whereIn('id', $user_ids)->update(['status' => DECLINED]);

                $message =  tr('admin_users_decline_success');

                DB::commit();

            }

            return back()->with('flash_success',$message)->with('bulk_action','true');

        } catch( Exception $e) {

            DB::rollback();


            return redirect()->back()->with('flash_error',$e->getMessage());
        }


    }

    /**
    * @method vod_bulk_action()
    * 
    * @uses To delete,approve,decline multiple vod
    *
    * @created Sakthi
    *
    * @updated 
    *
    * @param 
    *
    * @return success/failure message
    */
    public function vod_bulk_action(Request $request) {

        $action_name = $request->action_name ;
        $vod_ids = explode(',', $request->selected_vod);


        try {

            DB::beginTransaction();
            if($action_name == 'bulk_delete'){


                VodVideo::whereIn('id', $vod_ids)->delete();

                DB::commit();

                $message =  tr('admin_vod_delete_success');


            }elseif($action_name == 'bulk_approve'){

                $vod_details =  VodVideo::whereIn('id', $vod_ids)->update(['admin_status' => VOD_APPROVED_BY_ADMIN]);

                $message =  tr('admin_vod_approve_success');

                DB::commit();


            }elseif($action_name == 'bulk_decline'){

                $vod_details =  VodVideo::whereIn('id', $vod_ids)->update(['admin_status' => VOD_DECLINED_BY_ADMIN]);

                $message =  tr('admin_vod_decline_success');

                DB::commit();

            }
            return back()->with('flash_success',$message)->with('bulk_action','true');

        } catch( Exception $e) {

            DB::rollback();


            return redirect()->back()->with('flash_error',$e->getMessage());
        }


    }


    /**
    * @method custom_live_videos_bulk_action()
    * 
    * @uses To delete,approve,decline multiple live_tv
    *
    * @created Sakthi
    *
    * @updated 
    *
    * @param 
    *
    * @return success/failure message
    */
    public function custom_live_videos_bulk_action(Request $request) {

        $action_name = $request->action_name ;
        $video_ids = explode(',', $request->selected_live_id);

        try {

            DB::beginTransaction();

            if($action_name == 'bulk_delete'){

                CustomLiveVideo::whereIn('id', $video_ids)->delete();

                DB::commit();

                $message =  tr('admin_live_tv_delete_success');

            }elseif($action_name == 'bulk_approve'){

                CustomLiveVideo::whereIn('id', $video_ids)->update(['status' => APPROVED]);

                $message =  tr('admin_live_tv_approve_success');

                DB::commit();


            }elseif($action_name == 'bulk_decline'){


                CustomLiveVideo::whereIn('id', $video_ids)->update(['status' => DECLINED]);

                $message =  tr('admin_live_tv_decline_success');

                DB::commit();

            }
            
            return back()->with('flash_success',$message)->with('bulk_action','true');


        } catch( Exception $e) {

            DB::rollback();


            return redirect()->back()->with('flash_error',$e->getMessage());
        }

    }



    /**
     * @method live_videos_bulk_action_delete()
     * 
     * @uses To delete multiple live vieos
     *
     * @created Sakthi
     *
     * @updated 
     *
     * @param 
     *
     * @return success/failure message
     */
    public function live_videos_bulk_action_delete(Request $request) {

        $action_name = $request->action_name ;
        $video_ids = explode(',', $request->selected_live_id);

        try {
            
            DB::beginTransaction();

            // start delete function
            if($action_name == 'bulk_delete'){

               LiveVideo::whereIn('id', $video_ids)->where('status',VIDEO_STREAMING_STOPPED)->delete();

               DB::commit();

            }

            return redirect()->back()->with('flash_success',tr('admin_live_videos_delete_success'))->with('bulk_action','true');

        } catch (Exception $e) {

            DB::rollback();

            return back()->with('flash_error',$e->getMessage());
        }

    }

    /**
     * @method live_group_bulk_action_delete()
     * 
     * @uses To delete multiple live groups
     *
     * @created Sakthi
     *
     * @updated 
     *
     * @param 
     *
     * @return success/failure message
     */
    public function live_group_bulk_action_delete(Request $request) {

        $action_name = $request->action_name ;
        $group_ids = explode(',', $request->selected_livegroup_id);

        try {

            DB::beginTransaction();

            // start delete function
            if($action_name == 'bulk_delete'){

                LiveGroup::whereIn('id', $group_ids)->delete();

                DB::commit();

            }
            return redirect()->route('admin.live_groups.index')->with('flash_success',tr('admin_live_groups_delete_success'))->with('bulk_action','true');

        } catch (Exception $e) {

            DB::rollback();

            return back()->with('flash_error',$e->getMessage());
        }

    }


}
