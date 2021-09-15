@extends('layouts.admin')

@section('title', tr('redeems'))

@if($user)

@section('title')

{{$user ? $user->name  : ""}} - {{tr('redeems')}}

@endsection

@section('content-header', tr('users'))

@else

@section('title', tr('redeems'))

@section('content-header', tr('redeems'))

@endif


@section('breadcrumb')
<li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
<li><a href="{{route('admin.users.index')}}"><i class="fa fa-user"></i> {{tr('users')}}</a></li>
<li class="active"><i class="fa fa-trophy"></i> {{tr('redeems')}}</li>
@endsection

@section('content')

@include('notification.notify')

<div class="row">

	<div class="col-xs-12">

		<div class="box box-primary">

			<div class="box-header label-primary">

				<b>@yield('title')</b>

				<a href="{{route('admin.users.index')}}" class="btn btn-default pull-right"><i class="fa fa-eye"></i> {{tr('view_users')}}</a>
			</div>

			<div class="box-body table-responsive">

				<div class="col-md-9 mb-2 search_div">

					<form class="col-sm-offset-6 mb-2" action="{{route('admin.users.redeems') }}" method="GET"  role="search">
						{{ csrf_field() }}
						<div class="row input-group">
							<div class="col-md-1">
								<input type="text" class="form-control search_input" name="search_key" value="{{Request::get('search_key')??''}}" placeholder="{{tr('redeem_search_placeholder')}}">
							</div>

							<div class="col-md-4">
								<select class="form-control select-width input-space" name="status">
									<option value="">{{tr('select_status')}}</option>
									<option value="{{REDEEM_REQUEST_SENT}}" @if(Request::get('status')==REDEEM_REQUEST_SENT && Request::get('status')!='') selected @endif>{{tr('REDEEM_REQUEST_SENT')}}</option>
									<option value="{{REDEEM_REQUEST_PROCESSING}}" @if(Request::get('status')==REDEEM_REQUEST_PROCESSING && Request::get('status')!='' ) selected @endif>{{tr('REDEEM_REQUEST_PROCESSING')}}</option>
									<option value="{{REDEEM_REQUEST_PAID}}" @if(Request::get('status')==REDEEM_REQUEST_PAID && Request::get('status')!='' ) selected @endif>{{tr('REDEEM_REQUEST_PAID')}}</option>
									<option value="{{REDEEM_REQUEST_CANCEL}}" @if(Request::get('status')==REDEEM_REQUEST_CANCEL && Request::get('status')!='' ) selected @endif>{{tr('REDEEM_REQUEST_CANCEL')}}</option>

								</select>
							</div>

							<button type="submit" class="btn btn-warning">
								<span class="glyphicon glyphicon-search"> {{tr('search')}}</span>
							</button>

							<a class="btn btn-danger" href="{{route('admin.users.redeems')}}">{{tr('clear')}}</a>
							</span>
						</div>

					</form>
				</div><br>


				<table class="table table-bordered table-striped">

					<thead>
						<tr>
							<th>{{tr('id')}}</th>
							<th>{{tr('username')}}</th>
							<th>{{tr('redeem_amount')}}</th>
							<th>{{tr('paid_amount')}}</th>
							<th>{{tr('sent_date')}}</th>
							<th>{{tr('payment_mode')}}</th>
							<th>{{tr('status')}}</th>
							<th>{{tr('action')}}</th>
						</tr>

					</thead>

					<tbody>

						@foreach($data as $i => $value)

						<tr>

							<td>{{showEntries($_GET, $i+1)}}</td>

							<td>

								<a href="{{route('admin.users.view' , ['user_id' => $value->user_id])}}">
									{{$value->user ? $value->user->name : tr('user_not_available')}}
								</a>

							</td>

							<td><b>{{formatted_amount($value->request_amount)}}</b></td>

							<td><b>{{formatted_amount($value->paid_amount)}}</b></td>

							<td>{{$value->created_at ? $value->created_at->diffForHumans() : ""}}</td>

							<td><b>{{($value->payment_mode) ? ucfirst($value->payment_mode) : '-' }}</b></td>

							<td><b>{{redeem_request_status($value->status)}}</b></td>

							<td>

								@if(in_array($value->status ,[REDEEM_REQUEST_SENT , REDEEM_REQUEST_PROCESSING]))

								<form action="{{route('admin.payout.invoice')}}" method="POST">

									<input type="hidden" name="redeem_request_id" value="{{$value->id}}">

									<input type="text" name="paid_amount" value="{{$value->request_amount}}">

									<input type="hidden" name="user_id" value="{{$value->user_id}}">

									<?php $confirm_message = tr('redeem_pay_confirm'); ?>

									<button type="submit" class="btn btn-success btn-sm" onclick='confirm("{{$confirm_message}}")'>{{tr('paynow')}}</button>
								</form>

								@else
								<span>-</span>
								@endif

							</td>
						</tr>
						@endforeach

					</tbody>

				</table>

				<div align="right" id="paglink"><?php echo $data->appends(request()->input())->links(); ?></div>
			</div>
		</div>
	</div>
</div>

@endsection