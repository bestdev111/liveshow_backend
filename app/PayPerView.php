<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PayPerView extends Model
{
    //

    protected $appends = ['amount_formatted','admin_amount_formatted', 'user_amount_formatted'];

    /**
     * Get the video record associated with the flag.
     */
    public function vodVideo()
    {
        return $this->hasOne('App\VodVideo', 'id', 'video_id');
    }

    /**
     * Get the video record associated with the flag.
     */
    public function userVideos()
    {
        return $this->hasOne('App\User', 'id', 'user_id');
    }


    public function getAmountFormattedAttribute() {

	    return formatted_amount($this->amount);
	}

	public function getAdminAmountFormattedAttribute() {

	    return formatted_amount($this->admin_amount);
    }
    
    public function getUserAmountFormattedAttribute() {

        return formatted_amount($this->user_amount);
    }

     /**
     * Scope a query to only include users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCommonResponse($query) {

        return $query->leftJoin('users' , 'users.id' ,'=' , 'pay_per_views.user_id')
                     ->leftJoin('vod_videos' , 'vod_videos.id' ,'=' , 'pay_per_views.video_id')
                     ->select('pay_per_views.*');


    }

}
