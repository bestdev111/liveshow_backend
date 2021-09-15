@extends('layouts.admin')

@section('title', tr('view_vod_videos'))

@section('content-header', tr('vod_videos'))

@section('breadcrumb')
<li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
<li><a href="{{route('admin.vod-videos.index')}}"><i class="fa fa-video-camera"></i> {{tr('vod_videos')}}</a></li>
<li class="active"><i class="fa fa-eye"></i> {{tr('view_vod_videos')}}</li>
@endsection

@section('content')

@include('notification.notify')

<div class="row">

    <div class="col-xs-12">

        <div class="box box-primary">

            <div class="box-header label-primary">
                <b class="font_size_css">@yield('title')</b>

                <a href="{{route('admin.vod-videos.create')}}" style="float:right" class="btn btn-default"><i class="fa fa-plus"></i> {{tr('upload_vod_video')}}</a>

                <!-- EXPORT OPTION START -->

                @if(count($video_list) > 0 )

                <ul class="admin-action btn btn-default pull-right" style="margin-right: 20px">

                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                            {{tr('export')}} <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li role="presentation">
                                <a role="menuitem" tabindex="-1" href="{{route('admin.vod-videos.export' , ['format' => 'xlsx'])}}">
                                    <span class="text-red"><b>{{tr('excel_sheet')}}</b></span>
                                </a>
                            </li>

                            <li role="presentation">
                                <a role="menuitem" tabindex="-1" href="{{route('admin.vod-videos.export' , ['format' => 'csv'])}}">
                                    <span class="text-blue"><b>{{tr('csv')}}</b></span>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>

                <ul class="admin-action btn btn-default action_button pull-right">

                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                            {{ tr('admin_bulk_action') }} <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li role="presentation" class="action_list" id="bulk_delete">
                                <a role="menuitem" tabindex="-1" href="#">  <span class="text-red"><b>{{ tr('delete') }}</b> </span></a>
                            </li>
                            <li role="presentation" class="action_list" id="bulk_approve">
                                <a role="menuitem" tabindex="-1" href="#">  <span class="text-blue"><b>{{ tr('approve') }}</b></span></a>
                            </li>

                            <li role="presentation" class="action_list" id="bulk_decline">
                                <a role="menuitem" tabindex="-1" href="#"><span class="text-blue"><b>{{ tr('decline') }}</b></span></a>
                            </li>

                        </ul>
                    </li>
                </ul>

                @endif

                 <div class="bulk_action">

                    <form  action="{{route('admin.vod-videos.bulk_action')}}" id="vod_form" method="POST" role="search">

                        @csrf

                        <input type="hidden" name="action_name" id="action" value="">

                        <input type="hidden" name="selected_vod" id="selected_ids" value="">

                        <input type="hidden" name="page_id" id="page_id" value="{{ (request()->page) ? request()->page : '1' }}">

                    </form>
                </div>

            </div>
            <!-- EXPORT OPTION END -->

            <div class="box-body">

                <div class="table table-responsive">

                    <div class="col-md-12 mb-2">

                        @if(count($video_list) > 0)

                        <form action="{{route('admin.vod-videos.index') }}" method="POST" method="POST" role="search">

                                {{ csrf_field() }}
                                <div class="col-sm-offset-6 mb-2">
                                    <div class="col-md-1">
                                        <input type="text"  value="{{Request::get('search_key')??''}}" class="form-control search_input" name="search_key" placeholder="Search by {{tr('title')}}"> 
                                    </div>

                                    <div class="col-md-3">
                                        <select class="form-control" name="payment_status">
                                            <option value="">{{tr('select')}}</option>
                                            <option value="{{FREE_VIDEO}}" @if(Request::get('payment_status')==FREE_VIDEO && Request::get('payment_status')!='' ) selected @endif>{{tr('free_videos')}}</option>
                                            <option value="{{PAID_VIDEO}}" @if(Request::get('payment_status')==PAID_VIDEO ) selected @endif>{{tr('paid_videos')}}</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <select class="form-control" name="admin_status">
                                            <option value="">{{tr('select')}}</option>
                                            <option value="{{ADMIN_APPROVE_STATUS}}" @if(Request::get('admin_status')== ADMIN_APPROVE_STATUS) selected @endif>{{tr('admin_approved')}}</option>
                                            <option value="{{ADMiN_DECLINE_STATUS}}" @if(Request::get('admin_status')== ADMiN_DECLINE_STATUS && Request::get('admin_status')!='') selected @endif>{{tr('admin_declined')}}</option>
                                        </select>
                                    </div>


                                    <button type="submit" class="btn btn-warning">
                                        <span class="glyphicon glyphicon-search"> {{tr('search')}}</span>
                                    </button> &nbsp;&nbsp;
                                    <a class="btn btn-danger" href="{{route('admin.vod-videos.index')}}">{{tr('clear')}}</a>
                                    </span>
                                </div>


                            </form>
                    </div><br>

                    <table class="table table-bordered table-striped text-nowrap">
                        <thead>
                            <tr>
                                <th>
                                <input id="check_all" type="checkbox">
                                </th>
                                <th>{{tr('id')}}</th>
                                <th>{{tr('title')}}</th>
                                <th>{{tr('user')}}</th>
                                <th>{{tr('ppv')}}</th>
                                <th>{{tr('total')}} ({{Setting::get('currency')}})</th>
                                <th>{{tr('admin')}} ({{Setting::get('currency')}})</th>
                                <th>{{tr('user')}} ({{Setting::get('currency')}})</th>
                                <th>{{tr('uploaded_by')}}</th>
                                <th>{{tr('user_status')}}</th>
                                <th>{{tr('admin_status')}}</th>
                                <th>{{tr('action')}}</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($video_list as $i => $video)

                            <tr>
                                <td><input type="checkbox" name="row_check" class="faChkRnd" id="vod_{{$video->vod_id}}" value="{{$video->vod_id}}"></td>

                                <td>{{$i+$video_list->firstItem()}}</td>

                                <td><a href="{{route('admin.vod-videos.view',['video_id' =>$video->vod_id])}}">{{$video->title}}</a></td>

                                <td><a href="{{route('admin.users.view', ['user_id' => $video->user_id])}}">{{$video->user_name}}</a>
                                </td>

                                <td>
                                    @if($video->amount != 0)

                                    <span class="label label-success">{{tr('yes')}} -{{formatted_amount($video->amount)}}</span>
                                    @else
                                    <span class="label label-danger">{{tr('no')}}</span>

                                    @endif
                                </td>

                                <td>
                                    {{formatted_amount($video->admin_amount+$video->user_amount)}}
                                </td>

                                <td>
                                    {{formatted_amount($video->admin_amount)}}
                                </td>

                                <td>
                                    {{formatted_amount($video->user_amount)}}
                                </td>

                                <td class="text-capitalize">
                                    {{$video->created_by ? $video->created_by : tr('user')}}
                                </td>

                                <td>
                                    @if($video->status)

                                    <span class="label label-success">{{tr('approved')}}</span>

                                    @else

                                    <span class="label label-warning">{{tr('pending')}}</span>

                                    @endif
                                </td>

                                <td>
                                    @if($video->admin_status)

                                    <span class="label label-success">{{tr('approved')}}</span>

                                    @else

                                    <span class="label label-warning">{{tr('declined')}}</span>

                                    @endif
                                </td>

                                <td>
                                    <div class="dropdown  {{$i <= 2 ? 'dropdown' : 'dropup'}}">

                                        <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            {{tr('action')}}
                                            <span class="caret"></span>
                                        </button>

                                        <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenu">
                                            <li>
                                                @if(Setting::get('admin_delete_control'))
                                                <a href="javascript:;" class="btn disabled" style="text-align: left;"><b>&nbsp;{{tr('edit')}}</b></a> @else
                                                <a href="{{route('admin.vod-videos.edit',['id'=>$video->vod_id])}}"><b>&nbsp;{{tr('edit')}}</b></a>

                                                @endif
                                            </li>

                                            <li>

                                                <a href="{{route('admin.vod-videos.view',['video_id' =>$video->vod_id])}}"><b>&nbsp;{{tr('view')}}</b></a>

                                            </li>

                                            <li>
                                                <a href="" role="menuitem" tabindex="-1" data-toggle="modal" data-target="#{{$video->vod_id}}"><b> {{tr('pay_per_view')}}</b></a>
                                            </li>

                                            <li>
                                                @if($video->admin_status)

                                                <a href="{{route('admin.vod-videos.status',['video_id'=>$video->vod_id, 'user_id'=>$video->user_id])}}" onclick="return confirm(&quot;{{ tr('vod_video_decline_confirmation', substr($video->title , 0,25). " - " ) }}&quot;)"><b> {{tr('decline')}}</b></a> @else

                                                <a href="{{route('admin.vod-videos.status',['video_id'=>$video->vod_id,'user_id'=>$video->user_id])}}"><b> {{tr('approve')}}</b></a> @endif
                                            </li>

                                            @if(!$video->publish_status)

                                            <li>

                                                <a href="{{route('admin.vod-videos.publish',['video_id'=>$video->vod_id,'user_id'=>$video->user_id])}}"><b> {{tr('publish')}}</b></a>

                                            </li>

                                            @endif

                                            <li>

                                                @if(Setting::get('admin_delete_control'))

                                                <a href="javascript:;" class="btn disabled" style="text-align: left">
                                                    <b> {{tr('delete')}}</b></a>
                                                </a>

                                                @else
                                                <a class="menuitem" tabindex="-1" href="{{route('admin.vod-videos.delete',['video_id'=>$video->vod_id, 'user_id'=>$video->user_id])}}" onclick="return confirm(&quot;{{ tr('vod_video_delete_confirmation', $video->title) }}&quot;)"><b> {{tr('delete')}}</b></a> @endif

                                            </li>
                                        </ul>

                                    </div>

                                </td>
                            </tr>

                            <!-- Modal -->
                            <div id="{{$video->vod_id}}" class="modal fade" role="dialog">
                                <div class="modal-dialog">
                                    <form action="{{route('admin.vod-videos.ppv.create')}}" method="POST">
                                        <!-- Modal content-->
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                <h4 class="modal-title">{{tr('ppv')}}</h4>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">

                                                    <input type="hidden" name="ppv_created_by" id="ppv_created_by" value="{{Auth::guard('admin')->user()->name}}">
                                                </div>
                                                <br>
                                                <input type="hidden" name="video_id" value="{{$video->vod_id}}">

                                                <input type="hidden" name="user_id" value="{{$video->user_id}}">

                                                <div class="row ">
                                                    <div class="col-lg-4">
                                                        <label>{{tr('type_of_subscription')}}</label>
                                                    </div>
                                                    <div class="col-lg-8">
                                                        <div>
                                                            <input type="radio" name="type_of_subscription" value="{{ONE_TIME_PAYMENT}}" id="one_time_payment" @if($video->type_of_subscription == ONE_TIME_PAYMENT || $video->type_of_subscription == 0) checked @endif>&nbsp;
                                                            <label>
                                                                {{tr('one_time_payment')}}</label>&nbsp;
                                                            <input type="radio" name="type_of_subscription" value="{{RECURRING_PAYMENT}}" id="recurring_payment" @if($video->type_of_subscription == RECURRING_PAYMENT) checked @endif>&nbsp;
                                                            <label>{{tr('recurring_payment')}}</label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <input type="hidden" name="type_of_user" value="{{BOTH_USERS}}">

                                                <?php /*<div class="row">
									        	<div class="col-lg-3">
									        		<label>{{tr('type_of_user')}}</label>
									        	</div>
								                <div class="col-lg-9">
								                  <div class="input-group">
								                        <input type="radio" name="type_of_user" value="{{NORMAL_USER}}" checked @if($video->type_of_user == NORMAL_USER || $video->type_of_user == 0) checked @endif>&nbsp;<label>
								                        {{tr('normal_users')}}</label>&nbsp;
								                         <input type="radio" name="type_of_user" value="{{PAID_USER}}" @if($video->type_of_user == PAID_USER) checked @endif>&nbsp;<label>
								                        {{tr('paid_users')}}</label>&nbsp;
								                         <input type="radio" name="type_of_user" value="{{BOTH_USERS}}" @if($video->type_of_user == BOTH_USERS) checked @endif>&nbsp;<label>
								                        {{tr('all_users')}}</label>&nbsp;
								                  </div>
								                </div>
								            </div>
								            <br> */ ?>
                                                <div class="row top_css">
                                                    <div class="col-lg-4">
                                                        <label>{{tr('amount')}}</label>
                                                    </div>
                                                    <div class="col-lg-8">
                                                        <input type="number" required value="{{$video->amount}}" name="amount" class="form-control" id="amount" placeholder="{{tr('amount')}}" step="any">
                                                        <!-- /input-group -->
                                                    </div>
                                                </div>

                                            </div>

                                            <div class="modal-footer">

                                                <div class="pull-left">

                                                    @if($video->amount > 0)

                                                    <a class="btn btn-danger" onclick="return confirm(&quot;{{ tr('remove_pay_per_view_confirmation')}}&quot;)" href="{{route('admin.vod-videos.ppv.delete',['video_id'=>$video->vod_id, 'user_id'=>$video->user_id])}}">{{tr('remove_pay_per_view')}}</a>
                                                    @endif
                                                </div>
                                                <div class="pull-right">
                                                    <button type="button" class="btn btn-default" data-dismiss="modal">{{tr('close')}}</button>
                                                    <button type="submit" class="btn btn-primary">{{tr('submit')}}</button>
                                                </div>
                                                <div class="clearfix"></div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            @endforeach

                        </tbody>

                    </table>

                    <div align="right" id="paglink">
                        {{$video_list->appends(['search_key' => $search_key ?? ""])->links()}}
                    </div>

                    @else
                    <center>
                        <h3>{{tr('no_results_found')}}</h3>
                    </center>
                    @endif

                </div>
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
                        var message = "<?php echo tr('admin_vod_delete_confirmation') ?>";
                    }else if(selected_action == 'bulk_approve'){
                        var message = "<?php echo tr('admin_vod_approve_confirmation') ?>";
                    }else if(selected_action == 'bulk_decline'){
                        var message = "<?php echo tr('admin_vod_decline_confirmation') ?>";
                    }
                    var confirm_action = confirm(message);

                    if (confirm_action == true) {
                      $( "#vod_form" ).submit();
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

        localStorage.setItem("vod_checked_items"+page, JSON.stringify(checked_ids));

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

            localStorage.setItem("vod_checked_items"+page, JSON.stringify(checked_ids));
            get_values();
        } else {
            $("input:checkbox[name='row_check']").prop("checked", false);
            localStorage.removeItem("vod_checked_items"+page);
            get_values();
        }

    });


    function get_values(){
        var pageKeys = Object.keys(localStorage).filter(key => key.indexOf('vod_checked_items') === 0);
        var values = Array.prototype.concat.apply([], pageKeys.map(key => JSON.parse(localStorage[key])));

        if(values){
            $('#selected_ids').val(values);
        }

        for (var i=0; i<values.length; i++) {
            $('#vod_' + values[i] ).prop("checked", true);
        }

}

});
</script>

@endsection