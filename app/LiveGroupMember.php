<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LiveGroupMember extends Model
{
     /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCommonResponse($query) {

        return $query->leftJoin('users' , 'users.id' , '=' , 'live_group_members.member_id')
        	->select(
	            'live_group_members.id as live_group_member_id',
                'users.unique_id as user_unique_id',
                'users.id as member_id',
                'users.name as member_name',
	            'users.description as member_description',
                'users.picture as member_picture',
	            'users.description as member_description',
	            'live_group_members.status as live_group_member_status'
            );    
    }

    public function LiveGroup() {

        return $this->belongsTo(LiveGroup::class, 'live_group_id');
    }

    
}
