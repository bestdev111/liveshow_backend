<?php

namespace App\Jobs;

use App\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\User, App\UserNotification, App\Follower, App\NotificationTemplate;

use App\Helpers\Helper;

use Log;

class LiveVideoNotificationToFollower extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $logged_in_user_id;
    protected $live_video_id;
    protected $live_group_id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($logged_in_user_id, $live_video_id, $live_group_id = 0)
    {
        $this->logged_in_user_id = $logged_in_user_id;
        $this->live_video_id = $live_video_id;
        $this->live_group_id = $live_group_id;
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

            $notification = NotificationTemplate::getRawContent(LIVE_STREAM_STARTED, $user_details);

            $content = $notification ? $notification : LIVE_STREAM_STARTED;

            if($this->live_group_id) {

                $base_query = \App\LiveGroupMember::where('live_group_id', $this->live_group_id)->leftJoin('users' , 'users.id' ,'=' , 'live_group_members.member_id')
                        ->where('users.status', APPROVED)
                        ->where('users.is_verified', YES);

                $base_query->chunk(30, function($members) use ($user_details, $content) {

                    foreach ($members as $key => $value) {

                        $email_data['name'] = $value->name;

                        $email_data['streamer'] = $user_details->name;

                        $email_data['subject'] = $user_details->name.' '.tr('new_video_streaming');

                        $email_data['page'] = "emails.user.notification";

                        $email_data['email'] = $value->email;

                        dispatch(new \App\Jobs\SendEmailJob($email_data));

                        UserNotification::save_notification($value->member_id, $content, $this->live_video_id, LIVE_STREAM_STARTED, $this->logged_in_user_id);

                    }
                });

            } else {

                $base_query = Follower::select('user_id as id',
                                    'users.name as name', 
                                    'users.email as email', 
                                    'users.picture',
                                    'users.description' ,
                                    'followers.follower as follower_id' ,
                                    'followers.created_at as created_at'
                                   )
                            ->leftJoin('users' , 'users.id' ,'=' , 'followers.follower')
                            ->where('user_id', $this->logged_in_user_id)
                            ->where('users.status', APPROVED)
                            ->where('users.is_verified', YES)
                            ->orderBy('created_at', 'desc');

                $base_query->chunk(30, function($followers) use ($user_details, $content) {

                    foreach ($followers as $key => $value) {

                        $email_data['name'] = $value->name;

                        $email_data['streamer'] = $user_details->name;

                        $email_data['subject'] = $user_details->name.' '.tr('new_video_streaming');

                        $email_data['page'] = "emails.user.notification";

                        $email_data['email'] = $value->email;

                        dispatch(new \App\Jobs\SendEmailJob($email_data));

                        UserNotification::save_notification($value->follower_id, $content, $this->live_video_id, LIVE_STREAM_STARTED, $this->logged_in_user_id);

                    }
                });
            }
            
        } catch (Exception $e) {

            Log::info("Notification send Error".print_r($e->getMessage(), true));
            
        }

    }
}
