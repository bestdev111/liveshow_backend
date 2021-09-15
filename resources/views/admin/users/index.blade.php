@extends('layouts.admin')

@section('title', tr('view_users'))

@section('content-header',tr('users'))


@section('breadcrumb')
<li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
<li><a href="{{route('admin.users.index')}}"><i class="fa fa-user"></i> {{tr('users')}}</a></li>
<li class="active"><i class="fa fa-eye"></i> {{tr('view_users')}}</li>
@endsection

@section('content')

@include('notification.notify')

<div class="row">

	<div class="col-xs-12">

		<div class="box box-primary">

			<div class="box-header label-primary">
				<b class="capitalize">
					{{$sort== IS_CONTENT_CREATOR ? tr('streamers') : ($sort==''? tr('view_users'): tr('declined_users')) }}

					@if(isset($subscription)) -

					<a class="text-white" href="{{route('admin.subscriptions.view', $subscription->id)}}"> {{$subscription->title}}</a>

					@endif
				</b>
				<a href="{{route('admin.users.create')}}" style="float:right" class="btn btn-default"> <i class="fa fa-plus"></i> {{tr('add_user')}}</a>

				<!-- EXPORT OPTION START -->

				@if(count($data) > 0 )

				<ul class="admin-action btn btn-default pull-right" style="margin-right: 20px;">

					<li class="dropdown">
						<a class="dropdown-toggle" data-toggle="dropdown" href="#">
							{{tr('export')}} <span class="caret"></span>
						</a>
						<ul class="dropdown-menu">
							<li role="presentation">
								<a role="menuitem" tabindex="-1" href="{{route('admin.users.export', ['format' => 'xlsx'])}}">
									<span class="text-red"><b>{{tr('excel_sheet')}}</b></span>
								</a>
							</li>

							<li role="presentation">
								<a role="menuitem" tabindex="-1" href="{{route('admin.users.export' , ['format' => 'csv'])}}">
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
								<a role="menuitem" tabindex="-1" href="#">	<span class="text-red"><b>{{ tr('delete') }}</b> </span></a>
							</li>
							<li role="presentation" class="action_list" id="bulk_approve">
								<a role="menuitem" tabindex="-1" href="#">	<span class="text-blue"><b>{{ tr('approve') }}</b></span></a>
							</li>

							<li role="presentation" class="action_list" id="bulk_decline">
								<a role="menuitem" tabindex="-1" href="#"><span class="text-blue"><b>{{ tr('decline') }}</b></span></a>
							</li>

						</ul>
					</li>
				</ul>

				@endif


				<div class="bulk_action">

					<form  action="{{route('admin.users.bulk_action')}}" id="users_form" method="POST" role="search">

						@csrf

						<input type="hidden" name="action_name" id="action" value="">

						<input type="hidden" name="selected_users" id="selected_ids" value="">

						<input type="hidden" name="page_id" id="page_id" value="{{ (request()->page) ? request()->page : '1' }}">

					</form>
				</div>

				<!-- EXPORT OPTION END -->
			</div>

			<div class="box-header">
				<span class="col-sm-4">
					<h4>{{tr('total_users')}} : {{ $data->total_users ?? 0}}</h4>
				</span>
				<span class="col-sm-4">
					<h4>{{tr('total_approved')}} : {{ $data->total_approved ?? 0 }}</h4>
				</span>
				<span class="col-sm-4">
					<h4>{{tr('total_declined')}} : {{ $data->total_declined ?? 0}}</h4>
				</span>
			</div>

			<div class="box-body  table-responsive">

				<div class="col-md-12 mb-2">

					@if(count($data) > 0)

					@if(Request::get('sub_page'))
					<form class="col-sm-offset-6 mb-2" action="{{route('admin.users.index',['sort'=> Request::get('sort'),'sub_page'=> Request::get('sub_page')])}}" method="POST" method="POST" role="search">
						@else
						<form class="col-sm-offset-6 mb-2" action="{{route('admin.users.index') }}" method="POST" method="POST" role="search">
							@endif
							{{ csrf_field() }}
							<div class="row input-group">

							    @if(Request::get('sub_page'))
								<div class="col-md-3"></div>
								@endif

							   <div class="col-md-1"></div>
								<div class="col-md-1">
									<input type="text" class="form-control search_input" name="search_key" value="{{Request::get('search_key')??''}}" placeholder="{{tr('search_by')}}">
								</div>

								<input type="hidden" name="sub_page" value="{{Request::get('sub_page')??''}}">

								@if(Request::get('sub_page') != strtolower(tr('streamers')))
								<div class="col-md-3">
									<select class="form-control" name="status">
										<option value="">{{tr('select')}}</option>
										<option value="{{BOTH_USERS}}" @if(Request::get('status')==BOTH_USERS) selected @endif>{{tr('all')}}</option>
										<option value="{{VIEWER_STATUS}}" @if(Request::get('status')==VIEWER_STATUS && Request::get('status')!='' ) selected @endif>{{tr('viewers')}}</option>
										<option value="{{CREATOR_STATUS}}" @if(Request::get('status')==CREATOR_STATUS && Request::get('status')!='' ) selected @endif>{{tr('streamers')}}</option>
									</select>
								</div>
								@endif

								
								@if(Request::get('sub_page')!= strtolower(tr('declined')))
								<div class="col-md-3">

									<select class="form-control" name="user_status">
										<option value="">{{tr('select')}}</option>
										<option value="{{SORT_BY_APPROVED}}" @if(Request::get('user_status')==SORT_BY_APPROVED && Request::get('user_status')!='' ) selected @endif>{{tr('approved')}}</option>
										<option value="{{SORT_BY_DECLINED}}" @if(Request::get('user_status')==SORT_BY_DECLINED && Request::get('user_status')!='' ) selected @endif>{{tr('declined')}}</option>
									    <option  value="{{SORT_BY_VERIFIED}}" @if(Request::get('user_status') == SORT_BY_VERIFIED && Request::get('user_status')!='' ) selected @endif>{{tr('verified')}}</option>
                                        <option  value="{{SORT_BY_NOT_VERIFIED}}" @if(Request::get('user_status')==SORT_BY_NOT_VERIFIED && Request::get('user_status')!='' ) selected @endif>{{tr('unverified')}}</option>

									</select>

								</div>
								@endif

								<button type="submit" class="btn btn-warning">
									<span class="glyphicon glyphicon-search"> {{tr('search')}}</span>
								</button>
								@if(Request::get('sub_page'))
								<a class="btn btn-danger" href="{{route('admin.users.index',['sort'=> Request::get('sort'),'sub_page'=> Request::get('sub_page')])}}">{{tr('clear')}}</a>
								@else
								<a class="btn btn-danger" href="{{route('admin.users.index')}}">{{tr('clear')}}</a>
								@endif
								</span>
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
							<th>{{tr('email')}}</th>
							<th>{{tr('mobile_no')}}</th>
							<th>{{tr('verify')}}</th>
							<th>{{tr('content_creator')}}</th>
							<!-- <th>{{tr('blocked_me_by_others')}}</th> -->
							<!-- <th>{{tr('user_type')}}</th> -->
							<th>{{tr('is_logged')}}</th>
							<th>{{tr('clear_login')}}</th>
							<th>{{tr('status')}}</th>
							<th>{{tr('action')}}</th>
						</tr>
					</thead>

					<tbody>

						@foreach($data as $i => $user)

						<tr>

							<td><input type="checkbox" name="row_check" class="faChkRnd" id="{{$user->id}}" value="{{$user->id}}"></td>

							<td>{{$i+$data->firstItem()}}</td>

							<td>
								<a href="{{route('admin.users.view', ['user_id' => $user->id])}}" target="_blank">
									{{$user->name}}

									@if($user->user_type)
									<span class="text-green pull-right"><i class="fa fa-check-circle" title="{{tr('paid_user')}}"></i></span>
									@else
									<span class="text-red pull-right"><i class="fa fa-times" title="{{tr('unpaid_user')}}"></i></span>
									@endif
								</a>

							</td>

							<td>{{$user->email}}</td>

							<td>{{$user->mobile ?: tr('not_available')}}</td>

							<td>

								@if($user->is_verified)

								<label class="text-green">{{tr('verified')}}</label>

								@else
								<a href="{{route('admin.users.verify', $user->id)}}" class="btn btn-xs btn-danger">{{tr('verify')}}</a>
								@endif

							</td>

							<td class="text-center">

								@if($user->is_content_creator)

								<i class="label label-success">{{tr('yes')}}</i>

								@else

								<i class="label label-danger">{{tr('no')}}</i>

								@endif

							</td>

							<?php /*<td>
						      		<a href="{{route('admin.users.block_list', array('blocked_by_others'=>$user->id))}}" class="btn btn-xs btn-warning">
						      			<b>{{count($user->blockedMeByOthers)}} {{tr('users')}}</b>
						      		</a>
						      	</td> 
						     
						      	<td>
						      			
						      		@if($user->user_type)

						      			<label class="btn btn-xs btn-success">{{tr('premium')}}</label>

						      		@else
						      			<label class="btn btn-xs btn-danger">{{tr('normal')}}</label>
						      		@endif
						      	</td>*/ ?>


							<td class="text-center">
								@if($user->login_status)
								<i class="label label-success">{{tr('yes')}}</i>
								@else
								<i class="label label-danger">{{tr('no')}}</i>
								@endif
							</td>

							<td class="text-center">
								<a onclick="return confirm(&quot;{{tr('clear_login_confirmation')}}&quot;);" href="{{route('admin.users.clear-login', ['id'=>$user->id])}}"><span class="label label-warning">{{tr('clear')}}</span></a>
							</td>

							<td class="text-center">
								@if($user->status)
								<span class="label label-success">{{tr('approved')}}</span>
								@else
								<span class="label label-danger">{{tr('declined')}}</span>
								@endif
							</td>

							<td>
								<div class="dropdown {{$i <= 2 ? 'dropdown' : 'dropup'}}">

									<button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
										{{tr('action')}}
										<span class="caret"></span>
									</button>

									<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenu">
										<li>
											@if(Setting::get('admin_delete_control'))
											<a href="javascript:;" class="btn disabled" style="display: inline-block"><b><i class="fa fa-edit"></i>{{tr('edit')}}</b></a>
											@else
											<a href="{{route('admin.users.edit', array('id' => $user->id))}}"><b><i class="fa fa-edit"></i>&nbsp;{{tr('edit')}}</b></a>
											@endif
										</li>

										<li>
											<a href="{{route('admin.users.view', ['user_id' => $user->id])}}">
												<span class="text-green"><b><i class="fa fa-eye"></i>&nbsp;{{tr('view')}}</b></span>
											</a>
										</li>

										<li class="divider" role="presentation"></li>

										@if($user->is_content_creator)

										<li>
											<a href="{{route('admin.streamer_galleries.list' ,$user->id)}}">
												<span class="text-yellow"><b><i class="fa fa-picture-o"></i>&nbsp;{{tr('gallery')}}</b></span>
											</a>
										</li>

										<li class="divider" role="presentation"></li>

										@endif

										<?php /*<li>
											<a href="{{route('admin.users.followers' , array('id' => $user->id))}}"><b><i class="fa fa-users"></i>&nbsp;{{tr('followers')}}</b></a>
											</li>

											<li>
											<a href="{{route('admin.users.followings' , array('id' => $user->id))}}"><b><i class="fa fa-users"></i>&nbsp;{{tr('followings')}}</b></a>

											</li> */ ?>


										<li>
											@if(!$user->status)
											<a onclick="return confirm(&quot;{{$user->name}} - {{tr('user_approve_confirmation')}}&quot;);" href="{{route('admin.users.approve' , array('id' => $user->id))}}"><b><i class="fa fa-check"></i>&nbsp;{{tr('approve')}}</b></a>
											@else

											<a onclick="return confirm(&quot;{{$user->name}} - {{tr('user_decline_confirmation')}}&quot;); " href="{{route('admin.users.approve' , array('id' => $user->id))}}"><b><i class="fa fa-times"></i>&nbsp;{{tr('decline')}}</b></a>
											@endif
										</li>

										<li class="divider" role="presentation"></li>

										<li>
											@if(Setting::get('admin_delete_control'))

											<a href="javascript:;" class="btn disabled" style="text-align: left">
												<span class="text-red"><b><i class="fa fa-close"></i>&nbsp;{{tr('delete')}}</b></span>
											</a>

											@else
											<a onclick="return confirm(&quot;{{ tr('user_delete_confirmation', $user->name) }}&quot;)" href="{{route('admin.users.delete', array('id' => $user->id))}}">
												<span class="text-red"><b><i class="fa fa-close"></i>&nbsp;{{tr('delete')}}</b></span>
											</a>
											@endif

										</li>

										@if($user->is_content_creator)

										<li>
											<a href="{{route('admin.subscriptions.plans', $user->id)}}">
												<span class="text-green"><b><i class="fa fa-eye"></i> {{tr('subscription_plans')}}</b></span>
											</a>

										</li>

										@else

										<li>
											<a href="{{route('admin.become.creator' , ['id'=>$user->id])}}">
												<span class="text-green"><b><i class="fa fa-eye"></i> {{tr('become_a_creator')}}</b></span>
											</a>

										</li>

										@endif

										<li>
											<a href="{{route('admin.videos.videos_list', ['user_id' => $user->id])}}"><span class="text-green"><b><i class="fa fa-wifi"></i> {{tr('live_videos')}}</b></a>
										</li>

									</ul>

								</div>

							</td>

						</tr>
						@endforeach

					</tbody>

				</table>

				<div align="right" id="paglink">
					{{$data->appends(request()->input())->links()}}
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
						var message = "<?php echo tr('admin_users_delete_confirmation') ?>";
					}else if(selected_action == 'bulk_approve'){
						var message = "<?php echo tr('admin_users_approve_confirmation') ?>";
					}else if(selected_action == 'bulk_decline'){
						var message = "<?php echo tr('admin_users_decline_confirmation') ?>";
					}
					var confirm_action = confirm(message);

					if (confirm_action == true) {
					  $( "#users_form" ).submit();
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
			return this.id;
		})
		.get();

		localStorage.setItem("user_checked_items"+page, JSON.stringify(checked_ids));

		get_values();

	});
	// select all checkbox
	$("#check_all").on("click", function () {
		if ($("input:checkbox").prop("checked")) {
			$("input:checkbox[name='row_check']").prop("checked", true);
			var checked_ids = $(':checkbox[name=row_check]:checked').map(function() {
				return this.id;
			})
			.get();

			localStorage.setItem("user_checked_items"+page, JSON.stringify(checked_ids));
			get_values();
		} else {
			$("input:checkbox[name='row_check']").prop("checked", false);
			localStorage.removeItem("user_checked_items"+page);
			get_values();
		}

	});


	function get_values(){
		var pageKeys = Object.keys(localStorage).filter(key => key.indexOf('user_checked_items') === 0);
		var values = Array.prototype.concat.apply([], pageKeys.map(key => JSON.parse(localStorage[key])));

		if(values){
			$('#selected_ids').val(values);
		}

		for (var i=0; i<values.length; i++) {
			$('#' + values[i] ).prop("checked", true);
		}

}

});
</script>

@endsection