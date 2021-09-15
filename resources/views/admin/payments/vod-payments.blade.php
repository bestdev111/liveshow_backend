@extends('layouts.admin')

@section('title',tr('vod_payments'))

@section('content-header',tr('payments'))

@section('breadcrumb')
<li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
<li><i class="fa fa-money"></i> {{tr('payments')}}</li>
<li class="active"><i class="fa fa-credit-card"></i> {{tr('vod_payments')}}</li>
@endsection

@section('content')
<div class="row">
	<div class="col-xs-12">
		<div class="box box-primary">
			<div class="box-header label-primary">
				<b>@yield('title')</b>

				<!-- EXPORT OPTION START -->

				@if(count($vod_payments) > 0 )

				<ul class="admin-action btn btn-default pull-right" style="margin-right: 30px">

					<li class="dropdown">
						<a class="dropdown-toggle" data-toggle="dropdown" href="#">
							{{tr('export')}} <span class="caret"></span>
						</a>
						<ul class="dropdown-menu">
							<li role="presentation">
								<a role="menuitem" tabindex="-1" href="{{route('admin.vod-payments.export' , ['format' => 'xlsx'])}}">
									<span class="text-red"><b>{{tr('excel_sheet')}}</b></span>
								</a>
							</li>

							<li role="presentation">
								<a role="menuitem" tabindex="-1" href="{{route('admin.vod-payments.export' , ['format' => 'csv'])}}">
									<span class="text-blue"><b>{{tr('csv')}}</b></span>
								</a>
							</li>
						</ul>
					</li>
				</ul>

				@endif
			</div>
			<!-- EXPORT OPTION END -->

			<div class="box-body table-responsive">



				<div class="col-md-12 mb-2">

					<form action="{{route('admin.vod-videos.payments.list') }}" method="GET" method="GET" role="search">
						{{ csrf_field() }}
						<div class="col-sm-offset-6 mb-2">
							<div class="col-md-2">
								<input type="text" class="form-control search_input" name="search_key" value="{{Request::get('search_key')?:''}}" placeholder="{{tr('vod_payments_search_placeholder')}}">

							</div>

							<div class="col-md-3">
								<select class="form-control" name="paid_status">
									<option value="">{{tr('select')}}</option>
									<option value="{{PAID_STATUS}}" @if(Request::get('paid_status')== PAID_STATUS ) selected @endif>{{tr('paid')}}</option>
									<option value="{{UNPAID}}" @if(Request::get('paid_status')== UNPAID && Request::get('paid_status') !='' ) selected @endif>{{tr('not_paid')}}</option>
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

							<a class="btn btn-danger" href="{{route('admin.vod-videos.payments.list')}}">{{tr('clear')}}</a>


							</span>
						</div>


					</form>
				</div><br>








				@if(count($vod_payments)>0)

				<table  class="table table-bordered table-striped ">
					<thead>
						<tr>
							<th>{{tr('id')}}</th>
							<th>{{tr('title')}}</th>
							<th>{{tr('user_name')}}</th>
							<th>{{tr('payment_id')}}</th>
							<!-- 									<th>{{tr('amount')}}</th>
 -->
							<th>{{tr('admin_commission')}}</th>
							<th>{{tr(('user_commission'))}}</th>
							<th>{{tr(('payment_mode'))}}</th>
							<th>{{tr('is_coupon_applied')}}</th>
							<th>{{tr('coupon_code')}}</th>
							<th>{{tr('coupon_amount')}}</th>
							<th>{{tr('plan_amount')}}</th>
							<th>{{tr('final_amount')}}</th>
							<th>{{tr('coupon_reason')}}</th>
							<th>{{tr('status')}}</th>
							<th>{{tr('action')}}</th>
						</tr>
					</thead>

					<tbody>

						@foreach($vod_payments as $i=>$value)

						<tr>
							<td>{{showEntries($_GET , $i+1)}}</td>

							<td class="line_css"><a href="{{route('admin.vod-videos.view',['video_id' =>$value->video_id])}}">{{$value->vodVideo ? $value->vodVideo->title : tr('not_available') }}</a></td>

							<td><a href="{{route('admin.users.view' , ['user_id' => $value->user_id])}}">{{$value->userVideos ? $value->userVideos->name : tr('not_available') }}</a></td>

							<td>

							  <a href="{{route('admin.vod-videos.payments.view', ['vod_payment_id' => $value->id])}}">
								{{$value->payment_id}}
                              </a>
							</td>

							<!-- <td>{{formatted_amount($value->amount)}}</td> -->

							<td>{{formatted_amount($value->admin_amount)}}</td>

							<td>{{formatted_amount($value->user_amount)}}</td>

							<td class="text-capitalize">{{$value->payment_mode?:tr('not_available')}}</td>

							<td>
								@if($value->is_coupon_applied)
								<span class="label label-success">{{tr('yes')}}</span>
								@else
								<span class="label label-danger">{{tr('no')}}</span>
								@endif
							</td>

							<td>{{$value->coupon_code ?:tr('not_available')}}</td>

							<td> {{ $value->coupon_amount ? formatted_amount($value->coupon_amount) : tr('not_available')}}</td>

							<td>{{formatted_amount($value->subscription_amount)}}</td>

							<td>{{formatted_amount($value->amount)}}</td>

							<td>
								{{$value->coupon_reason ?: tr('not_available') }}
							</td>

							<td>
								@if($value->status == PAID_STATUS)
								<span class="label label-success">{{tr('paid')}}</span>
								@else
								<span class="label label-danger">{{tr('not_paid')}}</span>
								@endif
							</td>

							<td>
								<a href="{{route('admin.vod-videos.payments.view', ['vod_payment_id' => $value->id])}}" class="label label-success" target="_blank">{{tr('view') }}</a>
							</td>

						</tr>

						@endforeach

					</tbody>

				</table>

				<div align="right" id="paglink">{{ $vod_payments->appends(request()->input())->links() }}</div>

				@else
				<h3 class="no-result">{{tr('no_result_found')}}</h3>
				@endif
			</div>
		</div>
	</div>
</div>
</div>

@endsection