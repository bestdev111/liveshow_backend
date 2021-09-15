<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Viewer extends Model
{
    //
    /**
     * Load live video using relation model
     */
    public function getVideo()
    {
        return $this->hasOne('App\LiveVideo', 'id', 'video_id');
    }

    /**
     * Load user using relation model
     */
    public function getUser()
    {
        return $this->hasOne('App\User', 'id', 'user_id');
    }
}
