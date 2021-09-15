<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\UserSubscription;

use App\Subscription;

use App\User;

use Auth;

use Validator;

use App\LiveVideo;

use App\Follower;

use App\Helpers\EnvEditorHelper;

use App\Repositories\CommonRepository as CommonRepo;

use App\Repositories\UserRepository as UserRepo;

use App\Helpers\Helper;

use App\Viewer;

use App\Settings;

use DB;

use Hash;

use App\BlockList;

use App\ChatMessage;

use App\Page;

use App\LiveVideoPayment;

use Setting;

use DateTime;

use DateTimeZone;

class UserController extends Controller
{
    //
    protected $UserAPI;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(UserApiController $API)
    {

        $this->UserAPI = $API;
        
        $this->middleware('UserApiVal', ['except'=>[
                'video', 
                'userDetails', 
                'allPages', 
                'getPage',
                'searchall',
                'appSettings', 
                'settings', 
                'check_social', 
                'connectStream', 
                'disConnectStream', 
                'deleteStream', 
                'get_live_url', 
                'delete_video',
                'live_streaming']]);
    }


    public function video($mid, Request $request) {

        // Load Model
        $model = LiveVideo::where('live_videos.id',$mid)
                ->select('live_videos.id as id' , 'live_videos.*', 'users.name as name', 'users.email as email', 'live_videos.created_at as created_date')
                ->leftJoin('users' , 'users.id' ,'=' , 'live_videos.user_id')
                ->first();


        $model = $model ? (($model->status == DEFAULT_TRUE) ? '' : $model) : '';



        if ($model) {

            /*if ($model->start_time) {

                $now = getUserTime(date('H:i:s'), ($model->user) ? $model->user->timezone : null , "H:i:s");
                $model->no_of_minutes = getMinutesBetweenTime($model->start_time, $now);

            }*/
            
            $model->created_date = convertTimeToUSERzone($model->created_date, ($model->user) ? $model->user->timezone : null, 'd-m-Y h:i A');

            if ($model->unique_id == 'sample') {

                

            } else {

                if ($model->video_url) {

                    $model->video_url = convertAndriodToOtherUrl($model);

                }

            }

  


        }

        


       /* if ($request->has('reload')) {

            if($request->reload == 1) {

                $myfile = fopen("storage.sdp", "w") or die("Unable to open file!");
                fwrite($myfile, $data);
                fclose($myfile);
            }
        }*/

        /*$model->created_at = getUserTime($model->created_at, $model->user ? $model->user->timezone : null, "d-m-Y H:i");*/

        return response()->json($model,200);

    }

    public function close_streaming(Request $request) {

        $model = LiveVideo::find($request->video_id);

        if ($model) {

            $model->status = DEFAULT_TRUE;

            if($request->id) {

                if ($model->user_id == $request->id) {

                    $model->end_time = getUserTime(date('H:i:s'), ($model->user) ? $model->user->timezone : '', "H:i:s");

                    $model->no_of_minutes = getMinutesBetweenTime($model->start_time, $model->end_time);

                    $message =  tr('streaming_stopped_success');

                    //$route = route('user.channel', ['id'=>$model->channel_id]);

                } else {

                    $message = tr('no_more_video_available');

                    // $route = route('user.live_videos');
                }

            } else {

                $message = tr('no_more_video_available');

               // $route = route('user.live_videos');

            }

            if ($model->save()) {

                if ($request->id) {

                    if ($model->user_id == $request->id) {  

                        if (Setting::get('wowza_server_url')) {

                            $this->disConnectStream($model->user->id.'-'.$model->id);

                        }

                    }

                }

            }
        
        } else {

            $message = tr('no_more_video_available');

        }

        return response()->json($message);
        // return redirect($route)->with('flash_success',$message);
    
    }

    public function appSettings($mid, Request $request) {

        $userModel = null;

        $videoPayment = null;

        if ($mid) {

            $video = LiveVideo::find($mid);

            if ($video->status) {


            } else {

                $userModel = User::find($request->id);

                // video payment 

                $videoPayment = 1;

                if ($video->payment_status == 1) {

                    $videoPayment = LiveVideoPayment::where('live_video_id', $mid)
                        ->where('live_video_viewer_id', $request->id)
                        ->where('status',DEFAULT_TRUE)->first();

                }

                /*$save_viewers = DEFAULT_TRUE;

                if ($video->amount > 0 && !$videoPayment) {

                    $save_viewers = DEFAULT_FALSE;

                }

                if($save_viewers) {

                    // Load Viewers model

                    $model = Viewer::where('video_id', $mid)->where('user_id', $request->id)->first();

                    if(!$model) {

                        $model = new Viewer;

                        $model->video_id = $mid;

                        $model->user_id = $request->id;

                    }

                    $model->count = ($model->count) ? $model->count + 1 : 1;

                    $model->save();

                    if ($model) {

                        $model->getVideo->viewer_cnt += 1;

                        $model->getVideo->save();

                    }
                
                } */
            }


            

            $appSettings = json_encode([
                    'SOCKET_URL' => Setting::get('SOCKET_URL'),
                    'CHAT_ROOM_ID' => isset($mid) ? $mid : null,
                    'BASE_URL' => Setting::get('BASE_URL'),
                    'TURN_CONFIG' => [],
                    'TOKEN' => null,
                    'USER' => null,
                    'USER_PICTURE'=>($userModel) ? $userModel->chat_picture : '',
                    'NAME'=>($userModel) ? $userModel->name : '',
                    'CLASS'=>'right',
                    'VIDEO_PAYMENT'=>($videoPayment) ? $videoPayment : '',
            ]);

            if (isset($userModel)) {

                if($video->user_id == $userModel->id) {

                     $appSettings = json_encode([
                                            'SOCKET_URL' => Setting::get('SOCKET_URL'),
                                            'CHAT_ROOM_ID' => isset($mid) ? $mid : null,
                                            'BASE_URL' => Setting::get('BASE_URL'),
                                            'TURN_CONFIG' => [],
                                            'TOKEN' => $request->token,
                                            'USER_PICTURE'=>($userModel) ? $userModel->chat_picture : '',
                                            'NAME'=>($userModel) ? $userModel->name : '',
                                            'CLASS'=>'left',
                                            'USER' => ['id' => $request->id, 'role' => "model"],
                                            'VIDEO_PAYMENT'=>1,
                                        ]);

                }

            }

            // dd($appSettings);

            return response()->json(['success'=>true, 'appSettings'=>$appSettings], 200);

        } else {

            return response()->json(['success'=>false, 'error_messages'=>tr('streaming_stopped_success')], 200);

        }
    
    }

    public function check_subscription_plan(Request $request) {

        $user = User::find($request->id);

        if ($user) {
           
           if ($user->user_type == 1) {

                return response()->json(['success'=>true, 'data'=>$user], 200);

           }

        } 

        return response()->json(['success' => false , 'error_messages' => Helper::error_message(001) , 'error_code' => 001], 200);

    }

    public function userDetails(Request $request) {

        $model = User::find($request->id);

        return response()->json($model,200);
    
    }

    public function setCaptureImage(Request $req, $roomId) {

        $data = explode(',', $req->get('base64'));

        if ($data[1] != '') {

            file_put_contents(join(DIRECTORY_SEPARATOR, [public_path(), 'uploads', 'rooms', $roomId . '.png']), base64_decode($data[1]));

            $model = LiveVideo::find($roomId);
            $model->snapshot = Helper::web_url()."/uploads/rooms/".$roomId . '.png';
            $model->save();

            if ($model->save()) {

                return response()->json(true,200);

            } else {

                return response()->json(false,200);

            }
        }
         
    }

    public function getChatMessages($mid, Request $request) {

        $model = ChatMessage::where('live_video_id', $mid)->get();

        $messages = [];

        foreach ($model as $key => $value) {
            
            $messages[] = [
                'id' => $value->id, 
                'user_id' => ($value->getUser)? $value->user_id : $value->live_video_viewer_id, 
                'name' => ($value->getUser) ? $value->getUser->name : $value->getViewUser->name,
                'picture'=> ($value->getUser) ? $value->getUser->chat_picture : $value->getViewUser->chat_picture ,
                'live_video_id'=>$value->live_video_id, 
                'message'=>$value->message];

        }

        return response()->json($messages,200);

    }

    public function allPages() {

        $all_pages = Page::select('id', 'type', 'title', 'heading')->get();

        $all_pages = count($all_pages) > 0 ? $all_pages->chunk(4) : [];

        return response()->json($all_pages, 200);

    }

    public function getPage($id) {

        $page = Page::where('id', $id)->first();

        return response()->json($page, 200);

    }

    public function searchall(Request $request) {

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
            $response_array = array('success' => false, 'error' => Helper::get_error_message(101), 'error_code' => 101, 'error_messages'=>$error_messages);

            return false;
        
        } else {

            $q = $request->term;

            \Session::set('user_search_key' , $q);

            $items = array();
            
            $results = Helper::search_user($request->id, $q, 0, 8);

            if($results) {

                foreach ($results as $i => $key) {

                    // Blocked Users
                    $blockedUser = BlockList::where('user_id', $request->id)
                            ->where('block_user_id', $key->id)->first();
                            
                    if (!$blockedUser) {

                        $check = $i+1;

                        if($check <=10 ) {
         
                            array_push($items,$key->name);

                        } if($check == 10 ) {

                            array_push($items,"View All" );
                        }

                    }
                
                }

            }

            return response()->json($items);
        }   
    
    }

    public function zero_plan(Request $request) {

        if ($request->plan_id) {

            // Load model
            $plan = Subscription::find($request->plan_id);

            // save video payment for onetime

            $model = new UserSubscription;

            $model->subscription_id = $request->plan_id;

            $model->user_id = $request->id;

            $model->payment_id = "Free Plan";

            $model->payment_mode = "Free Plan";

            $model->amount = 0;

            $model->expiry_date = date('Y-m-d H:i:s',strtotime("+{$plan->plan} months"));

            $model->status =  DEFAULT_TRUE;

            $model->save();

            if ($model) {

                $model->user->user_type = DEFAULT_TRUE;

                $model->user->one_time_subscription = DEFAULT_TRUE;

                $model->user->amount_paid += 0;

                $model->user->expiry_date = $model->expiry_date;

                $model->user->no_of_days = 0;

                $model->user->save();

                $response_array = ['success' => true , 'model' => $model];
            } else {

                $response_array = ['success' => false , 'error_messages' => Helper::error_message(146) , 'error_code' => 146];

            }

        } else {

            $response_array = ['success' => false , 'error_messages' => Helper::error_message(146) , 'error_code' => 146];

        }

        return response()->json($response_array, 200);

    }

    public function settings() {

        $settings = Settings::get();

        $home_bg = Setting::get('home_bg_image');

        $status = false;

        if ($home_bg) {

            $extension = pathinfo($home_bg)['extension'];

            if ($extension == 'jpg' || $extension == 'png' || $extension == 'jpeg') {

                $status = true;

            }

        }

       // $pathinfo  = $home_bg ? pathinfo($home_bg)['extension'] : '';

        $data = ['settings'=>$settings, 'pathinfo'=>$status];

        return response()->json($data, 200); 
    
    }

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

    public function delete_video($id, $user_id) {

        // Load Model
        $model = LiveVideo::find($id);

        if ($model) {

            if ($model->user_id == $user_id) {

                if ($model->is_streaming) {

                    $model->status = DEFAULT_TRUE;

                    $model->end_time = getUserTime(date('H:i:s'), ($model->user) ? $model->user->timezone : '', "H:i:s");

                    // $model->no_of_

                    if ($model->save()) {

                        if (Setting::get('kurento_socket_url')) {

                            $this->disConnectStream($model->user->id.'-'.$model->id);

                        }

                    } else {

                        $response_array = ['success'=>false, 'error_messages'=>tr('went_wrong')];

                    }

                    $response_array = ['success'=>true];

                }

            } else {

                $response_array = ['success'=>false, 'error_messages'=> tr('not_authorized_person')];

            }
            
        } else {

            $response_array = ['success'=>false, 'error_messages'=> tr('no_live_video_present')];

        }

        return response()->json($response_array);

    }
}



