@extends('layouts.admin')

@section('title',tr('view_coupon'))

@section('content-header',tr('coupons'))

@section('breadcrumb')

	<li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>

	<li><a href="{{route('admin.coupon.list')}}"><i class="fa fa-gift"></i>{{tr('coupons')}}</a></li>

	<li class="active"><i class="fa fa-eye"></i>{{tr('view_coupon')}}</li>

@endsection

@section('content')
	
	@include('notification.notify')

	<div class="row">

		<div class="col-md-6">

			<div class="box box-primary">

				<div class="box-header label-primary">
					<b>@yield('title')</b>

					<a href="{{route('admin.edit.coupons',$view_coupon->id)}}" class="btn btn-warning pull-right"><i class="fa fa-pencil"></i> {{tr('edit')}}</a>

				</div>

				<div class="box box-body">
					<strong>{{tr('title')}}</strong>
					<h5 class="pull-right">{{$view_coupon->title}}</h5><hr>

					<strong>{{tr('coupon_code')}}</strong>
					<h4 class="pull-right" style="border: 2px solid #20bd99">{{$view_coupon->coupon_code}}</h4><hr>

					<strong>{{tr('amount_type')}}</strong>
						@if($view_coupon->amount_type == 0)
						<span class="label label-primary pull-right">{{tr('percentage')}}</span>
						@else
						<span class="label label-primary pull-right">{{tr('absoulte')}}</span>
						@endif
					<hr>
					<strong>{{tr('amount')}}</strong>
						@if($view_coupon->amount_type == 0)
						<span class="label label-primary pull-right">{{$view_coupon->amount}} % </span>
						@else
						<span class="label label-primary pull-right">{{formatted_amount($view_coupon->amount)}}</span>
						@endif
					<hr>
					<strong>{{tr('expiry_date')}}</strong>

						<h5 class="pull-right">
							
							 {{date('d M y', strtotime($view_coupon->expiry_date))}} 
							
						</h5>
					<hr>
					<strong>{{tr('no_of_users_limit')}}</strong>

						<h5 class="pull-right">
							
							 {{$view_coupon->no_of_users_limit}} 
							
						</h5>
					<hr>
					<strong>{{tr('per_users_limit')}}</strong>

						<h5 class="pull-right">
							
							 {{$view_coupon->per_users_limit}} 
							
						</h5>
					<hr>
					<strong>{{tr('status')}}</strong>
						@if($view_coupon->status == 0)
						<span class="label label-warning pull-right">{{tr('declined')}}</span>
						@else
						<span class="label label-success pull-right">{{tr('approved')}}</span>
						@endif
					
					<hr>
					<strong>{{tr('created_at')}}</strong>
					<h5 class="pull-right">{{common_date($view_coupon->created_at,Auth::guard('admin')->user()->timezone)}}</h5>
					<hr>
					<strong>{{tr('updated_at')}}</strong>
					<h5 class="pull-right">{{common_date($view_coupon->updated_at,Auth::guard('admin')->user()->timezone)}}</h5>
					
					@if($view_coupon->description == '')

					@else
					<hr>
					<strong>{{tr('description')}} </strong>
					<p class="pull-right"><?php echo $view_coupon->description ?></p>
					@endif

				</div>
			</div>
		</div>
	</div>


@endsection