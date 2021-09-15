<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
  
class SubscriptionsExport implements FromView 
{

    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View {

       return view('exports.subscription', [
           'subscription' => \App\UserSubscription::all()
       ]);

    }

}