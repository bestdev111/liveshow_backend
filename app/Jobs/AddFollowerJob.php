<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\User, App\UserNotification, App\Follower, App\NotificationTemplate;

use App\Helpers\Helper;

use Log;

use App\Notifications\PushNotification;

class AddFollowerJob extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $logged_in_user_id;
    protected $live_video_id;
    protected $follower_id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($logged_in_user_id, $live_video_id, $follower_id)
    {
        $this->logged_in_user_id = $logged_in_user_id;
        $this->live_video_id = $live_video_id;
        $this->follower_id = $follower_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        
        try {

            $user_details = User::find($this->logged_in_user_id);

            $following_user = User::find($this->follower_id);

            $notification = NotificationTemplate::getRawContent(USER_FOLLOW, $user_details);

            $content = $notification ? $notification : USER_FOLLOW;

            UserNotification::save_notification($this->follower_id, $content, $this->logged_in_user_id, USER_FOLLOW , $this->logged_in_user_id);

            if (env('FCM_SENDER_ID') && $following_user->push_status == YES && ($following_user->device_token != '')) {
                
                \Notification::send($this->follower_id, new PushNotification(tr('following_title') , tr('following_message'), tr('following_message'), $this->follower_id));
            }

            $email_data['name'] = $following_user->name;

            $email_data['follower'] = $user_details->name;

            $email_data['subject'] = $following_user->name.' '.tr('following_title');

            $email_data['page'] = "emails.user.add_follower";

            $email_data['email'] = $following_user->email;

            dispatch(new \App\Jobs\SendEmailJob($email_data));
            
        } catch (Exception $e) {

            Log::info("Notification send Error".print_r($e->getMessage(), true));
            
        }

    }
}
