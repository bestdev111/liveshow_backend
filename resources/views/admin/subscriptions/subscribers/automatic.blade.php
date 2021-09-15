@extends('layouts.admin')

@section('title', tr('automatic_subscribers'))

@section('content-header', tr('subscriptions'))

@section('breadcrumb')
<li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
<li><a href="{{route('admin.subscriptions.index')}}"><i class="fa fa-key"></i> {{tr('subscriptions')}}</a></li>
<li class="active"><i class="fa fa-key"></i> {{tr('automatic_subscribers')}}</li>
@endsection

@section('styles')

<style>
	.subscription-image {
		overflow: hidden !important;
		position: relative !important;
		height: 15em !important;
		background-position: center !important;
		background-repeat: no-repeat !important;
		background-size: cover !important;
		margin: 0 !important;
		width: 100%;
	}

	.subscription-desc {
		max-height: 100px;
		overflow-y: auto;
		margin-bottom: 10px !important;
		min-height: 100px;
	}
</style>

@endsection

@section('content')

<div class="row">
	<div class="col-xs-12">

		@include('notification.notify')

		<div class="box box-primary">



			<div class="box-header label-primary">
				<b>{{tr('automatic_subscribers').' - '.Setting::get('currency').$amount}}</b>

				<ul class="admin-action btn btn-default pull-right" style="margin-right: 20px;">

					<li class="dropdown">
						<a class="dropdown-toggle" data-toggle="dropdown" href="#">
							{{tr('export')}} <span class="caret"></span>
						</a>
						<ul class="dropdown-menu">
							<li role="presentation">
								<a role="menuitem" tabindex="-1" href="{{route('admin.automatic.subscribers.export', ['format' => 'xlsx'])}}">
									<span class="text-red"><b>{{tr('excel_sheet')}}</b></span>
								</a>
							</li>

							<li role="presentation">
								<a role="menuitem" tabindex="-1" href="{{route('admin.automatic.subscribers.export' , ['format' => 'csv'])}}">
									<span class="text-blue"><b>{{tr('csv')}}</b></span>
								</a>
							</li>
						</ul>
					</li>
				</ul>
			</div>

			<div class="box-body table-responsive">

				@if(count($payments) > 0)




				<table id="example1" class="table table-bordered table-striped">

					<thead>
						<tr>
							<th>{{tr('id')}}</th>
							<th width="132px">{{tr('username')}}</th>
							<th width="132px">{{tr('subscription_name')}}</th>
							<th>{{tr('amount')}}</th>
							<th>{{tr('expiry_date')}}</th>
							<th>{{tr('action')}}</th>
						</tr>
					</thead>

					<tbody>

						@foreach($payments as $i => $payment)

						<tr>

							<td>{{$i+1}}</td>

							<td>@if($payment->user_name)<a href="{{route('admin.users.view' ,['user_id' =>  $payment->user_id])}}"> {{($payment->user_name) ? $payment->user_name : ''}} </a>@endif</td>
							<td>
								@if($payment->subscription_name)
								<a href="{{route('admin.subscriptions.view' , $payment->subscription_id)}}" target="_blank"> {{($payment->subscription_name) ? $payment->subscription_name : ''}} </a>
								@endif
							</td>

							<td>{{formatted_amount($payment->amount)}}</td>
							<td>
								{{common_date($payment->expiry_date,Auth::guard('admin')->user()->timezone,'Y-m-d H:i:s')}}
							</td>
							<td class="text-center width_css">
								<a data-toggle="modal" data-target="#{{$payment->id}}_cancel_subscription" class="btn btn-sm btn-danger" style="float:left">{{tr('cancel_subscription')}}</a>
							</td>
						</tr>

						<div class="modal fade error-popup" id="{{$payment->id}}_cancel_subscription" role="dialog">

							<div class="modal-dialog">

								<div class="modal-content">

									<form method="post" action="{{route('admin.automatic.subscription.cancel', ['id'=>$payment->user_id])}}">

										<div class="modal-body">

											<div class="media">

												<div class="media-body">

													<h4 class="media-heading">{{tr('reason')}} *</h4>


													<textarea rows="5" name="cancel_reason" id='cancel_reason' required style="width: 100%"></textarea>

												</div>

											</div>

											<div class="text-right">

												<br>

												<button type="submit" class="btn btn-primary top">{{tr('submit')}}</button>

											</div>

										</div>

									</form>

								</div>

							</div>

						</div>

						@endforeach

					</tbody>

				</table>

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