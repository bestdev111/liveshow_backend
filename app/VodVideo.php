<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Setting, DB;

use App\Helpers\Helper;

class VodVideo extends Model {

    protected $appends = ['amount_formatted','vod_video_unique_id'];

    public function getAmountFormattedAttribute() {

        return formatted_amount($this->amount);
    }

    public function getVodVideoUniqueIdAttribute() {

        return $this->unique_id;
    }

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query) {

        $query->where('vod_videos.status', VOD_APPROVED_BY_USER)->where('vod_videos.admin_status', VOD_APPROVED_BY_ADMIN)->where('vod_videos.publish_status', VIDEO_PUBLISHED);

        return $query;
    }

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloq uent\Builder
     */
    public function scopeVodResponse($query)
    {

        $currency = Setting::get('currency');

        return $query
            ->leftJoin('users', 'users.id', '=', 'vod_videos.user_id')
            ->select('users.id as user_id', 
                'users.name as user_name' , 
                'users.unique_id as user_unique_id',
                'users.picture as user_picture' , 
                'vod_videos.id as vod_id' , 
                'vod_videos.id as vod_video_id' , 
                'vod_videos.title' , 
                'vod_videos.description' , 
                'vod_videos.image',
                'vod_videos.video',
                'vod_videos.amount',
                'vod_videos.type_of_subscription',
                'vod_videos.type_of_user',
                'vod_videos.created_at',
                'vod_videos.status',
                'users.user_type',
                'vod_videos.admin_status',
                'vod_videos.created_at as date',
                'vod_videos.is_pay_per_view',
                'vod_videos.publish_status',
                'vod_videos.publish_time',
                'vod_videos.unique_id',
                'vod_videos.admin_amount',
                'vod_videos.user_amount',
                'vod_videos.publish_status as publish_type',
                'vod_videos.created_by',
                'vod_videos.unique_id as vod_video_unique_id' , 
                DB::raw("'$currency' as currency")
                );
    }

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVodRevenueResponse($query)
    {

        $currency = Setting::get('currency');

        return $query
            ->select(
                'vod_videos.id as vod_id' , 
                'vod_videos.id as vod_video_id' , 
                'vod_videos.title' , 
                'vod_videos.user_id',
                'vod_videos.description' , 
                'vod_videos.image',
                'vod_videos.video',
                'vod_videos.amount',
                'vod_videos.type_of_subscription',
                'vod_videos.type_of_user',
                'vod_videos.created_at',
                'vod_videos.status',
                'vod_videos.admin_amount',
                'vod_videos.user_amount',
                'vod_videos.admin_status',
                'vod_videos.publish_status',
                'vod_videos.publish_time',
                'vod_videos.unique_id',
                DB::raw("'$currency' as currency"),
                'vod_videos.is_pay_per_view'
                );
    }

    /**
     * Save the unique ID 
     *
     *
     */
    public function setUniqueIdAttribute($value){

        $this->attributes['unique_id'] = uniqid(str_replace(' ', '-', $value));

    }


    public function getUser()
    {
        return $this->hasOne('App\User', 'id', 'user_id');
    }

    public function getPayPerViews()
    {
        return $this->hasMany('App\PayPerView', 'id', 'video_id');
    }


    /**
     * Boot function for using with User Events
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

         //delete your related models here, for example
        static::deleting(function($model)
        {
            if($model->image) {

                Helper::storage_delete_file($model->image, VOD_VIDEO_IMAGE_PATH);
            }

            if($model->video) {

                Helper::storage_delete_file($model->video, VOD_VIDEO_VIDEO_PATH);

            }

            if($model->getPayPerViews) {
                
                foreach ($model->getPayPerViews as $key => $value) {
                    $value->delete();
                }
            }

        });
    }
}
