@extends('layouts.admin')

@section('title', tr('subscriptions'))

@section('content-header')

<span style="color: green;">{{tr('subscriptions')}}</span> - {{$user ?  $user->name : ""}}

@endsection

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li><a href="{{route('admin.users.index')}}"><i class="fa fa-user"></i> {{tr('users')}}</a></li>
    <li class="active"><i class="fa fa-key"></i> {{tr('subscriptions')}}</li>
@endsection

@section('after-styles')

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
	min-height: 10em !important;
	max-height: 10em !important;
	overflow: scroll !important;
	margin-bottom: 10px !important;
}

</style>

@endsection

@section('content')

	@include('notification.notify')

	<div class="row">
        <div class="col-xs-12">
          	<div class="box">
	            <div class="box-body">

	            	@if(count($payments) > 0)

		              	<table id="example1" class="table table-bordered table-striped">

							<thead>
							    <tr>
							      <th>{{tr('id')}}</th>
							      <th>{{tr('username')}}</th>
							      <th>{{tr('subscription')}}</th>
							      <th>{{tr('payment_id')}}</th>
							      <th>{{tr('amount')}}</th>
							      <th>{{tr('expiry_date')}}</th>
							      <th>{{tr('reason')}}</th>
							      <th>{{tr('action')}}</th>
							     
							    </tr>
							</thead>

							<tbody>

								@foreach($payments as $i => $payment)

								    <tr>
								      	<td>{{$i+1}}</td>
								      	<td><a href="{{route('admin.users.view' , ['user_id' => $payment->user_id])}}"> {{($payment->user) ? $payment->user->name : tr('user_not_available')}} </a></td>
								      	<td>
								      		@if($payment->subscription)
								      		<a href="{{route('admin.subscriptions.view' , $payment->subscription->id ?? '')}}">{{$payment->subscription ? $payment->subscription->title : tr('subscription_not_available')}}</a>
								      		@endif
								      	</td>
								      	<td>{{$payment->payment_id}}</td>	

								      	<td>{{formatted_amount($payment->amount)}} </td>
								      	<td>
								      	{{common_date($payment->expiry_date , Auth::guard('admin')->user()->timezone)}}</td>
								      	<td>{{$payment->cancel_reason?:tr('not_available')}}</td>
								      	
								      	<td class="text-center">

								      		@if($i == 0 && !$payment->is_cancelled) 
								      		<a data-target="#{{$payment->id}}_cancel_subscription" class="pull-right btn btn-sm btn-success" data-toggle="modal" >{{tr('cancel_subscription')}}</a>

								      		@else

								      			@if($payment->is_cancelled)

								      			<?php $enable_subscription_notes = tr('enable_subscription_notes') ; ?>
								      			
								      				<a onclick="return confirm('{{$enable_subscription_notes}}')" href="{{route('admin.automatic.subscription.enable', ['id'=>$payment->user_id])}}" class="pull-right btn btn-sm btn-success">{{tr('enable_subscription')}}</a>


								      			@else

								      				-
								      				
								      			@endif

								      		@endif

								      	</td>

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

								    </tr>					
								@endforeach
							</tbody>
						</table>

						<div>
							
						</div>
					@else
						<h3 class="no-result">{{tr('no_subscription_found')}}</h3>
					@endif

	            </div>
          	</div>
        </div>
    </div>

	<div class="row">

		<div class="col-md-12">

			<div class="row">

				@if(count($subscriptions) > 0)

					@foreach($subscriptions as $s => $subscription)

						<div class="col-md-4 col-lg-4 col-sm-6 col-xs-12 scroller" >

							<div class="thumbnail">

								<!-- <img alt="{{$subscription->title}}" src="{{$subscription->picture ?  $subscription->picture : asset('common/img/landing-9.png')}}" class="subscription-image" /> -->
								<div class="caption">

									<h4 >	
										{{strip_tags($subscription->title)}}
									</h4>
									<hr>
									<div class="subscription-desc">
										<?php echo $subscription->description; ?>
									</div>

									<br>

									<p>
										<span class="btn btn-danger pull-left">{{ Setting::get('currency')}} {{$subscription->amount}} / {{$subscription->plan}} M</span>

										<a onclick="return confirm(&quot;{{$subscription->title}} - {{tr('you_want_choose_plan')}}&quot;);" href="{{route('admin.subscription.save' , ['s_id' => $subscription->id, 'u_id'=>$id])}}" class="btn btn-success pull-right">{{tr('choose')}}</a>

									</p>
									<br>
									<br>
								</div>
							
							</div><br>
						
						</div>

					@endforeach

				@else

				<h3 class="no-result">{{tr('no_result_found')}}</h3>

				@endif
				
			</div>
		</div>
	</div>

@endsection