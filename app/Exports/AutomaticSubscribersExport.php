<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
  
class AutomaticSubscribersExport implements FromView 
{

    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View {

    	$result = \App\UserSubscription::select(\DB::raw('max(user_subscriptions.id) as user_subscription_id'),'user_subscriptions.*','subscriptions.title')
			->leftjoin('subscriptions', 'subscriptions.id','=' ,'subscription_id')
			->where('subscriptions.amount', '>', DEFAULT_FALSE)
			->where('user_subscriptions.status', PAID_STATUS)
			->where('user_subscriptions.is_cancelled',AUTORENEWAL_ENABLED)
			->groupBy('user_subscriptions.user_id')
			->orderBy('user_subscriptions.created_at' , 'desc')
			->get()->chunk(100);
			// Check the result is not empty

       	return view('exports.automatic_subscribers', [
        	'payments' => $result
       	]);

    }

}