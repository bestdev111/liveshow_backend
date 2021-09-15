<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
  
class CancelledSubscribersExport implements FromView 
{

    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View {

    	$user_subscriptions = \App\UserSubscription::select(\DB::raw('max(user_subscriptions.id) as user_subscription_id'),'user_subscriptions.*','subscriptions.title')
			->where('user_subscriptions.status', PAID_STATUS)
			->leftjoin('subscriptions', 'subscriptions.id','=' ,'subscription_id')
			->where('user_subscriptions.is_cancelled', AUTORENEWAL_CANCELLED)
			->groupBy('user_subscriptions.user_id')
			->orderBy('user_subscriptions.created_at' , 'desc')
			->get()->chunk(500);

       	return view('exports.cancelled_subscribers', [
           'payments' => $user_subscriptions
       	]);

    }

}