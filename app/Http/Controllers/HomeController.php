<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;

use App\ChatMessage;

use App\User;

use App\Helpers\Helper;

use Setting;

use App\Jobs\sendPushNotification;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('welcome');
    }

    /**
     *
     *
     */

    /**
     * Used to generate index.php file to avoid uploads folder access
     *
     */

    public function generate_index(Request $request) {

        if($request->has('folder')) {

            Helper::generate_index_file($request->folder);

        }

        return response()->json(['success' => true , "message" => tr('successfully')]);

    }

    public function message_save(Request $request) {

        \Log::info("message data".print_r($request->all() , true));

        $validator = \Validator::make($request->all(), [
                "live_video_id" => "required|integer",
                "user_id" => "required|integer",
                "live_video_viewer_id" => "",
                "type" => "required|in:uv,vu",
                "message" => "required",
            ]);

        if($validator->fails()) {
            $error = implode(',', $validator->messages()->all());
            return response()->json(['success' => false , 'error' => $error]);
        }

        ChatMessage::create($request->all());

        return response()->json(['success' => 'true']);
    
    }

    /**
     * To verify the email from user
     *
     */

    public function email_verify(Request $request) {

        \Log::info("USER ID".print_r($request->id , true));

        // Check the request have user ID

        if($request->id) {

            // Check the user record exists

            if($user = User::find($request->id)) {

                // Check the user already verified

                if(!$user->is_verified) {

                    // Check the verification code and expiry of the code

                    $response = Helper::check_email_verification($request->verification_code , $user, $error);

                    \Log::info("EMAIL Verification Response".print_r($response , true));

                    if($response) {

                        $user->is_verified = true;
                        $user->save();

                        $message = tr('email_verified_success');

                        return redirect()->away(Setting::get('ANGULAR_URL'));

                    } else {
                        
                        \Log::info("EMAIL Verification Response NOOOOO".print_r("Hello" , true));

                        return redirect()->away(Setting::get('ANGULAR_URL'));
                    }

                } else {
                    
                    \Log::info("EMAIL Verification Response NOOOOO".print_r("Hello" , true));

                    // Already Verified user - Just login and continue

                    return redirect()->away(Setting::get('ANGULAR_URL'));
                }

            } else {
                
                \Log::info("EMAIL Verification Response NOOOOO".print_r("Hello" , true));

                // User Redord Not Found

                return redirect()->away(Setting::get('ANGULAR_URL'));
            }

        } else {

            $message = tr('something_missing_email_verification');

            return redirect()->away(Setting::get('ANGULAR_URL'));
        }
    
    }

    public function test() {

        $user = User::find(1);
        Helper::check_email_verification("" , $user , $error);

        $email_data['password'] = "HELLo";
        $email_data['user'] = $user;

        return view('emails.user.forgot-password')->with('email_data' , $user)->with('site_url' , url('/'));
    }

    public function send_push(Request $request) {
        $title = "Hello All";

        $message = tr('message');

        $push_data = ['type' => PUSH_SINGLE_VIDEO, 'video_id' => $request->other_id];

        if(Setting::get('push_notification') == NO) {

            Log::info("Push notification disabled by admin");

            return false;
        }

        if(!check_push_notification_configuration_new()) {

            Log::info("Push Notification configuration failed");

            return false;

        }

        $this->dispatch(new sendPushNotification($request->id ? $request->id : 13,LIVE_PUSH,PUSH_SINGLE_VIDEO,$title,$message , $request->other_id ? $request->other_id : 10, $push_data));
    }
}
