<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RedeemRequest extends Model
{

    protected $appends = ['redeem_request_status_formatted','redeem_request_id', 'request_amount_formatted', 'paid_amount_formatted'];

    protected $hidden = ['id'];

    public function getRequestAmountFormattedAttribute() {

	    return formatted_amount($this->request_amount);
	}

	public function getPaidAmountFormattedAttribute() {

	    return formatted_amount($this->paid_amount);
    }
    
    public function getRedeemRequestStatusFormattedAttribute() {

        return redeem_request_status($this->status);
    }

	public function getRedeemRequestIdAttribute() {

	    return $this->id;
	}

    public function user() {
    	return $this->belongsTo('App\User');
    }

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCommonResponse($query) {

    	return $query->select('id as redeem_request_id', 'redeem_requests.*');

    }

    public static function boot() {

        parent::boot();

        static::deleting(function ($model) {

            //

        });

    }
}
