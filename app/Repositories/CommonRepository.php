<?php


namespace App\Repositories;

use App\Helpers\Helper;

use Illuminate\Http\Request;

use Validator, Hash, Log, Setting, Exception, DB;

use App\User, App\CustomLiveVideo, App\Language;

class CommonRepository {

	/**
	 * Usage : Register api - validation for the basic register fields 
	 *
	 */

	public static function basic_validation($data = [], &$errors = []) {

		$validator = Validator::make( $data,array(
                'device_type' => 'required|in:'.DEVICE_ANDROID.','.DEVICE_IOS.','.DEVICE_WEB,
                'device_token' => 'required',
                'login_by' => 'required|in:manual,facebook,google',
            )
        );
        
	    if($validator->fails()) {
	        $errors = implode(',', $validator->messages()->all());
	        return false;
	    }

	    return true;

	}

		/**
	 * Usage : Register api - validation for the social register or login 
	 *
	 */

	public static function social_validation($data = [] , &$errors = []) {

		$validator = Validator::make( $data,array(
                'social_unique_id' => 'required',
                'name' => 'required|max:255',
                'email' => 'required|email|max:255',
                'mobile' => 'digits_between:6,13',
                'picture' => '',
                'gender' => 'in:male,female,others',
            )
        );
        
	    if($validator->fails()) {
	        $errors = implode(',', $validator->messages()->all());
	        return false;
	    }

	    return true;

	}

	/**
	 * Usage : Register api - validation for the manual register fields 
	 *
	 */

	public static function manual_validation($data = [] , &$errors = []) {

		$validator = Validator::make( $data,array(
                'name' => 'required|max:255',
                'email' => 'required|email',
                'password' => 'required|min:6|confirmed',

                'mobile' => 'digits_between:6,13',
                'picture' => 'mimes:jpeg,jpg,bmp,png',
            )
        );
        
	    if($validator->fails()) {
	        $errors = implode(',', $validator->messages()->all());
	        return false;
	    }

	    return true;

	}

	/**
	 * Usage : Login api - validation for the manual login fields 
	 *
	 */

	public static function email_validation($data = [] , &$errors = [] , $table = "users") {

		$validator = Validator::make( $data,[
                'email' => 'required|email|exists:'.$table.',email',
            ],
            [
            	'exists' => tr('email_id_not_found')
            ]
        );
        
	    if($validator->fails()) {
	        $errors = implode(',', $validator->messages()->all());
	        return false;
	    }

	    return true;

	}

	/**
	 * Usage : Login api - validation for the manual login fields 
	 *
	 */

	public static function login_validation($data = [] , &$errors = [] , $table = "users") {

		$validator = Validator::make( $data,[
                'email' => 'required|email|exists:'.$table.',email',
                'password' => 'required',
            ]
        );
        
	    if($validator->fails()) {
	        $errors = implode(',', $validator->messages()->all());
	        return false;
	    }

	    return true;

	}

	public static function change_password_validation($data = [] , &$errors = [] , $table = "users") {

		$validator = Validator::make( $data,[
                'password' => 'required|confirmed',
                'old_password' => 'required',
            ]
        );
        
	    if($validator->fails()) {
	        $errors = implode(',', $validator->messages()->all());
	        return false;
	    }

	    return true;

	}

	public static function getUrl($video, $request) {


        $sdp = $video->user_id.'-'.$video->id.'.sdp';

        $device_type = $request->device_type;

        $browser = $request->browser;

        if ($device_type == DEVICE_ANDROID) {

            $url = "rtmp://".Setting::get('cross_platform_url')."/live/".$sdp;

        } else if($device_type == DEVICE_IOS) {

            $url = is_ssl().Setting::get('cross_platform_url')."/live/".$sdp."/playlist.m3u8";

        } else {

            $browser = $browser ? $browser : get_browser();

            if (strpos($browser, 'safari') !== false) {
                
                $url = "http://".Setting::get('cross_platform_url')."/live/".$sdp."/playlist.m3u8";  

            } else {

                $url = "rtmp://".Setting::get('cross_platform_url')."/live/".$sdp;
            }

        }

        return $url;
    }


    public static function rtmpUrl($model) {

    	$RTMP_URL = 'rtmp://'.Setting::get('cross_platform_url').'/live/';

        $url = $RTMP_URL.$model->user_id.'_'.$model->id;

        return $url;
    }


    public static function iosUrl($model) {

    	$sdp = $model->video_url;

    	$url =  is_ssl().Setting::get('cross_platform_url')."/live/".$sdp."/playlist.m3u8";

        return $url;
    }

   public static function webIosUrl($model) {

    	$sdp = $model->user_id.'-'.$model->id.'.sdp';;

    	$url =  is_ssl().Setting::get('cross_platform_url')."/live/".$sdp."/playlist.m3u8";

        return $url;
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
    public static function save_custom_live_video($request) {

        if ($request->id) {

            $validator = Validator::make($request->all(),array(
                    'title' => 'required|max:255',
                    'description' => 'required',
                    'rtmp_video_url'=>'required|max:255',
                    'hls_video_url'=>'required|max:255',
                    'image' => 'mimes:jpeg,jpg,png',
                    'user_id'=>'required|exists:users,id,is_content_creator,'.CREATOR_STATUS
                )
            );

         } else {

             $validator = Validator::make($request->all(),array(
                'title' => 'max:255|required',
                'description' => 'required',
                'rtmp_video_url'=>'required|max:255',
                'hls_video_url'=>'required|max:255',
                'image' => 'required|mimes:jpeg,jpg,png',
                'user_id'=>'required|exists:users,id,is_content_creator,'.CREATOR_STATUS
                )
            );

         }
        
        if($validator->fails()) {

            $error_messages = implode(',', $validator->messages()->all());

            $response_array = ['success'=>false, 'message'=>$error_messages];

        } else {
            
            $model = ($request->id) ? CustomLiveVideo::find($request->id) : new CustomLiveVideo;

            $model->user_id = $request->user_id;
            
            $model->title = $request->has('title') ? $request->title : $model->title;

            $model->description = $request->has('description') ? $request->description : $model->description;

            $model->rtmp_video_url = $request->has('rtmp_video_url') ? $request->rtmp_video_url : $model->rtmp_video_url;

            $model->hls_video_url = $request->has('hls_video_url') ? $request->hls_video_url : $model->hls_video_url;


            if($request->hasFile('image')) {

                if($request->id) {

                    Helper::storage_delete_file($model->image, LIVETV_IMAGE_PATH); 
                }

                $model->image = Helper::storage_upload_file($request->image, LIVETV_IMAGE_PATH);

            }
                
            $model->status = DEFAULT_TRUE;

            if ($model->save()) {

                $response_array = ['success'=>true, 'message'=> ($request->id) ? tr('live_custom_video_update_success') : tr('live_custom_video_create_success'), 'data' => $model];

            } else {

                $response_array = ['success'=>false, 'message'=>tr('something_error')];

            }
            
        }

        return response()->json($response_array);

    }



    public static function languages_save($request) {

        try {
            
            DB::beginTransaction();

            $validator = Validator::make($request->all(),[
                    // 'folder_name' => $request->language_id ? 'required|max:4|unique:languages,folder_name,'.$request->language_id : 'required|max:4|unique:languages,folder_name',
                    // 'language'=> $request->language_id ? 'required|max:4|unique:languages,language,'.$request->language_id : 'required|max:4|unique:languages,language',
                    'folder_name' => 'required|max:4',
                    'language'=>'required|max:64',
                    'auth_file'=> !($request->language_id) ? 'required' : '',
                    'messages_file'=>!($request->language_id) ? 'required' : '',
                    'pagination_file'=>!($request->language_id) ? 'required' : '',
                    'passwords_file'=>!($request->language_id) ? 'required' : '',
                    'validation_file'=>!($request->language_id) ? 'required' : '',
            ]);
            
            if($validator->fails()) {

                $error = implode(',', $validator->messages()->all());

                throw new Exception($error, 101);
            } 

            $language_details = ($request->language_id != '') ? Language::find($request->language_id) : new Language;

            $lang = ($request->language_id != '') ? $language_details->folder_name : '';

            $language_details->folder_name = $request->folder_name;

            $language_details->language = $request->language;

            $language_details->status = APPROVED;

            if ($request->hasFile('auth_file')) {

                 // Read File Length

                $originallength = Helper::readFileLength(base_path().'/resources/lang/en/auth.php');

                $length = Helper::readFileLength($_FILES['auth_file']['tmp_name']);

                if ($originallength != $length) {

                    throw new Exception(Helper::error_message(175), 175);
                }

                if ($language_details->id != '') {

                    $boolean = ($lang != $request->folder_name) ? DEFAULT_TRUE : DEFAULT_FALSE;

                    Helper::delete_language_files($lang, $boolean, 'auth.php');
                }

                Helper::upload_language_file($language_details->folder_name, $request->auth_file, 'auth.php');

            }

            if ($request->hasFile('messages_file')) {

                 // Read File Length

                $originallength = Helper::readFileLength(base_path().'/resources/lang/en/messages.php');

                $length = Helper::readFileLength($_FILES['messages_file']['tmp_name']);

                if ($originallength != $length) {

                    throw new Exception(Helper::error_message(175), 175);
                }

                if ($language_details->id != '') {

                    $boolean = ($lang != $request->folder_name) ? DEFAULT_TRUE : DEFAULT_FALSE;

                    Helper::delete_language_files($lang, $boolean, 'messages.php');
                }

                Helper::upload_language_file($language_details->folder_name, $request->messages_file, 'messages.php');

            }

            if ($request->hasFile('pagination_file')) {

                 // Read File Length

                $originallength = Helper::readFileLength(base_path().'/resources/lang/en/pagination.php');

                $length = Helper::readFileLength($_FILES['pagination_file']['tmp_name']);

                if ($originallength != $length) {

                    throw new Exception(Helper::error_message(175), 175);
                }

                if ($language_details->id != '') {

                    $boolean = ($lang != $request->folder_name) ? DEFAULT_TRUE : DEFAULT_FALSE;

                    Helper::delete_language_files($lang, $boolean, 'pagination.php');
                }

                Helper::upload_language_file($language_details->folder_name, $request->pagination_file, 'pagination.php');

            }


            if ($request->hasFile('passwords_file')) {

                 // Read File Length

                $originallength = Helper::readFileLength(base_path().'/resources/lang/en/passwords.php');

                $length = Helper::readFileLength($_FILES['passwords_file']['tmp_name']);

                if ($originallength != $length) {
                    
                    throw new Exception(Helper::error_message(175), 175);

                }

                if ($language_details->id != '') {

                    $boolean = ($lang != $request->folder_name) ? DEFAULT_TRUE : DEFAULT_FALSE;

                    Helper::delete_language_files($lang, $boolean , 'passwords.php');
                }

                Helper::upload_language_file($language_details->folder_name, $request->passwords_file, 'passwords.php');

            }

            if($request->hasFile('validation_file')) {

                // Read File Length

                $originallength = Helper::readFileLength(base_path().'/resources/lang/en/validation.php');

                $length = Helper::readFileLength($_FILES['validation_file']['tmp_name']);

                if ($originallength != $length) {
                    
                    throw new Exception(Helper::error_message(175), 175);
                    
                }

                if ($language_details->id != '') {
                    $boolean = ($lang != $request->folder_name) ? DEFAULT_TRUE : DEFAULT_FALSE;

                    Helper::delete_language_files($lang, $boolean, 'validation.php');
                }

                Helper::upload_language_file($language_details->folder_name, $request->validation_file, 'validation.php');

            } 

            if ($request->id) {
                if($lang != $request->folder_name)  {
                    $current_path=base_path('resources/lang/'.$lang);
                    $new_path=base_path('resources/lang/'.$request->folder_name);
                    rename($current_path,$new_path);
                }
            }

            $language_details->save();

            if($language_details) {
                
                DB::commit();

                $response_array = ['success' => true, 'message'=> $request->id != '' ? tr('language_update_success') : tr('language_create_success')];
           
            } else {
                
                throw new Exception(tr('something_error'), 101);
            }
            
        } catch (Exception $e) {
            
            DB::rollback();

            $error = $e->getMessage();
            
            $code = $e->getCode();
            
            $response_array = ['success' => false , 'error' => $e->getMessage(), 'code' => $e->getCode()];

        }

        return $response_array;
    }


}