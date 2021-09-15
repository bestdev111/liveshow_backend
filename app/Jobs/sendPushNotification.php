<?php

namespace App\Jobs;

use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Jobs\Job;
use App\Helpers\Helper;
use App\User;
use App\Provider;
use Setting;
use Log;

use App\Repositories\PushNotificationRepository as PushRepo;

use App\Notifications\PushNotification;

class sendPushNotification extends Job implements ShouldQueue {

    use InteractsWithQueue, SerializesModels;

    protected $id;
    protected $push_type;
    protected $page_type;
    protected $title;
    protected $message;
    protected $live_video_id;
    protected $push_data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id, $push_type , $page_type , $title, $message , $live_video_id = "",$push_data) {

        $this->id = $id;
        $this->push_type = $push_type;
        $this->page_type = $page_type;
        $this->title = $title;
        $this->message = $message;
        $this->live_video_id = $live_video_id;
        $this->push_data = $push_data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {

        // Check the user type whether "USER" or "PROVIDER"
        if(Setting::get('push_notification') == NO) {

            Log::info("Push notification disabled by admin");

            return false;
        }

        if(!check_push_notification_configuration_new()) {

            Log::info("Push Notification configuration failed");

            return false;

        }

        if($this->push_type == LIVE_PUSH) {

            // Get the followers list

            // $followers_list = followers_for_notification($this->id);
            $register_ids = \App\Follower::select('followers.id as id' , 'followers.*', 
                    'users.name as name', 'users.email as email', 'users.picture' , 'users.device_token', 'users.device_type')
                ->leftJoin('users' , 'users.id' ,'=' , 'followers.follower')
                ->where('user_id', $this->id)
                ->where('users.device_token' , '!=' , "")
                ->whereIn('users.device_type' ,[DEVICE_ANDROID,DEVICE_IOS])
                ->where('users.push_status' , ON)
                ->pluck('users.device_token')->toArray();
                
            \Notification::send($register_ids, new PushNotification($this->title , $this->message, $this->push_data, $register_ids));

            // if($followers_list->count() > 0) {

            //     foreach ($followers_list as $key => $follower_details) {

            //         PushRepo::push_notification($follower_details->device_token, $this->title, $this->message, $this->push_data, $follower_details->device_type);
                    
            //     }
            // }
        }
            
           
    }
}
