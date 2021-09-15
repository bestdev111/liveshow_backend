@extends('layouts.admin')

@section('title', tr('view_subscription'))

@section('content-header', tr('subscription'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li><a href="{{route('admin.subscriptions.index')}}"><i class="fa fa-key"></i> {{tr('subscriptions')}}</a></li>
    <li class="active"><i class="fa fa-eye"></i>&nbsp;{{tr('view_subscription')}}</li>
@endsection

@section('content')

	<style type="text/css">
		.timeline::before {
		    content: '';
		    position: absolute;
		    top: 0;
		    bottom: 0;
		    width: 0;
		    background: #fff;
		    left: 0px;
		    margin: 0;
		    border-radius: 0px;
		}
	</style>

	@include('notification.notify')
	
	<div class="row">

		<div class="col-md-6 col-md-offset-3">

			<div class="box box-primary">

                <div class="box-header label-primary">
					<div class="pull-left">
						<h3 class="box-title"><b>@yield('title')</b></h3>
					</div>

					@if($data->status != -1)
						<div class="pull-right">
			      			<a href="{{route('admin.subscriptions.status' , $data->id)}}" class="btn btn-sm {{$data->status ? 'btn-warning' : 'btn-success'}}">
			      				@if($data->status) 
	      							<i class="fa fa-close"></i>&nbsp;&nbsp;{{tr('decline')}}
	      						@else 
	      							<i class="fa fa-check"></i>&nbsp;&nbsp;{{tr('approve')}}
	      						@endif
	      					</a>
							<a href="{{route('admin.subscriptions.edit',$data->id)}}" class="btn btn-sm btn-warning"><i class="fa fa-pencil"></i> {{tr('edit')}}</a>
						</div>
					@endif
					<div class="clearfix"></div>
				</div>

				<div class="box-body">

					<strong><i class="fa fa-book margin-r-5"></i> {{tr('title')}}</strong>

					<p class="text-muted pull-right">{{$data->title}}</p>

					<hr>

					
					<strong><i class="fa fa-book margin-r-5"></i> {{tr('subscribers')}}</strong>
					<a href="{{route('admin.subscription.payments',['subscription_id'=>$data->id])}}">
					<p class="text-muted pull-right"><span class="label label-success btn_highlight_css"><b>
					
						{{$data->subscribers_count}}
					</p>
					</a>

					<hr>


					<strong><i class="fa fa-calendar margin-r-5"></i> {{tr('plan')}}</strong>
					

					<p class="pull-right">
					<span class="label label-success" style="padding: 5px 10px;margin: 5px;font-size: 18px"><b >{{$data->plan}}</b></span>
					
					</p>

					<hr>

					<strong><i class="fa fa-money margin-r-5"></i> {{tr('amount')}}</strong>

					<p class="pull-right"><span class="label label-danger" style="padding: 5px 10px;margin: 5px;font-size: 18px"><b>{{formatted_amount($data->amount)}}</b></span>
					</p>
					<hr>
					<strong><i class="fa fa-book margin-r-5"></i> {{tr('popular')}}</strong>

					<p class="pull-right">
					<span class="label label-success" style="padding: 5px 10px;margin: 5px;font-size: 18px"><b>{{$data->popular_status}}</b></span>
					</p>

					<hr>
					<strong><i class="fa fa-calendar margin-r-5"></i> {{tr('created_at')}}</strong>
					<span class="pull-right"><b>{{common_date($data->created_at,Auth::guard('admin')->user()->timezone)}}</b></span>
					<br>
					<br>

					<hr>
					<strong><i class="fa fa-calendar margin-r-5"></i> {{tr('updated_at')}}</strong>

				
					<span class="pull-right"><b>{{common_date($data->updated_at,Auth::guard('admin')->user()->timezone)}}</b></span>
					

					<hr>
				    <strong><i class="fa fa-book margin-r-5"></i> {{tr('description')}}</strong>

					<p class="text-muted pull-right description_block">{{$data->description}}</p>

					<!-- <hr> -->


				</div>

			</div>
			<!-- /.box -->
		</div>

    </div>

@endsection


