<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Redeem extends Model
{

    protected $appends = ['redeem_id', 'total_formatted', 'remaining_formatted', 'paid_formatted'];

    protected $hidden = ['id'];

    public function getTotalFormattedAttribute() {

	    return formatted_amount($this->total);
	}

	public function getRemainingFormattedAttribute() {

	    return formatted_amount($this->remaining);
	}

	public function getPaidFormattedAttribute() {

	    return formatted_amount($this->paid);
	}

	public function getRedeemIdAttribute() {

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

    	return $query->select('id as redeem_id', 'redeems.*');

    }
}
