<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class UserNotification extends Model
{

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCommonResponse($query) {

        return $query->select('user_notifications.id as user_notification_id', 
                            'user_notifications.notification', 
                            'user_notifications.user_id as user_id',
                            'users.picture as user_picture',
                            'users.name as user_name',
                            'user_notifications.type',
                            'user_notifications.link_id',
                            'user_notifications.status',
                            'user_notifications.created_at',
                            'user_notifications.updated_at'
                        );
    
    }

    public static function save_notification($user_id, $content, $link_id, $type = "" , $notifier_user_id) {

    	$model = new \App\UserNotification;

    	$model->user_id = $user_id;

    	$model->notification = $content;

        $model->type = $type;

        $model->link_id = $link_id;

        $model->notifier_user_id = $notifier_user_id;

    	$model->status = 0;

    	$model->save();

    }

    public function user() {
        return $this->belongsTo('App\User');
    }
}
