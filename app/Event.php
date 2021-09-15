<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Log;
use App\Helpers\Helper;

class Event extends Model
{
    protected $table = 'events';
    protected $appends = ['name', 'email', 'url', 'date' ,'description'];
    protected $fillable = ['name', 'email', 'url', 'date', 'description'];
    public function getPublishTimeAttribute() {

        return common_date($this->created_at, '', 'd M Y');
    }

    public function getCreatedAtFormattedAttribute() {
        return $this->asDateTime($this->created_at)->format('Y-m-d h:i A');
    }
    
	public function user() {

		return $this->belongsTo('App\User');
		
	}

    /**
     * Boot function for using with User Events
     *
     * @return void
     */

    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    /**
     * Scope a query to only include active users.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHomeResponse($query) {

        return $query
            ->select(
                'users.name as name',
                'users.email as email',
                'users.url as url',
                'users.date as date',
                'event.created_at',
                'event.updated_at',
                );
    }
}
