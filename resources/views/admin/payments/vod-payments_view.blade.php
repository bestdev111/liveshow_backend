@extends('layouts.admin')

@section('title', tr('vod_payments'))

@section('content-header', tr('vod_payments'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li><a href="{{route('admin.subscriptions.index')}}"><i class="fa fa-key"></i> {{tr('subscriptions')}}</a></li>
    <li class="active"><i class="fa fa-pencil"></i> {{tr('vod_payments')}} {{tr('view')}}</li>
@endsection

@section('content')

	@include('notification.notify')

    <section class="invoice">
      <div class="row">
        <div class="col-xs-12">
          <h2 class="page-header">
            <i class="fa fa-video-camera"></i> {{$vod_payments->vodVideo ? $vod_payments->vodVideo->title : tr('not_available') }}
          </h2>
        </div>
      </div>

      <div class="row">
        
        <!-- /.col -->
        <div class="col-xs-6">

          <div class="table-responsive">
        
            <table class="table">
             
              <tr>
              	<th>{{ tr('payment_id')}}</th>
              	<td>{{$vod_payments->payment_id}}</td>
              </tr>

              <tr>
              	<th>{{ tr('amount')}}</th>
              	<td>{{formatted_amount($vod_payments->amount)}}</td>
              </tr>
              <tr>
              	<th>{{ tr('admin_amount')}}</th>
              	<td>{{formatted_amount($vod_payments->admin_amount)}}</td>
              
              </tr>
              <tr>
              	<th>{{ tr('user_amount')}}</th>
              	<td>{{formatted_amount($vod_payments->user_amount)}}</td>
              
              </tr>
              <tr>
              	<th>{{ tr('payment_mode')}}</th>
              	<td class="text-uppercase">{{$vod_payments->payment_mode}}</td>
              
              </tr>
              <tr>
              	<th>{{ tr('expiry_date')}}</th>
              	<td>{{$vod_payments->expiry_date}}</td>
              
              </tr>
              <tr>
              	<th>{{ tr('ppv_date')}}</th>
              	<td>{{$vod_payments->ppv_date}}</td>
              
              </tr>
              <tr>
              	<th>{{ tr('reason')}}</th>
              	<td>{{$vod_payments->reason ?: tr('not_available')}}</td>
              
              </tr>
              <tr>
              	<th>{{ tr('is_watched')}}</th>
              	<td>{{$vod_payments->is_watched}}</td>
              
              </tr>
              <tr>
              	<th>{{ tr('status')}}</th>
              	<td>
              	@if($vod_payments->amount > 0)
					<span class="label label-success">{{tr('paid')}}</span>
				@else
					<span class="label label-danger">{{tr('not_paid')}}</span>
				@endif
				</td>
              
              </tr>	
        
        	</table>
        
          </div>
        
        </div>

        <div class="col-xs-6">

          <div class="table-responsive">
            
            <table class="table">

        	<tr>
        		<th>{{ tr('is_coupon_applied')}}</th>
        		<td>@if($vod_payments->is_coupon_applied)
					<span class="label label-success">{{tr('yes')}}</span>
					@else
					<span class="label label-danger">{{tr('no')}}</span>
					@endif
				</td>
        	
        	</tr>
        	<tr>
        		<th>{{ tr('coupon_code')}}</th>
        		<td>{{$vod_payments->coupon_code ?: tr('not_available')}}</td>
        	
        	</tr>
              <tr>
              	<th>{{ tr('coupon_amount')}}</th>
              	<td>{{formatted_amount($vod_payments->coupon_amount)}}</td>
              
              </tr>
              <tr>
              	<th>{{ tr('ppv_amount')}}</th>
              	<td>{{formatted_amount($vod_payments->ppv_amount)}}</td>
              
              </tr>
              <tr>
              	<th>{{ tr('coupon_reason')}}</th>
              	<td>{{$vod_payments->coupon_reason ?: tr('not_available')}}</td>
              
              </tr>
              <tr>
              	<th>{{ tr('coupon_amount')}}</th>
              	<td>{{formatted_amount($vod_payments->coupon_amount)}}</td>
              
              </tr>
              <tr>
              	<th>{{ tr('ppv_amount')}}</th>
              	<td>{{formatted_amount($vod_payments->ppv_amount)}}</td>
              
              </tr>
              <!-- <tr>
              	<th>{{ tr('coupon_reason')}}</th>
              	<td>{{$vod_payments->coupon_reason ?: tr('not_available')}}</td>
              
              </tr> -->
              <tr>
              	<th>{{ tr('created_at')}}</th>
              	<td>{{ common_date($vod_payments->created_at, Auth::guard('admin')->user()->timezone) }}</td>              
              </tr>
              <tr>
              	<th>{{ tr('updated_at')}}</th>
              	<td>{{ common_date($vod_payments->updated_at,  Auth::guard('admin')->user()->timezone) }}</td>
              
              </tr>
            
            </table>
          
          </div>
        
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->

@endsection