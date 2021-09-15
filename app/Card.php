<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCommonResponse($query) {

    	$query->select('id as user_card_id', 'customer_id' , 'last_four' ,'card_type', 'card_token' , 'is_default', 'card_holder_name');

    	return $query;
    }
}
