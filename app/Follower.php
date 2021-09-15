<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Follower extends Model
{
    //

    /**
     * Load follower using relation model
     */
    public function getUser()
    {
        return $this->hasOne('App\User', 'id', 'follower');
    }

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCommonResponse($query) {

        return $query->leftJoin('users' , 'users.id' ,'=' , 'followers.follower')
        			->select(
        				'users.id as user_id',
			            'users.unique_id as user_unique_id',
                        'users.name',
			            'users.email as email',
			            'users.picture as picture',
			            'users.chat_picture as chat_picture',
			            'users.is_content_creator',
                        'followers.follower',
                        'followers.created_at',
                        'followers.updated_at'
                    );
    
    }

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFollowerResponse($query) {

        return $query->leftJoin('users' , 'users.id' ,'=' , 'followers.follower')
                    ->select(
                        'users.id as user_id',
                        'users.unique_id as user_unique_id',
                        'users.name',
                        'users.email as email',
                        'users.picture as picture',
                        'users.chat_picture as chat_picture',
                        'users.is_content_creator',
                        'followers.follower',
                        'followers.created_at',
                        'followers.updated_at'
                    );
    
    }

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFollowingResponse($query) {

        return $query->leftJoin('users' , 'users.id' ,'=' , 'followers.user_id')
                    ->select(
                        'users.id as user_id',
                        'users.unique_id as user_unique_id',
                        'users.name',
                        'users.email as email',
                        'users.picture as picture',
                        'users.chat_picture as chat_picture',
                        'users.is_content_creator',
                        'followers.follower',
                        'followers.created_at',
                        'followers.updated_at'
                    );
    
    }
}
