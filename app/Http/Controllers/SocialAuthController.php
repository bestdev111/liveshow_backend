<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\User;

use App\Helpers\Helper;

use App\Repositories\UserRepository as UserRepo;

use App\Repositories\CommonRepository as CommonRepo;

use Socialite;

use Hash;

use Log;

use Setting;

use Session;

class SocialAuthController extends Controller {

    public function redirect(Request $request)
    {
    	Log::info("redirect ".$request->user_type);

    	Session::put('login_type', $request->user_type);

        return Socialite::driver($request->provider)->redirect();
    }

    public function callback(Request $request ,$provider)
	{

		if($provider == "twitter") {
    		
    		if($request->has('denied')) {
		    	
		    	return redirect('/')->with('flash_error' , tr('permission_denied'));

    		}

    	} else {

	    	if(!$request->has('code') || $request->has('denied')) {
			    return redirect('/')->with('flash_error' , tr('permission_denied'));
			}

		}

		Log::info("Login By".$provider);

		$user_type = Session::get('login_type');

		$social_user = \Socialite::driver($provider)->user();

		Log::info('Social Login Response'.print_r($social_user, true));

		if($social_user) {

			$user = new User;

			$check_user;

			$user->email = "social".uniqid()."@streamnow.com";

			// Check the social login has email 

			$new = 0;

			if($social_user->email) {

				// Check the record exists

				$check_user = User::where('email',$social_user->email)->first();

				if(!$check_user) {
					$user->email = $social_user->email;
				}

				$new = 1;

			} else {

				// Check social unique ID Already exists
				
				$check_user = User::where('social_unique_id' , $social_user->id)->first();

				if($social_user->email && !User::where('email',$social_user->email)->first()) {

					$user->email = $social_user->email;

				}
			
			}

			if($check_user) {

				$user = $check_user;

				$is_content_creator = $user_type == CREATOR ? CREATOR_STATUS : VIEWER_STATUS;

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

	                Log::info($message);
	               //  throw new Exception($message);

	                Session::forget('login_type');

	                return redirect()->away(Setting::get('ANGULAR_URL')."choose-login?error=".$user->is_content_creator);
	                
	            }


			} else {

				$is_content_creator = $user_type == CREATOR ? CREATOR_STATUS : VIEWER_STATUS;

				$user->is_content_creator = $is_content_creator;

			}

			$user->social_unique_id = $social_user->id;

			$user->login_by = $provider;

			$user->name = $social_user->name;

			$user->unique_id = uniqid($social_user->name);

			$picture = "";

			if(in_array($provider, array('facebook','twitter'))) {

				if($social_user->avatar_original) {
					$picture = $social_user->avatar_original;
				}
			}

			Log::info("user_type ".$request->user_type);


			if($new) {

				$user->name = $user->name ? $user->name : "Dummy";

				$user->picture = $picture ? $picture : asset('images/default-profile.jpg');

				$user->status = 1;

				$user->payment_mode = 'cod';

				// $user->password = Hash::make($social_user->id);

				$user->register_type = "user";

                $user->chat_picture = $user->picture;

                $user->cover = asset('images/cover.jpg');
				
			} else {

				$user->picture = $picture ? $picture : $user->picture;

                $user->chat_picture = $user->picture;

                $user->cover = asset('images/cover.jpg');
				
			}




			$user->token_expiry = Helper::generate_token_expiry();   

			$user->is_verified = 1; 
    			
			$user->login_status = DEFAULT_TRUE;

			$user->save();

			Session::forget('login_type');

	    	return redirect()->away(Setting::get('ANGULAR_URL')."social/login?id=".$user->id);

	    } else {

	    	Session::forget('login_type');

	    	return redirect()->away(Setting::get('ANGULAR_URL')."choose-login");

	    }
	}
}
