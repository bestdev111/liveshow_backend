<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use App\Helpers\Helper;

use DB;

class LiveGroup extends Model
{
    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBaseResponse($query) {

        return $query->leftJoin('users' , 'users.id', '=' , 'live_groups.user_id')
        ->select(
            'live_groups.id as live_group_id',
            'live_groups.user_id as owner_id',
            'live_groups.name as live_group_name',
            'live_groups.description as live_group_description',
            'live_groups.picture as live_group_picture',
            'live_groups.status as live_group_status',
            'users.name as owner_name',
            'users.picture as picture',
            'live_groups.created_at',
            'live_groups.updated_at',
             DB::raw('DATE_FORMAT(live_groups.updated_at, "%d %M, %Y") as date')
            );
    
    }


    public function groupMembers() {

        return $this->hasMany('App\LiveGroupMember');
    }

    public function scopeGroupMembersCount() {

        return $query->groupMembers()->count();
    }

    public function liveVideos() {

        return $this->hasMany('App\LiveVideo');
    }

    public static function boot() {

        //execute the parent's boot method 
        parent::boot();

        //delete your related models here, for example

        static::deleting(function($model) {

            Helper::storage_delete_file($model->picture,COMMON_IMAGE_PATH);

            if(count($model->groupMembers) > 0 ) {

                $model->groupMembers()->delete();
            }

            /**
             * If group deleting means no need to delete the live videos. 
             * Because still user can see the live videos of his own
             */

            if(count($model->liveVideos) > 0 ) {

                foreach ($model->liveVideos as $key => $liveVideo) {
                    $liveVideo->live_group_id = 0;
                }

            }
        }); 
        
        static::creating(function ($model) {

            $model->attributes['unique_id'] = "G"."-".uniqid();

        });
    }
}
