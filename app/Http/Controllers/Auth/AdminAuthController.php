<?php

namespace App\Http\Controllers\Auth;

use App\Admin;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

use DB, Setting, Hash, Validator, Exception;

use Carbon\Carbon;

class AdminAuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Registration & Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users, as well as the
    | authentication of existing users. By default, this controller uses
    | a simple trait to add these behaviors. Why don't you explore it?
    |
    */

    use AuthenticatesUsers;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest:admin', ['except' => ['logout','reset_password']]);
    }

    /**
     * Show the application’s login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    protected function guard() {

        return \Auth::guard('admin');

    }

    protected $registerView = 'admin.auth.register';

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:admins',
            'password' => 'required|min:6|confirmed',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        return Admin::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);
    }

    protected function authenticated(Request $request, Admin $admin){
        
        if(\Auth::guard('admin')->check()) {

            if($admin = Admin::find(\Auth::guard('admin')->user()->id)) {

                $admin->timezone = $request->has('timezone') ? $request->timezone : '';

                $admin->save();
            }   
        };

        $admin_name = $admin->name??'';

       return redirect()->route('admin.dashboard')->with('flash_success',ucfirst($admin_name)."  ".tr('login_success'));
    }

    public function login(Request $request) {

        // Validate the form data
        $this->validate($request, [
            'email'   => 'required|email',
            'password' => 'required|min:5'
         ]);
      
        // Attempt to log the user in
        if (\Auth::guard('admin')->attempt(['email' => $request->email, 'password' => $request->password], $request->remember)) {

            if((\Auth::guard('admin')->user()->is_sub_admin == YES) && (\Auth::guard('admin')->user()->status) == DECLINED) {

                \Session::flash('flash_error', tr('sub_admin_account_decline_note'));
                
                \Auth::guard('admin')->logout();

                return redirect()->route('admin.login');
            }

            $admin = Admin::find(\Auth::guard('admin')->user()->id);

            $admin->timezone = $request->has('timezone') ? $request->timezone : '';
            
            $admin->save();
            
            $admin_name = \Auth::guard('admin')->user()->name ?? '';
            // if successful, then redirect to their intended location
            return redirect()->intended(route('admin.dashboard'))->with('flash_success',ucfirst($admin_name)." ".tr('login_success'));

        }
        
        // if unsuccessful, then redirect back to the login with the form data
        
        return redirect()->back()->withInput($request->only('email', 'remember'))->with('flash_error', tr('username_password_not_match'));
    }


    public function showLinkRequestForm() {
        
        try {

            $is_email_configured = YES;

            if(!envfile('MAIL_USERNAME') || !envfile('MAIL_PASSWORD') || !envfile('MAIL_FROM_ADDRESS') || !envfile('MAIL_FROM_NAME')) {

                $is_email_configured = NO;

                // throw new Exception(tr('email_not_configured'), 101);
                
            }

            return view('admin.auth.forgot')->with('is_email_configured', $is_email_configured);

        } catch(Exception $e){ 

            return redirect()->route('admin.login')->with('flash_error', $e->getMessage());

        } 
    }

    public function forgot_password_update(Request $request){

        try {
    
            DB::beginTransaction();
    
            // Check email configuration and email notification enabled by admin
    
            if(Setting::get('email_verify_control') != YES ) {
    
                throw new Exception(tr('email_not_configured'), 101);
                
            }
            
            $validator = Validator::make( $request->all(), [
                'email' => 'required|email|max:255|exists:admins',
            ]);
    
            if($validator->fails()) {
    
                $error = implode(',', $validator->messages()->all());
    
                throw new Exception($error, 101);
            }
    
            $admin = \App\Admin::where('email' , $request->email)->first();
    
            if(!$admin) {
    
                throw new Exception(api_error(1002), 1002);
            }
    
            
            $token = app('auth.password.broker')->createToken($admin);
    
            \App\PasswordReset::where('email', $admin->email)->delete();
    
            \App\PasswordReset::insert([
                'email'=>$admin->email,
                'token'=>$token,
                'created_at'=>Carbon::now()
            ]);
    
            $email_data['subject'] = tr('reset_password_title' , Setting::get('site_name'));
    
            $email_data['email']  = $admin->email;
    
            $email_data['name']  = $admin->name;
    
            $email_data['user']  = $admin;
    
            $email_data['page'] = "emails.admin_reset_password";
    
            $email_data['url'] = url('/')."/admin/reset/password?token=".$token;
            
            $this->dispatch(new \App\Jobs\SendEmailJob($email_data));
    
            DB::commit();
    
            return redirect()->back()->with('flash_success',api_success(102)); 
    
    
        } catch(Exception $e) {
    
            DB::rollback();
    
            return redirect()->back()->withInput()->with('flash_error', $e->getMessage());
    
        }
       }


       /**
     * @method reset_password
     *
     * @uses return view to reset password
     *
     * @created Ganesh
     *
     * @updated 
     *
     * @param object 
     * 
     * @return response return view page
     *
     **/

    public function reset_password() {

        \Auth::guard('admin')->logout();
        
        return view('admin.auth.reset-password');

    }


    /**
     * @method reset_password_update()
     *
     * @uses To reset the password
     *
     * @created Ganesh
     *
     * @updated Ganesh
     *
     * @param object $request - Email id
     *
     * @return send mail to the valid store
     */
    
    public function reset_password_update(Request $request) {

        try {


            $validator = Validator::make( $request->all(), [
                'password' => 'required|confirmed|min:6',
                'password_confirmation'=>'required',
                'reset_token' => 'required|string'
            ]);
    
            if($validator->fails()) {
    
                $error = implode(',', $validator->messages()->all());
    
                throw new Exception($error, 101);
            }

            DB::beginTransaction();

            $password_reset = \App\PasswordReset::where('token', $request->reset_token)->first();

            if(!$password_reset){

                throw new Exception(tr('invalid_token'), 101);
            }
            
            $admin = \App\Admin::where('email', $password_reset->email)->first();

            $admin->password = \Hash::make($request->password);

            $admin->save();

            \App\PasswordReset::where('email', $admin->email) ->delete();

            DB::commit();

            // if successful, then redirect to their intended location
            return redirect()->route('admin.login')->with(['profile'=>$admin, 'flash_success'=>api_success(104)]); 

        } catch(Exception $e) {

             DB::rollback();

            return redirect()->back()->withInput()->with('flash_error', $e->getMessage());
        }


   }


    public function logout() {

        \Auth::guard('admin')->logout();
        
        return redirect()->route('admin.login')->with('flash_success',tr('logout_success'));
    }
}
