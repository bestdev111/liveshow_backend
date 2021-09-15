@extends('layouts.admin')

@section('title', tr('video_payments'))

@section('content-header',tr('payments'))

@section('breadcrumb')
<li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
<li><i class="fa fa-money"></i> {{tr('payments')}}</li>
<li class="active"><i class="fa fa-money"></i> {{tr('video_payments')}}</li>
@endsection

@section('content')

@include('notification.notify')

<div class="row">
	<div class="col-xs-12">
		<div class="box box-primary">
			<div class="box-header label-primary">

				<b>@yield('title')</b>
				<!-- EXPORT OPTION START -->

				@if(count($data) > 0 )

				<ul class="admin-action btn btn-default pull-right" style="margin-right: 60px">

					<li class="dropdown">
						<a class="dropdown-toggle" data-toggle="dropdown" href="#">
							{{tr('export')}} <span class="caret"></span>
						</a>
						<ul class="dropdown-menu">
							<li role="presentation">
								<a role="menuitem" tabindex="-1" href="{{route('admin.payperview.export' , ['format' => 'xlsx'])}}">
									<span class="text-red"><b>{{tr('excel_sheet')}}</b></span>
								</a>
							</li>

							<li role="presentation">
								<a role="menuitem" tabindex="-1" href="{{route('admin.payperview.export' , ['format' => 'csv'])}}">
									<span class="text-blue"><b>{{tr('csv')}}</b></span>
								</a>
							</li>
						</ul>
					</li>
				</ul>

				@endif

				<!-- EXPORT OPTION END -->

			</div>

			<div class="box-body table-responsive">


				<div class="col-md-12 mb-2">

					<form action="{{route('admin.videos.payments') }}" method="GET" method="GET" role="search">
						{{ csrf_field() }}
						<div class="col-sm-offset-6 mb-2">
							<div class="col-md-2">
								<input type="text" class="form-control search_input" name="search_key" value="{{Request::get('search_key')??''}}" placeholder="{{tr('payments_search_placeholder')}}">

							</div>

							<div class="col-md-3">
								<select class="form-control" name="paid_status">
									<option value="">{{tr('select')}}</option>
									<option value="{{YES}}" @if(Request::get('paid_status')==YES ) selected @endif>{{tr('paid')}}</option>
									<option value="{{NO}}" @if(Request::get('paid_status')==NO && Request::get('paid_status') !='' ) selected @endif>{{tr('not_paid')}}</option>
								</select>
							</div>

							<div class="col-md-3">
									<select class="form-control" name="payment_mode">
										<option value="">{{tr('select')}}</option>
										<option value="{{PAYPAL}}" @if(Request::get('payment_mode')== PAYPAL ) selected @endif>{{PAYPAL}}</option>
										<option value="{{CARD}}" @if(Request::get('payment_mode')== CARD ) selected @endif>{{CARD}}</option>
									</select>
								</div>



							<button type="submit" class="btn btn-warning">
								<span class="glyphicon glyphicon-search"> {{tr('search')}}</span>
							</button> 

							<a class="btn btn-danger" href="{{route('admin.videos.payments')}}">{{tr('clear')}}</a>


							</span>
						</div>


					</form>
				</div><br>








				@if(count($data) > 0)

				<table  class="table table-bordered table-striped">

					<thead>
						<tr>
							<th>{{tr('id')}}</th>
							<th>{{tr('name')}}</th>
							<th>{{tr('video')}}</th>
							<th>{{tr('payment_id')}}</th>
							<th>{{tr('amount')}}</th>
							<th>{{tr('admin_commission')}}</th>
							<th>{{tr('user_commission')}}</th>
							<th>{{tr('payment_mode')}}</th>
							<th>{{tr('final_amount')}}</th>
							<th>{{tr('status')}}</th>
							<th>{{tr('action')}}</th>
						</tr>
					</thead>

					<tbody>

						@foreach($data as $i => $payment)

						<tr>
							<td>{{showEntries($_GET ,$i+1)}}</td>
							<td>
								@if($payment->paiduser)

								<a href="{{route('admin.users.view' , ['user_id' => $payment->user_id])}}"> {{$payment->paiduser ? $payment->paiduser->name : tr('not_available')}} </a></td>

							@else
							-
							@endif
							<td style="display:flex"><a href="{{route('admin.videos.view', ['video_id' => $payment->live_video_id])}}">{{$payment->getVideo ? $payment->getVideo->title : tr('not_available')}}</a></td>


							<td>{{$payment->payment_id??tr('not_available')}}</td>
							<td>{{formatted_amount($payment->amount)}}</td>
							<td>{{formatted_amount($payment->admin_amount)}}</td>
							<td>{{formatted_amount($payment->user_amount)}}</td>
							<td class="text-capitalize">{{$payment->payment_mode?:tr('not_available')}}</td>

							<td>{{formatted_amount($payment->amount)}}</td>

							<td class="text-center">

								@if($payment->status)
								<span class="label label-success">{{tr('paid')}}</span>
								@else
								<span class="label label-danger">{{tr('not_paid')}}</span>
								@endif
							</td>

							<td>
								<a href="{{route('admin.live_video_payments.view' , ['video_payment_id' => $payment->id] )}}" class="btn btn-success btn-xs" target="_blank">{{tr('view')}}</a>
							</td>
						</tr>

						@endforeach
					</tbody>
				</table>

				<div align="right" id="paglink">{{ $data->appends(request()->input())->links() }}</div>

				@else
				<h3 class="no-result">{{tr('no_result_found')}}</h3>
				@endif
			</div>
		</div>
	</div>

	@endsection