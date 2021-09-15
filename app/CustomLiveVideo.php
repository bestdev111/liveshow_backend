<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use App\Helpers\Helper;


class CustomLiveVideo extends Model
{
    public function toArray() {

    	$array = parent::toArray();

        $array['created_time'] = $this->created_at ? $this->created_at->diffForHumans() : '-';

        return $array;
    }

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLiveVideoResponse($query)
    {
        return $query
            ->leftJoin('users', 'users.id', '=', 'custom_live_videos.user_id')
            ->select(
                'custom_live_videos.id as custom_live_video_id' ,
                'custom_live_videos.title' ,
                'custom_live_videos.description',
                'custom_live_videos.hls_video_url',
                'custom_live_videos.rtmp_video_url',
                'custom_live_videos.status',
                'custom_live_videos.image',
                'custom_live_videos.user_id' ,
                \DB::raw('IFNULL(users.picture,"") as user_picture'),
                \DB::raw('IFNULL(users.unique_id,"") as user_unique_id'),
                \DB::raw('IFNULL(users.name,"") as user_name'),
                \DB::raw('DATE_FORMAT(custom_live_videos.created_at , "%e %b %y") as created_date'),
                'custom_live_videos.created_at',
                \DB::raw('("live") as category_name')
            );
    }

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOwnerResponse($query)
    {
        return $query
            ->leftJoin('users', 'users.id', '=', 'custom_live_videos.user_id')
            ->select(
                'custom_live_videos.id as custom_live_video_id' ,
                'custom_live_videos.title' ,
                'custom_live_videos.description',
                'custom_live_videos.image',
                'custom_live_videos.hls_video_url',
                'custom_live_videos.rtmp_video_url',
                'custom_live_videos.status',
                'custom_live_videos.user_id' ,
                \DB::raw('IFNULL(users.picture,"") as user_picture'),
                \DB::raw('IFNULL(users.name,"") as user_name'),
                \DB::raw('IFNULL(users.unique_id,"") as user_unique_id'),
                \DB::raw('DATE_FORMAT(custom_live_videos.created_at , "%e %b %y") as created_date'),
                'custom_live_videos.created_at',
                \DB::raw('("live") as category_name')
            );
    }

    public function user() {

        return $this->belongsTo('App\User');
        
    }

     /**
     * Scope a query to only include users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCommonResponse($query) {

        return $query->leftJoin('users' , 'users.id' ,'=' , 'custom_live_videos.user_id')
                     ->select('custom_live_videos.*');


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

                Helper::storage_delete_file($model->image, LIVETV_IMAGE_PATH);
            }


        });
    }
}
