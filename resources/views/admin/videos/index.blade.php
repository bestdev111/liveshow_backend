@extends('layouts.admin')

@section('title') {{$data->title}} @endsection

@section('content-header')

@if(isset($subscription)) {{$subscription->title}} - @endif

{{ tr('live_videos') }}

@endsection

@section('breadcrumb')
<li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
<li><a href="{{route('admin.videos.videos_list')}}"><i class="fa fa-video-camera"></i> {{tr('live_videos')}}</a></li>
<li class="active"><i class="fa fa-eye"></i> {{$data->title}}</li>
@endsection

@section('content')

@include('notification.notify')

<div class="row">

    <div class="col-xs-12">

        <div class="box box-primary">

            <div class="box-header label-primary">

                <b>@yield('title')</b>

                <!-- EXPORT OPTION START -->

                @if(@count($data) > 0)

                <ul class="admin-action btn btn-default pull-right" style="margin-right: 20px">

                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                            {{tr('export')}} <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li role="presentation">
                                <a role="menuitem" tabindex="-1" href="{{route('admin.livevideos.export' , ['format' => 'xlsx'])}}">
                                    <span class="text-red"><b>{{tr('excel_sheet')}}</b></span>
                                </a>
                            </li>

                            <li role="presentation">
                                <a role="menuitem" tabindex="-1" href="{{route('admin.livevideos.export' , ['format' => 'csv'])}}">
                                    <span class="text-blue"><b>{{tr('csv')}}</b></span>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
              @if($data->title == 'History')
                <ul class="admin-action btn btn-default action_button pull-right">

                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                            {{ tr('admin_bulk_action') }} <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li role="presentation" class="action_list" id="bulk_delete">
                                <a role="menuitem" tabindex="-1" href="#">  <span class="text-red"><b>{{ tr('delete') }}</b> </span></a>
                            </li>
                        </ul>
                    </li>
                </ul>
                @endif

                @endif


                <!-- EXPORT OPTION END -->
            </div>

            <div class="box-body table-responsive">

               <div class="bulk_action">

                    <form  action="{{route('admin.videos.bulk_action_delete')}}" id="live_form" method="POST" role="search">

                        @csrf

                        <input type="hidden" name="action_name" id="action" value="">

                        <input type="hidden" name="selected_live_id" id="selected_ids" value="">

                        <input type="hidden" name="page_id" id="page_id" value="{{ (request()->page) ? request()->page : '1' }}">

                    </form>
                </div>

                <div class="col-md-12 mb-2">

                @if(@count($data) > 0)

                    @if($is_streaming == DEFAULT_FALSE)
                    <form action="{{route('admin.videos.videos_list') }}" method="POST" method="POST" role="search">
                        @else
                        <form action="{{route('admin.videos.index') }}" method="POST" method="POST" role="search">
                            @endif
                            {{ csrf_field() }}
                            <div class="row video_search_box">

                                @if($is_streaming == DEFAULT_TRUE)
                                <div class="col-md-3"></div>
                                @endif

                                
                                <div class="col-md-1">
                                    <input type="text" class="form-control search_input" name="search_key" value="{{Request::get('search_key')??''}}" placeholder="{{tr('live_video_search_placeholder')}}">

                                </div>

                                <div class="col-md-2 dropdown-width">
                                    <select class="form-control" name="payment_status">
                                        <option value="">{{tr('select_status')}}</option>
                                        <option value="{{FREE_VIDEO}}" @if(Request::get('payment_status')==FREE_VIDEO && Request::get('payment_status')!='' ) selected @endif>{{tr('free_videos')}}</option>
                                        <option value="{{PAID_VIDEO}}" @if(Request::get('payment_status')==PAID_VIDEO ) selected @endif>{{tr('paid_videos')}}</option>
                                    </select>
                                </div>


                                <div class="col-md-2 dropdown-width">
                                    <select class="form-control" name="video_type">
                                        <option value="">{{tr('select_video_type')}}</option>
                                        <option value="{{TYPE_PUBLIC}}" @if(Request::get('video_type')==TYPE_PUBLIC) selected @endif>{{tr('public_videos')}}</option>
                                        <option value="{{TYPE_PRIVATE}}" @if(Request::get('video_type')==TYPE_PRIVATE) selected @endif>{{tr('private_videos')}}</option>
                                    </select>
                                </div>


                                @if($is_streaming == DEFAULT_FALSE)
                                <div class="col-md-2 dropdown-width">
                                    <select class="form-control" name="broadcast_status">
                                        <option value="">{{tr('select_status')}}</option>
                                        <option value="{{DELETE_STATUS}}" @if(Request::get('broadcast_status')==DELETE_STATUS) selected @endif>{{tr('video_call_initiated')}}</option>
                                        <option value="{{VIDEO_STREAMING_ONGOING}}" @if(Request::get('broadcast_status')==VIDEO_STREAMING_ONGOING && Request::get('broadcast_status')!='' ) selected @endif>{{tr('video_call_started')}}</option>
                                        <option value="{{VIDEO_STREAMING_STOPPED}}" @if(Request::get('broadcast_status')==VIDEO_STREAMING_STOPPED) selected @endif>{{tr('video_call_ended')}}</option>
                                    </select>
                                </div>
                                @endif



                                <div class="pull-right">
                                    <button type="submit" class="btn btn-warning btn-sm">
                                        <span class="glyphicon glyphicon-search"> {{tr('search')}}</span>
                                    </button> &nbsp;&nbsp;
                                    @if($is_streaming == DEFAULT_FALSE)
                                    <a class="btn btn-danger btn-sm" href="{{route('admin.videos.videos_list')}}">{{tr('clear')}}</a>
                                    @else
                                    <a class="btn btn-danger" href="{{route('admin.videos.index')}}">{{tr('clear')}}</a>

                                    @endif

                                    </span>
                                </div>
                            </div>


                        </form>
                </div><br>


                <table class="table table-bordered table-striped">

                    <thead>
                        <tr>
                            <th>
                                <input id="check_all" type="checkbox">
                            </th>
                            <th>{{tr('id')}}</th>
                            <th>{{tr('username')}}</th>
                            <th>{{tr('title')}}</th>
                            <th>{{tr('video_type')}}</th>
                            <th>{{tr('payment')}}</th>
                            <th>{{tr('streaming_status')}}</th>
                            <th>{{tr('streamed_at')}}</th>
                            <th>{{tr('viewer_count')}}</th>
                            <th>{{tr('action')}}</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($data as $i => $video)

                        <tr>

                            <td><input type="checkbox" name="row_check" class="faChkRnd" id="live_{{$video->id}}" value="{{$video->id}}"></td>


                            <td>{{$i+$data->firstItem()}}</td>

                            <td>
                                <a href="{{$video->user ? route('admin.users.view' , ['user_id' => $video->user_id]) : '#'}}"> {{$video->user ? $video->user->name : tr('user_not_available')}}</a>
                            </td>

                            <td><a href="{{route('admin.videos.view', ['video_id' =>$video->id])}}"> {{$video->title}}</a>
                            </td>

                            <td>
                                @if($video->type == TYPE_PUBLIC)
                                <label class="label label-primary capitalize">{{TYPE_PUBLIC}}</label>
                                @else
                                <label class="label label-danger capitalize">{{TYPE_PRIVATE}}</label>
                                @endif
                            </td>

                            <td>
                                @if($video->payment_status)
                                <label class="label label-warning">{{tr('payment')}}</label>
                                @else
                                <label class="label label-success">{{tr('free')}}</label>
                                @endif
                            </td>

                            <td>
                                @if($video->is_streaming)

                                @if(!$video->status)

                                <label class="label label-danger"><b>{{tr('video_call_started')}}</b></label>

                                @else

                                <label class="label label-danger"><b>{{tr('video_call_ended')}}</b></label>

                                @endif

                                @else

                                <label class="label label-primary"><b>{{tr('video_call_initiated')}}</b></label>

                                @endif
                            </td>

                            <td>{{common_date($video->created_at, Auth::guard('admin')->user()->timezone)}}</td>

                            <td>{{$video->viewer_cnt}}</td>

                            <td><a href="{{route('admin.videos.view' , ['video_id' =>$video->id])}}" class="btn btn-success btn-xs" target="_blank"><b>{{tr('view')}}</b></a></td>

                            @if(Setting::get('delete_video'))
                            <td>
                                <a role="button" href="#" class="btn btn-danger"><i class="fa fa-trash"></i>&nbsp;{{tr('delete')}}</a>
                            </td>
                            @else
                             <td>
                                <a onclick="return confirm(&quot;{{ tr('video_delete_confirmation', $video->title) }}&quot;)" href="{{route('admin.videos.delete', $video->id)}}" class="btn btn-danger"><i class="fa fa-trash"></i>&nbsp;{{tr('delete')}}</a>
                            </td>
                            @endif
                        </tr>
                        @endforeach

                    </tbody>

                </table>

                <div align="right" id="paglink">{{ $data->appends(['video_type' =>Request::get('video_type')??'', 'broadcast_status'=>Request::get('broadcast_status')??'','payment_status'=>Request::get('payment_status')])->links() }}</div>

                @else
                <center>
                    <h3>{{tr('no_on_live_videos_found')}}</h3>
                </center>
                @endif

            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
    
@if(Session::has('bulk_action'))
<script type="text/javascript">
    $(document).ready(function(){
        localStorage.clear();
    });
</script>
@endif

<script type="text/javascript">

    $(document).ready(function(){
        get_values();

        $('.action_list').click(function(){
            var selected_action = $(this).attr('id');
            if(selected_action != undefined){
                $('#action').val(selected_action);
                if($("#selected_ids").val() != ""){
                    if(selected_action == 'bulk_delete'){
                        var message = "<?php echo tr('admin_live_video_delete_confirmation') ?>";
                    }
                    var confirm_action = confirm(message);

                    if (confirm_action == true) {
                      $( "#live_form" ).submit();
                    }
                    // 
                }else{
                    alert('Please select the check box');
                }
            }
        });
    // single check
    var page = $('#page_id').val();
    $(':checkbox[name=row_check]').on('change', function() {
        var checked_ids = $(':checkbox[name=row_check]:checked').map(function() {
            return this.value;
        })
        .get();

        localStorage.setItem("live_videos_checked_items"+page, JSON.stringify(checked_ids));

        get_values();

    });
    // select all checkbox
    $("#check_all").on("click", function () {
        if ($("input:checkbox").prop("checked")) {
            $("input:checkbox[name='row_check']").prop("checked", true);
            var checked_ids = $(':checkbox[name=row_check]:checked').map(function() {
                return this.value;
            })
            .get();

            localStorage.setItem("live_videos_checked_items"+page, JSON.stringify(checked_ids));
            get_values();
        } else {
            $("input:checkbox[name='row_check']").prop("checked", false);
            localStorage.removeItem("live_videos_checked_items"+page);
            get_values();
        }

    });


    function get_values(){
        var pageKeys = Object.keys(localStorage).filter(key => key.indexOf('live_videos_checked_items') === 0);
        var values = Array.prototype.concat.apply([], pageKeys.map(key => JSON.parse(localStorage[key])));

        if(values){
            $('#selected_ids').val(values);
        }

        for (var i=0; i<values.length; i++) {
            $('#live_' + values[i] ).prop("checked", true);
        }

}

});
</script>

@endsection