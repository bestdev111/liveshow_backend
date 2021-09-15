<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LiveVideoPayment extends Model
{
    protected $appends = ['live_video_payment_id','live_video_payment_unique_id', 'admin_amount_formatted', 'user_amount_formatted', 'coupon_amount_formatted', 'live_video_amount_formatted'];

    protected $hidden = ['id'];

    public function getLiveVideoPaymentIdAttribute() {

        return $this->id;
    }

    public function getLiveVideoPaymentUniqueIdAttribute() {

        return $this->unique_id;
    }

    public function getAdminAmountFormattedAttribute() {

        return formatted_amount($this->admin_amount);
    }

    public function getUserAmountFormattedAttribute() {

        return formatted_amount($this->user_amount);
    }

    public function getCouponAmountFormattedAttribute() {

        return formatted_amount($this->coupon_amount);
    }

    public function getLiveVideoAmountFormattedAttribute() {

        return formatted_amount($this->live_video_amount);
    }
    
    public function user() {
    	return $this->belongsTo('App\User','user_id');
    }

    public function paiduser() {
        return $this->belongsTo('App\User' , 'live_video_viewer_id');
    }

    public function getUser() {
    	return $this->belongsTo('App\User', 'user_id');
    }

    public function video() {
    	return $this->belongsTo('App\LiveVideo' , 'id');
    }

     public function getVideo() {
    	return $this->belongsTo('App\LiveVideo' , 'live_video_id');
    }

    /**
     * Scope a query to basic user video payment details
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */

    public function scopeCommonResponse($query) {

        return $query->leftjoin('users','users.id','=','live_video_payments.user_id')
               ->leftjoin('live_videos','live_videos.id','=','live_video_payments.live_video_id')
               ->leftjoin('pay_per_views','pay_per_views.user_id','=','users.id')
               ->select(
                   'live_video_payments.*',
                   'live_videos.title as video_name',
                   'users.name as user_name',
                   'users.mobile',
                   'users.email',
                   'pay_per_views.type_of_subscription',
                   'pay_per_views.type_of_user',
                   'pay_per_views.ppv_date',
                   'pay_per_views.ppv_amount',
                   'pay_per_views.expiry_date'
                );
    }


    public static function boot() {

        parent::boot();

        static::creating(function ($model) {

            $model->attributes['unique_id'] = "LVP-".uniqid();
        });

        static::created(function($model) {

            $model->attributes['unique_id'] = "LVP-".$model->attributes['id']."-".uniqid();

            $model->save();
        
        });

       
    }
}
