@extends( 'layouts.user' )

@section( 'styles' )

<link rel="stylesheet" type="text/css" href="{{asset('streamtube/css/custom-style.css')}}"> 

<style>
	
	.list-style li {
		margin: 10px auto;
		text-align: center;
	}
</style>

@endsection

@section('content')

<div class="y-content">

	<div class="row content-row">

		@include('layouts.user.nav')

		<div class="page-inner col-sm-9 col-md-10">

			@include('notification.notify')

			<div class="invoice">

				<div class="row"> 

					<div class="col-xs-12 col-sm-12 col-md-5 col-lg-4">

						<img src="{{asset('payment-failure.png')}}">

					</div>

					<div class="col-xs-12 col-sm-12 col-md-7 col-lg-8">

						<div class="text-center" style="margin-top: 10%">

							<h4>{{tr('payment_failed')}}</h4>

							<p>
								{{tr('payment_cause_reason')}}
							</p>

							@if($paypal_error)

								<span style="color: red">{{$paypal_error}}</span>

							@else

							<ul class="list-style">
								<li><span>* {{tr('insufficient_funds')}}</span></li>
								<li><span>* {{tr('payment_configuration')}}</span></li>
								<li><span>* {{tr('unexcepted_error')}}</span></li>
							</ul>

							@endif

							<div class="clearfix"></div>

							<a href="{{url('/')}}" class="btn btn-primary">{{tr('go_home')}}</a>

						</div>
						
					</div>

				</div>
			</div>

		</div>

	</div>

</div>

@endsection