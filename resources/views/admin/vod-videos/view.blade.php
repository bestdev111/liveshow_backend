@extends('layouts.admin')

@section('title', tr('view_vod_video'))

@section('content-header', tr('vod_videos'))

@section('styles')

<style>
hr {
    margin-bottom: 10px;
    margin-top: 10px;
}
</style>

@endsection

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li><a href="{{route('admin.vod-videos.index')}}"><i class="fa fa-video-camera"></i> {{tr('vod_videos')}}</a></li>
    <li class="active">{{tr('view_video')}}</li>
@endsection 

@section('content')

    <div class="row">

        @include('notification.notify')

        <div class="col-lg-12">
        
            <div class="box box-primary">
        
            <div class="box-header with-border btn-primary">
                <div class='pull-left'>
                    <h3 class="box-title" style="color: white;font-size:15px"> <b>{{$video->title}}</b></h3>
                    <br>
                </div>
                <div class='pull-right'>
                    
                    <a href="{{route('admin.vod-videos.edit',['id'=>$video->id])}}" class="btn btn-sm btn-warning"><i class="fa fa-pencil"></i> {{tr('edit')}}</a>
                    
                </div>
                <div class="clearfix"></div>
            </div>
            <!-- /.box-header -->
            <div class="box-body">

                @if($video->amount > 0)

                    <section id="revenue-section" >

                        <div class="row">

                            <h3 style="margin-top:0;" class="text-green col-lg-12">{{tr('ppv_revenue')}}</h3>

                            <div class="col-md-4">

                                <p class="ppv-amount-label">
                                    <b>{{tr('total')}}: </b>
                                    <label class="text-red">{{formatted_amount($video->user_amount + $video->admin_amount)}}</label>
                                </p>

                            </div>

                            <div class="col-md-4">

                                <p class="ppv-amount-label">
                                    <b>{{tr('admin_amount')}}: </b>
                                    <label class="text-green">{{formatted_amount($video->admin_amount)}}</label>
                                </p>

                            </div>

                            <div class="col-md-4">

                                <p class="ppv-amount-label">
                                    <b>{{tr('user_amount')}}: </b>
                                    <label class="text-blue">
                                        {{formatted_amount($video->user_amount)}} 
                                    </label>
                                </p>

                            </div>

                        </div>

                    </section>

                @endif

              <div class="row">
                 
                <div class="col-lg-12 row">

                    <div class="col-lg-6">
                        <strong>{{tr('ppv_status')}}</strong>
                        <a class="pull-right">

                            @if($video->amount > 0)
                                <span class="label label-success">{{tr('yes')}}</span>

                            @else
                                <span class="label label-danger">{{tr('no')}}</span>
                            @endif
                        </a>

                        <hr>

                        <strong>{{tr('published_time')}}</strong>
                        <a class="pull-right">{{$video->publish_time ? common_date($video->publish_time, Auth::guard('admin')->user()->timezone, 'd-m-Y H:i:s'):'-'}}</a>

                        <hr>

                        <strong>{{tr('publish_status')}}</strong>
                        <a class="pull-right">

                            @if($video->publish_status > 0)
                                <span class="label label-success">{{tr('published')}}</span>

                            @else
                                <span class="label label-danger">{{tr('not_yet_published')}}</span>
                            @endif
                        </a>

                        <hr>

                        @if($video->amount > 0)

                        <strong>{{tr('ppv_amount')}}</strong>
                         <a class="pull-right">{{formatted_amount($video->amount)}}</a>

                        <hr>

                        @endif
                       
                         <strong>{{tr('uploaded_to')}}</strong>
                         <a class="pull-right">{{$video->getUser ? $video->getUser->name : tr('user_not_available') }}</a>
                         <hr>

                         <strong>{{tr('uploaded_by')}}</strong>
                         <a class="pull-right">{{$video->created_by ? $video->created_by : CREATOR }}</a>
                         <hr>
                        

                        @if($video->amount > 0)

                            <strong>{{tr('type_of_subscription')}}</strong>

                            <a class="pull-right">

                                @if($video->type_of_subscription==ONE_TIME_PAYMENT)

                                    {{tr('one_time_payment')}}

                                @else

                                    {{tr('recurring_payment')}}

                                @endif
                            </a>

                            <hr>
                        @endif

                        <strong>{{tr('created_at')}}</strong>
                        <a class="pull-right">
                         {{common_date($video->created_at,Auth::guard('admin')->user()->timezone)}}</a>
                         <hr>

                        <strong>{{tr('updated_at')}}</strong>
                         <a class="pull-right">
                            {{common_date($video->updated_at,Auth::guard('admin')->user()->timezone)}}
                        </a>

                    </div>

                    <div class="col-lg-6">

                        <strong>{{tr('type_of_user')}}</strong>
                        <a class="pull-right">{{vod_type_of_user($video->type_of_user)}}</a>
                        <hr>

                        <strong>{{tr('type_of_subscription')}}</strong>
                        <a class="pull-right">{{vod_type_of_subscription($video->type_of_subscription)}}</a>
                        <hr>

                        <strong>{{tr('amount')}}</strong>
                        <a class="pull-right">{{formatted_amount($video->amount)}}</a>
                        <hr> 

                        <strong>{{tr('admin_amount')}}</strong>
                        <a class="pull-right">{{formatted_amount($video->admin_amount)}}</a>
                        <hr>

                        <strong>{{tr('user_amount')}}</strong>
                        <a class="pull-right">{{formatted_amount($video->user_amount)}}</a>
                        <hr>

                        <strong>{{tr('viewer_count')}}</strong>
                        <a class="pull-right">{{$video->viewer_count}}</a>
                        <hr>

                        <div class="box-body box-profile">
                            <h4></h4>
                        </div>

                    </div>
                    
                  </div>

                </div>

                <hr>

                <div>                    
                    
                    <strong><i class="fa fa-file-text-o margin-r-5"></i> {{tr('description')}}</strong>

                    <p style="margin-top: 10px;"><?= $video->description ?> </p>

                    <hr>

                </div>

                <div class="row">

                    <div class="col-lg-12">

                        <div class="col-lg-6">

                            <strong><i class="fa fa-video-camera margin-r-5"></i> {{tr('video')}}</strong>

                            <div class="row margin-bottom" style="margin-top: 10px;">
                                <div class="col-lg-12">
                                    <div id="video-player" style="width: 100%"></div>
                                </div>
                            </div>
                        </div>  

                        <div class="col-lg-6">
                           <strong><i class="fa fa-file-picture-o margin-r-5"></i> {{tr('image')}}</strong>

                            <div class="row margin-bottom" style="margin-top: 20px;">
                                <!-- /.col -->
                                <div class="col-lg-12">
                                    <img alt="Photo" src="{{($video->image) ? $video->image : ''}}" class="img-responsive" style="width:100%;height:240px;">
                                    <!-- /.col -->
                                </div>
                                  <!-- /.row -->
                            </div>
                        </div>
                        
                    </div>

                </div>
            </div>
        </div>
    </div>
    </div>
@endsection


@section('scripts')


    <script src="{{asset('jwplayer/jwplayer.js')}}"></script>

    <script>jwplayer.key="{{Setting::get('jwplayer_key')}}";</script>

    <script type="text/javascript">

    var playerInstance = jwplayer("video-player");

    playerInstance.setup({
        file: "{{$video->video}}",
        image: "{{$video->image}}",
        width: "100%",
        aspectratio: "16:9",
        primary: "flash",
        controls : true,
        "controlbar.idlehide" : false,
        controlBarMode:'floating',
        "controls": {
          "enableFullscreen": false,
          "enablePlay": false,
          "enablePause": false,
          "enableMute": true,
          "enableVolume": true
        },
        // autostart : true,
        "sharing": {
            "sites": ["reddit","facebook","twitter"]
          },
    });

    </script>

@endsection