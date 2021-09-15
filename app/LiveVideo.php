<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use App\Helpers\Helper;

class LiveVideo extends Model
{
    protected $appends = ['amount_formatted', 'payment_type_text', 'publish_time','created_at_formatted'];

    public function getPublishTimeAttribute() {

        return common_date($this->created_at, '', 'd M Y');
    }

    public function getAmountFormattedAttribute() {

        return formatted_amount($this->amount);
    }

    public function getPaymentTypeTextAttribute() {

        return formatted_live_payment_text($this->payment_status);
    }

    public function getCreatedAtFormattedAttribute() {
        return $this->asDateTime($this->created_at)->format('Y-m-d h:i A');
    }
    
    public function setUniqueIdAttribute($value){

		$this->attributes['unique_id'] = uniqid(str_replace(' ', '-', $value));

	}

	public function payments() {

		return $this->hasMany('App\LiveVideoPayment');
		
	}

	public function user() {

		return $this->belongsTo('App\User');
		
	}

	/**
     * Load viewers using relation model
     */
    public function getViewers()
    {
        return $this->hasMany('App\Viewer', 'video_id', 'id');
    }

    /**
     * Load viewers using relation model
     */
    public function getVideosPayments()
    {
        return $this->hasMany('App\LiveVideoPayment', 'live_video_id', 'id');
    }

    public function getLiveGroup()
    {
        return $this->belongsTo('App\LiveGroup', 'live_group_id', 'id');
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
        	if (count($model->getViewers) > 0) {

                foreach($model->getViewers as $viewer)
                {
                    $viewer->delete();
                } 

            }

            if (count($model->getVideosPayments) > 0) {

                foreach($model->getVideosPayments as $video)
                {
                    $video->delete();
                } 

            }

            if($model->snapshot) {

                Helper::delete_picture($model->snapshot, "uploads/rooms");

            }

        });
    }

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVideoResponse($query) {

        return $query->leftJoin('live_groups' , 'live_groups.id' ,'=' , 'live_videos.live_group_id')
                ->select(
                 'users.id as id',
                'users.unique_id as user_unique_id',
                 'users.name as name', 
                 'users.email as email',
                 'users.picture as user_picture',
                 'users.chat_picture as chat_picture',
                 'live_videos.id as video_id',
                 'live_videos.title as title',
                 'live_videos.type as type',
                 'live_videos.description as description',
                 'live_videos.amount as amount',
                 'live_videos.snapshot as snapshot',
                 'live_videos.viewer_cnt as viewers',
                 'live_videos.no_of_minutes as no_of_minutes',
                 'live_videos.payment_status as payment_status',
                 'live_videos.status as video_stopped_status',
                \DB::raw('DATE_FORMAT(live_videos.created_at , "%e %b %y") as date'),
                 'live_videos.live_group_id as live_group_id',
                \DB::raw('IFNULL(live_groups.name,"") as live_group_name'),
                \DB::raw('IFNULL(live_groups.picture,"") as live_group_picture')
            );
    
    }

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHomeResponse($query) {

        return $query
                ->where('live_videos.is_streaming', IS_STREAMING_YES)
                ->where('live_videos.status', VIDEO_STREAMING_ONGOING)
                ->leftJoin('users' , 'users.id' ,'=' , 'live_videos.user_id')
                ->leftJoin('live_groups' , 'live_groups.id' ,'=' , 'live_videos.live_group_id')
                ->select(
                    'users.name as user_name',
                    'users.unique_id as user_unique_id',
                    'users.chat_picture as user_picture',
                    'live_videos.id as live_video_id',
                    'live_videos.virtual_id as virtual_id',
                    'live_videos.user_id',
                    'live_videos.title as title',
                    'live_videos.type as type',
                    'live_videos.broadcast_type as broadcast_type',
                    'live_videos.description as description',
                    'live_videos.amount as amount',
                    'live_videos.snapshot as snapshot',
                    'live_videos.viewer_cnt as viewer_cnt',
                    'live_videos.no_of_minutes',
                    'live_videos.payment_status',
                    'live_videos.status',
                    'live_videos.live_group_id as live_group_id',
                    'live_videos.created_at',
                    'live_videos.updated_at',
                    \DB::raw('IFNULL(live_groups.name,"") as live_group_name'),
                    \DB::raw('IFNULL(live_groups.picture,"") as live_group_picture')
                );

    }

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeListResponse($query) {

        return $query
                ->leftJoin('users' , 'users.id' ,'=' , 'live_videos.user_id')
                ->leftJoin('live_groups' , 'live_groups.id' ,'=' , 'live_videos.live_group_id')
                ->select(
                    'users.name as user_name',
                    'users.unique_id as user_unique_id',
                    'users.chat_picture as user_picture',
                    'live_videos.id as live_video_id',
                    'live_videos.virtual_id as virtual_id',
                    'live_videos.user_id',
                    'live_videos.title as title',
                    'live_videos.type as type',
                    'live_videos.broadcast_type as broadcast_type',
                    'live_videos.description as description',
                    'live_videos.amount as amount',
                    'live_videos.snapshot as snapshot',
                    'live_videos.viewer_cnt as viewer_cnt',
                    'live_videos.no_of_minutes',
                    'live_videos.payment_status',
                    'live_videos.status',
                    'live_videos.live_group_id as live_group_id',
                    'live_videos.created_at',
                    'live_videos.updated_at',
                    \DB::raw('IFNULL(live_groups.name,"") as live_group_name'),
                    \DB::raw('IFNULL(live_groups.picture,"") as live_group_picture')
                );

    }

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCurrentLive($query) {

        return $query->where('live_videos.is_streaming', YES)
                ->where('live_videos.status', VIDEO_STREAMING_ONGOING);

    }

     /**
     * Scope a query to only include users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCommonResponse($query) {

        return $query->leftJoin('users' , 'users.id' ,'=' , 'live_videos.user_id')
                     ->select('live_videos.*');


    }
}
