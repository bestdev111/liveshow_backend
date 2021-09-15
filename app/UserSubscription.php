<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserSubscription extends Model
{
    protected $appends = ['user_subscription_unique_id','subscription_amount_formatted', 'amount_formatted', 'coupon_amount_formatted'];

    public function getSubscriptionAmountFormattedAttribute() {

        return formatted_amount($this->subscription_amount ?? 0.00);
    }

    public function getUserSubscriptionUniqueIdAttribute() {

        return $this->unique_id;
    }

    public function getAmountFormattedAttribute() {

        return formatted_amount($this->amount  ?? 0.00);
    }

    public function getCouponAmountFormattedAttribute() {

        return formatted_amount($this->coupon_amount ?? 0.00);
    }

    public function user() {
    	return $this->belongsTo('App\User','user_id');
    }

    public function subscription() {
    	return $this->belongsTo('App\Subscription', 'subscription_id');
    }

    /**
     * Scope a query to basic subscription details
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBaseResponse($query) {

        $currency = \Setting::get('currency' , '$');

    	return $query->leftJoin('subscriptions', 'subscriptions.id', '=', 'subscription_id')
                        ->select(
                                'user_subscriptions.id as user_subscription_id',
                                'subscriptions.title as title',
                                'subscriptions.description as description',
                                'subscriptions.popular_status as popular_status',
                                'subscriptions.plan',
                                'user_subscriptions.*',
                                \DB::raw("'$' as currency")
                                );
    }
    

     /**
     * Scope a query to basic user subscription details
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */

    public function scopeCommonResponse($query) {

        return $query->join('users','users.id','=','user_subscriptions.user_id')
               ->join('subscriptions','subscriptions.id','=','user_subscriptions.subscription_id')
               ->select(
                   'user_subscriptions.*',
                   'subscriptions.title as subscription_name',
                   'users.name as user_name',
                   'users.mobile',
                   'users.email'
                );
    }

    public static function boot() {

        parent::boot();

        static::creating(function ($model) {

            $model->attributes['unique_id'] = "US-".uniqid();
        });

        static::created(function($model) {

            $model->attributes['unique_id'] = "US-".$model->attributes['id']."-".uniqid();

            $model->save();
        
        });

       
    }
}
