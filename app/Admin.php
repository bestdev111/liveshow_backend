<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

use App\Helpers\Helper;

class Admin extends Authenticatable
{
    //

    public static function boot()
    {
        //execute the parent's boot method 
        parent::boot();

        //delete your related models here, for example
        static::deleting(function($model){
            
            if($model->picture) {

                Helper::delete_picture($model->picture , ADMIN_FILE_PATH);

            }

        });
    }
}
