<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use App\Follower;

class BlockList extends Model
{

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCommonResponse($query) {
        
        return $query->leftJoin('users' , 'users.id' ,'=' , 'block_user_id')
                    ->select(
                        'users.name',
                        'users.unique_id as user_unique_id',
                        'users.email as email',
                        'users.picture as picture',
                        'users.chat_picture as chat_picture',
                        'users.is_content_creator',
                        'block_lists.user_id',
                        'block_lists.block_user_id'
                    );
    
    }
    /**
     * Boot function for using with User Events
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
           
            $user_follower = Follower::where('follower', $model->user_id)
            	->where('user_id', $model->block_user_id)->first();

            if ($user_follower) {

            	$user_follower->delete();

            }

            $user_following = Follower::where('follower', $model->block_user_id)
            	->where('user_id', $model->user_id)->first();

            if ($user_following) {

            	$user_following->delete();
            	
            }

        });

    }
}
