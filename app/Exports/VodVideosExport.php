<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
  
class VodVideosExport implements FromView 
{

    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View {

       return view('exports.vod-videos', [
           'data' => \App\VodVideo::all()
       ]);

    }

}