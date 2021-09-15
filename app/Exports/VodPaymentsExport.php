<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
  
class VodPaymentsExport implements FromView 
{

    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View {

       return view('exports.vod-payments', [
           'data' => \App\PayPerView::all()
       ]);

    }

}