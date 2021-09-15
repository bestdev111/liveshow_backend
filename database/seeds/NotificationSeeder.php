<?php

use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //

        DB::table('notification_templates')->delete();
    	DB::table('notification_templates')->insert([
    		[
		        'type' => LIVE_STREAM_STARTED,
		        'subject'=>'Live Stream Started',
		        'content'=>'Started a new live video. Watch it before it ends!',
		        'status'=>1,
		    ],
		    [
		        'type' => USER_FOLLOW,
		        'subject'=>'User following request',
		        'content'=>'New user started following you',
		        'status'=>1,
		    ],
		    [
		        'type' => USER_JOIN_VIDEO,
		        'subject'=>'User joined a video',
		        'content'=>'New user has entered your room..!',
		        'status'=>1,
		    ],
		    [
		        'type' => USER_GROUP_ADD,
		        'subject'=>'You are added in a new group',
		        'content'=>'You are added in a new group',
		        'status'=>1,
		    ],
		]);
    }
}
