<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
  
class LiveVideosExport implements FromView 
{

    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View {

       return view('exports.live_videos', [
           'live_videos' => \App\LiveVideo::all()
       ]);

    }

}