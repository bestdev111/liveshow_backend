@extends('layouts.admin')

@section('title', tr('view_custom_live_videos'))

@section('content-header', tr('custom_live_videos'))

@section('breadcrumb')
<li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
<li><a href="{{route('admin.custom.live')}}"><i class="fa fa-wifi"></i> {{tr('custom_live_videos')}}</a></li>
<li class="active"><i class="fa fa-eye"></i> {{tr('view_custom_live_videos')}}</li>
@endsection

@section('content')

@include('notification.notify')

<div class="row">

	<div class="col-xs-12">

		<div class="box box-primary">

			<div class="box-header label-primary">
				<b>@yield('title')</b>
				<a href="{{route('admin.custom.live.create')}}" class="btn btn-default pull-right"><i class="fa fa-plus"></i> {{tr('create_custom_live_video')}}</a>

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
			</div>

			<div class="box-body table-responsive">

				<div class="bulk_action">

                    <form  action="{{route('admin.custom.live.bulk_action')}}" id="live_form" method="POST" role="search">

                        @csrf

                        <input type="hidden" name="action_name" id="action" value="">

                        <input type="hidden" name="selected_live_id" id="selected_ids" value="">

                        <input type="hidden" name="page_id" id="page_id" value="{{ (request()->page) ? request()->page : '1' }}">

                    </form>
                </div>

				<div class="col-md-12 mb-2">

					<form action="{{route('admin.custom.live') }}" method="GET" method="GET" role="search">
						{{ csrf_field() }}
						<div class="col-sm-offset-6 mb-2">
						   <div class="col-md-1"></div>
							<div class="col-md-3">
								<input type="text" class="form-control search_input" name="search_key" value="{{Request::get('search_key')??''}}" placeholder="{{tr('custom_live_video_search_placeholder')}}">
							</div>

							<div class="col-md-3">
								<select class="form-control" name="status">
									<option value="">{{tr('select')}}</option>
									<option value="{{PAID_STATUS}}" @if(Request::get('status')== PAID_STATUS ) selected @endif>{{tr('approved')}}</option>
									<option value="{{UNPAID}}" @if(Request::get('status')== UNPAID && Request::get('status') !='' ) selected @endif>{{tr('pending')}}</option>
								</select>
							</div>



							<button type="submit" class="btn btn-warning">
								<span class="glyphicon glyphicon-search"> {{tr('search')}}</span>
							</button> &nbsp;&nbsp;

							<a class="btn btn-danger" href="{{route('admin.custom.live')}}">{{tr('clear')}}</a>


							</span>
						</div>


					</form>
				</div><br>

				@if(count($live_tv_videos) > 0)

				<table class="table table-bordered table-striped">

					<thead>
						<tr>
							<th>
                                <input id="check_all" type="checkbox">
                            </th>
							<th>{{tr('id')}}</th>
							<th>{{tr('username')}}</th>
							<th>{{tr('title')}}</th>
							<th>{{tr('description')}}</th>
							<th>{{tr('image')}}</th>
							<th>{{tr('status')}}</th>
							<th>{{tr('action')}}</th>
						</tr>
					</thead>

					<tbody>
						@foreach($live_tv_videos as $i => $live_tv_details)

						<tr>

							<td><input type="checkbox" name="row_check" class="faChkRnd" id="live_{{$live_tv_details->id}}" value="{{$live_tv_details->id}}"></td>

							<td>{{$i+$live_tv_videos->firstItem()}}</td>

							<td>
								<a href="{{route('admin.users.view' , ['user_id' => $live_tv_details->user_id])}}">
									{{$live_tv_details->user ? $live_tv_details->user->name : tr('user_not_available')}}
								</a>
							</td>
							<td>
								<a href="{{route('admin.custom.live.view' , ['id' => $live_tv_details->id])}}">
									{{substr($live_tv_details->title , 0,25)}}...
								</a>
							</td>

							<td>{{substr($live_tv_details->description , 0,25)}}...</td>

							<td>
								<img src="@if($live_tv_details->image) {{$live_tv_details->image}} @else {{asset('placeholder.png')}} @endif" style="width: 75px;height: 50px;">

							</td>

							<td>

								@if($live_tv_details->status)
								<span class="label label-success">{{tr('approved')}}</span>
								@else
								<span class="label label-warning">{{tr('pending')}}</span>
								@endif

							</td>
							<td>
								<ul class="admin-action btn btn-default">
									<li class="dropdown">
										<a class="dropdown-toggle" data-toggle="dropdown" href="#">
											{{tr('action')}} <span class="caret"></span>
										</a>
										<ul class="dropdown-menu">

											<li role="presentation">
												@if(Setting::get('admin_delete_control'))
												<a role="button" href="javascript:;" class="btn disabled" style="text-align: left">{{tr('edit')}}</a>
												@else
												<a role="menuitem" tabindex="-1" href="{{route('admin.custom.live.edit' , array('id' => $live_tv_details->id))}}">{{tr('edit')}}</a>
												@endif
											</li>

											<li role="presentation"><a role="menuitem" tabindex="-1" target="_blank" href="{{route('admin.custom.live.view' , array('id' => $live_tv_details->id))}}">{{tr('view')}}</a></li>

											@if($live_tv_details->status)
											<li role="presentation"><a role="menuitem" tabindex="-1" href="{{route('admin.custom.live.change_status', array('id'=>$live_tv_details->id))}}" onclick="return confirm(&quot;{{ tr('live_video_decline_confirmation', substr($live_tv_details->title , 0,25). " - " ) }}&quot;)">{{tr('decline')}}</a></li>
											@else

											<li role="presentation"><a role="menuitem" tabindex="-1" href="{{route('admin.custom.live.change_status', array('id'=>$live_tv_details->id))}}">{{tr('approve')}}</a></li>

											@endif

											<li class="divider" role="presentation"></li>

											<li role="presentation">
												@if(Setting::get('admin_delete_control'))

												<a role="button" href="javascript:;" class="btn disabled" style="text-align: left">{{tr('delete')}}</a>

												@else
												<a role="menuitem" tabindex="-1" onclick="return confirm(&quot;{{ tr('live_video_delete_confirmation', substr($live_tv_details->title , 0,25) ) }}&quot;)" href="{{route('admin.custom.live.delete' , array('id' => $live_tv_details->id))}}">{{tr('delete')}}</a>
												@endif
											</li>

										</ul>
									</li>
								</ul>
							</td>
						</tr>

						@endforeach

					</tbody>

				</table>

				<div align="right" id="paglink">
					{{$live_tv_videos->appends(['search_key' => $search_key ?? ""])->links()}}
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
                        var message = "<?php echo tr('admin_live_tv_delete_confirmation') ?>";
                    }else if(selected_action == 'bulk_approve'){
                        var message = "<?php echo tr('admin_live_tv_approve_confirmation') ?>";
                    }else if(selected_action == 'bulk_decline'){
                        var message = "<?php echo tr('admin_live_tv_decline_confirmation') ?>";
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

        localStorage.setItem("custom_live_videos_checked_items"+page, JSON.stringify(checked_ids));

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

            localStorage.setItem("custom_live_videos_checked_items"+page, JSON.stringify(checked_ids));
            get_values();
        } else {
            $("input:checkbox[name='row_check']").prop("checked", false);
            localStorage.removeItem("custom_live_videos_checked_items"+page);
            get_values();
        }

    });


    function get_values(){
        var pageKeys = Object.keys(localStorage).filter(key => key.indexOf('custom_live_videos_checked_items') === 0);
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