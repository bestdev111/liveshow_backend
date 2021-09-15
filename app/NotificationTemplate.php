<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    //

    public static function getRawContent($type, $user) {

    	$model = self::where('type', $type)
                                        ->where('status', DEFAULT_TRUE)
                                        ->first();

        \Log::info("Model Obj".print_r($model, true));

        if ($model) {

            $subject = $model->subject;

            $user_replaced = ($user) ? str_replace('%<$user>%',$user->name, $model->content) : $model->content;

            $model->content = $user_replaced;

            return $model->content;

        }

        return "";
    }
}
