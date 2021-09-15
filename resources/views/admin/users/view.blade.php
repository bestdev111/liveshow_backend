@extends('layouts.admin')

@section('title', tr('view_user'))

@section('content-header', tr('users'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li><a href="{{route('admin.users.index')}}"><i class="fa fa-user"></i> {{tr('users')}}</a></li>
    <li class="active"><i class="fa fa-eye"></i> {{tr('view_user')}}</li>
@endsection

@section('content')

	@include('notification.notify')

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

	<div class="row">
		<div class="col-md-12">
          <!-- Widget: user widget style 1 -->
          <div class="box box-widget widget-user">
            <!-- Add the bg color to the header using any of the bg-* classes -->
            <div class="widget-user-header bg-black" style="background: url({{$data->cover ? $data->cover : url('cover.jpg')}}) center center;">
              <h3 class="widget-user-username">{{$data->name}}</h3>
              <h5 class="widget-user-desc">{{tr('user')}}</h5>
            </div>
            <div class="widget-user-image">
              <img class="img-circle" src="@if($data->picture) {{$data->picture}} @else {{asset('placeholder.png')}} @endif" alt="{{$data->name}}">
            </div>
            <div class="box-footer">
              <div class="row">
                <div class="col-sm-4 border-right">
                  <div class="description-block">
                    <h5 class="description-header">{{$data->get_followers_count}}</h5>
                    <span class="description-text">{{tr('followers')}}</span>
                  </div>
                  <!-- /.description-block -->
                </div>
                <!-- /.col -->
                <div class="col-sm-4 border-right">
                  <div class="description-block">
                    <h5 class="description-header">{{$data->get_live_videos_count}}</h5>
                    <span class="description-text">{{tr('videos')}}</span>
                  </div>
                  <!-- /.description-block -->
                </div>
                <!-- /.col -->
                <div class="col-sm-4">
                  <div class="description-block">
                    <h5 class="description-header">{{formatted_amount($data->total_user_amount)}}</h5>
                    <span class="description-text">{{tr('revenue')}}</span>
                  </div>
                  <!-- /.description-block -->
                </div>
                <!-- /.col -->
              </div>
              <!-- /.row -->
            </div>
           
          </div>
          <!-- /.widget-user -->
        </div>

	</div>

	<div class="row">
  	
      	<div class="col-md-3">
          	<div class="box box-widget widget-user-2">
	            <!-- Add the bg color to the header using any of the bg-* classes -->
	            <div class="widget-user-header bg-yellow" style="padding: 1px 10px">
	              <h3 class="">{{tr('profile_info')}}</h3>
	            </div>
	            <div class="box-footer no-padding">
	              <ul class="nav nav-stacked">
	              	<li><a href="#">{{tr('is_verified')}} @if($data->is_verified)

					      			<span class="pull-right badge bg-green">{{tr('yes')}}</span>

					      		@else
					      			<span class="pull-right badge bg-red">{{tr('no')}}</span>
					      		@endif</a></li>
	              	<li><a href="#">{{tr('status')}} @if($data->status)

					      			<span class="pull-right badge bg-green">{{tr('approved')}}</span>

					      		@else
					      			<span class="pull-right badge bg-red">{{tr('pending')}}</span>
					      		@endif</a></li>
	              	<li><a href="#">{{tr('subscribed_user')}} @if($data->user_type)

					      			<span class="pull-right badge bg-green">{{tr('yes')}}</span>

					      		@else
					      			<span class="pull-right badge bg-red">{{tr('no')}}</span>
					      		@endif</a></li>
	              	<li><a href="#">{{tr('is_content_creator')}} @if($data->is_content_creator)

					      			<span class="pull-right badge bg-green">{{tr('yes')}}</span>

					      		@else
					      			<span class="pull-right badge bg-red">{{tr('no')}}</span>
					      		@endif</a></li>
					<li><a href="#">{{tr('login_status')}} @if($data->login_status)

					      			<span class="pull-right badge bg-green">{{tr('yes')}}</span>

					      		@else
					      			<span class="pull-right badge bg-red">{{tr('no')}}</span>
					      		@endif</a></li>
					<li><a href="#">{{tr('push_status')}} @if($data->push_status)

					      			<span class="pull-right badge bg-green">{{tr('on')}}</span>

					      		@else
					      			<span class="pull-right badge bg-red">{{tr('off')}}</span>
					      		@endif</a></li>
	                <li><a href="#">{{tr('followings')}} <span class="pull-right badge bg-blue">{{$data->get_following_count}}</span></a></li>
	                <li><a href="#">{{tr('blockers')}} <span class="pull-right badge bg-aqua">{{$data->get_block_users_count}}</span></a></li>
	                <li><a href="#">{{tr('paid_videos')}} <span class="pull-right badge bg-warning">{{$data->get_paymentvideos_count}}</span></a></li>

	                <li><a href="#">{{tr('vod_videos')}} <span class="pull-right badge bg-warning">{{$data->get_vodvideos_count}}</span></a></li>
	              </ul>
	              @if(Setting::get('admin_delete_control'))
	              	<button class="btn btn-primary btn-block disabled"><b>{{tr('edit_user')}}</b></button>
	              @else
	              	 
	              	 <a href="{{route('admin.users.edit', array('id' => $data->id))}}" class="btn btn-primary btn-block"><b>{{tr('edit_user')}}</b></a>
	              @endif
	            </div>
	          </div>
      	</div>

      	<div class="col-md-9">
          <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
              <li class="active"><a href="#activity" data-toggle="tab">{{tr('basic_info')}}</a></li>
              <li><a href="#user_followers" data-toggle="tab">{{tr('followers')}}</a></li>
              <li><a href="#user_followings" data-toggle="tab">{{tr('followings')}}</a></li>
              <li><a href="#user_blockers" data-toggle="tab">{{tr('blockers')}}</a></li>
              <li><a href="#user_videos" data-toggle="tab">{{tr('live_videos')}}</a></li>
              <li><a href="#user_vod_videos" data-toggle="tab">{{tr('vod_videos')}}</a></li>
            </ul>
            <div class="tab-content">
              <div class="active tab-pane" id="activity">
                	
                	<h4 class="h4-header">
	                    {{tr('basic_info')}}
	                </h4>
		            <table class="table table-striped">
		            	<tr>
		            		<th>{{tr('username')}}</th>
		            		<td>{{$data->name}}</td>
		            	</tr>
		            	<tr>
		            		<th>{{tr('email')}}</th>
		            		<td>{{$data->email}}</td>
		            	</tr>
		            	<tr>
		            		<th>{{tr('paypal_email')}}</th>
		            		<td>{{$data->paypal_email ?: tr('not_available')}}</td>
		            	</tr>
		            	
		            	<tr>
		            		<th>{{tr('login_type')}}</th>
		            		<td>{{$data->login_by ? ucfirst($data->login_by) : "-"}}</td>
		            	</tr>

		            	@if(!in_array($data->login_by, ['manual']))

		            	<tr>
		            		<th>{{tr('social_unique_id')}}</th>
		            		<td>{{$data->social_unique_id ? $data->social_unique_id : "-"}}</td>
		            	</tr>

		            	@endif

		            	<tr>
		            		<th>{{tr('device_type')}}</th>
		            		<td>{{$data->device_type ? ucfirst($data->device_type) : "-"}}</td>
		            	</tr>

		            	<tr>
		            		<th>{{tr('register_type')}}</th>
		            		<td>{{$data->register_type ? ucfirst($data->register_type) : "-"}}</td>
		            	</tr>

		            	<tr>
		            		<th>{{tr('payment_mode')}}</th>
		            		<td>{{$data->payment_mode ? ucfirst($data->payment_mode) : tr('cod')}}</td>
		            	</tr>

		            	<tr>
		            		<th>{{tr('no_of_days')}}</th>
		            		<td>{{$data->no_of_days ? abs($data->no_of_days) : "0"}}</td>
		            	</tr>

		            	<tr>
		            		<th>{{tr('expiry_date')}}</th>
		            		<td>{{$data->no_of_days > 0 ? common_date($data->expiry_date,Auth::guard('admin')->user()->timezone) : '0'}}</td>
		            	</tr>

		            	<tr>
		            		<th>{{tr('joined')}}</th>
		            		<td>{{common_date($data->created_at,Auth::guard('admin')->user()->timezone)}}</td>
		            	</tr>

		            	<tr>
		            		<th>{{tr('updated')}}</th>
		            		<td>{{common_date($data->updated_at,Auth::guard('admin')->user()->timezone)}}</td>
		            	</tr>

		            	<tr>
		            		<th>{{tr('description')}}</th>
		            		<td>{{($data->description)?$data->description : tr('not_available')}}</td>
		            	</tr>

		            	<tr>

							<th>{{tr('action')}}</th>	
							
							<td>

			            		@if($data->is_content_creator)

									<a href="{{route('admin.subscriptions.plans' , $data->id)}}">
										<span class="text-green"><b><i class="fa fa-eye"></i> {{tr('subscription_plans')}}</b></span>
									</a>

								@else

									<a href="{{route('admin.become.creator' , ['id'=>$data->id])}}" onclick="return confirm(&quot;{{ tr('become_a_creator_confirmation') }}&quot;)" >	
										<span class="text-green"><b><i class="fa fa-eye"></i> {{tr('become_a_creator')}}</b></span>
									</a>
									
								@endif

							</td>
						</tr>
		            	
		            </table>

		            @if($data->is_content_creator)

		            <h4 class="h4-header">
	                    {{tr('redeems')}}
	                </h4>

	                 <table class="table table-striped">
		            	<tr>
		            		<th>{{tr('total_admin_amount')}}</th>
		            		<td> {{formatted_amount($data->total_admin_amount)}}</td>
		            	</tr>
		            	<tr>
		            		<th>{{tr('total_user_amount')}}</th>
		            		<td>{{formatted_amount($data->total_user_amount)}}</td>
		            	</tr>
		            	<tr>
		            		<th>{{tr('total')}}</th>
		            		<td>{{formatted_amount($data->userRedeem ? $data->userRedeem->total : '0.00')}}</td>
		            	</tr>
		            	<tr>
		            		<th>{{tr('wallet_balance')}}</th>
		            		<td>{{formatted_amount($data->userRedeem ? $data->userRedeem->remaining: '0.00')}}</td>
		            	</tr>
		            	<tr>
		            		<th>{{tr('paid_amount')}}</th>
		            		<td>{{formatted_amount($data->userRedeem ? $data->userRedeem->paid: '0.00')}}</td>
		            	</tr>
		            </table>

		            @endif

              </div>
              <!-- /.tab-pane -->
              <div class="tab-pane" id="user_followers">
                <blockquote>
	                <p>{{tr('followers_notes')}}</p>
	                <small>{{tr('to_view_more')}} <cite><a href="{{route('admin.users.followers', $data->id)}}" target="_blank">{{tr('click_here')}}</a></cite></small>
	            </blockquote>

           		<table class="table table-bordered table-striped datatable-withoutpagination">
					<thead>
					    <tr>
					      <th>{{tr('id')}}</th>
					      <th>{{tr('username')}}</th>
					      <th>{{tr('email')}}</th>
					    </tr>
					</thead>

					<tbody>

						@foreach($followers as $i => $follower)

						    <tr>
						      	<td>{{$i+1}}</td>
						      	<td><a href="{{route('admin.users.view', ['user_id' => $follower->follower_id])}}" target="_blank">{{$follower->name}}</a></td>
						      	<td>{{$follower->email}}</td>
						    </tr>					

						@endforeach

					</tbody>
				</table>
              </div>
              <!-- /.tab-pane -->

              <div class="tab-pane" id="user_followings">
                <blockquote>
	                <p>{{tr('followings_notes')}}</p>
	                <small>{{tr('to_view_more')}} <cite><a href="{{route('admin.users.followings', $data->id)}}" target="_blank">{{tr('click_here')}}</a></cite></small>
	            </blockquote>

           		<table class="table table-bordered table-striped datatable-withoutpagination">
					<thead>
					    <tr>
					      <th>{{tr('id')}}</th>
					      <th>{{tr('username')}}</th>
					      <th>{{tr('email')}}</th>
					    </tr>
					</thead>

					<tbody>

						@foreach($followings as $i => $following)

						    <tr>
						      	<td>{{$i+1}}</td>
						      	<td><a href="{{route('admin.users.view', ['user_id' => $following->follower_id])}}" target="_blank">{{$following->name}}</a></td>
						      	<td>{{$following->email}}</td>
						    </tr>					

						@endforeach

					</tbody>
				</table>
              </div>
              <!-- /.tab-pane -->

              <div class="tab-pane" id="user_blockers">
                <blockquote>
	                <p>{{tr('blockers_notes')}}</p>
	                <small>{{tr('to_view_more')}} <cite><a href="{{route('admin.users.block_list', $data->id)}}" target="_blank">{{tr('click_here')}}</a></cite></small>
	            </blockquote>

           		<table class="table table-bordered table-striped datatable-withoutpagination">
					<thead>
					    <tr>
					      <th>{{tr('id')}}</th>
					      <th>{{tr('username')}}</th>
					      <th>{{tr('email')}}</th>
					    </tr>
					</thead>

					<tbody>

						@foreach($blockers as $i => $blocker)

						    <tr>
						      	<td>{{$i+1}}</td>
						      	<td><a href="{{route('admin.users.view', ['user_id' => $blocker->block_user_id])}}" target="_blank">{{$blocker->name}}</a></td>
						      	<td>{{$blocker->email}}</td>
						    </tr>					

						@endforeach

					</tbody>
				</table>
              </div>
              <!-- /.tab-pane -->

              <div class="tab-pane" id="user_videos">
                <blockquote>
	                <p>{{tr('videos_notes')}}</p>
	                <small>{{tr('to_view_more')}} <cite><a href="{{route('admin.videos.videos_list', ['user_id' => $data->id])}}" target="_blank">{{tr('click_here')}}</a></cite></small>
	            </blockquote>

           		<table class="table table-bordered table-striped datatable-withoutpagination">
					<thead>
					    <tr>
					      	<th>{{tr('id')}}</th>
					      	<th>{{tr('title')}}</th>
					      	<th>{{tr('video_type')}}</th>
					      	<th>{{tr('payment')}}</th>
					      	<th>{{tr('streaming_status')}}</th>
					      	<th>{{tr('streamed_at')}}</th>
					      	<th>{{tr('viewer_count')}}</th>
					    </tr>
					</thead>

					<tbody>

						@foreach($videos as $i => $video)

						    <tr>
						      	<td>{{$i+1}}</td>

						      	<td><a href="{{route('admin.videos.view' , ['video_id' =>$video->id])}}" target="_blank">{{$video->title}}</a></td>

						      	<td>
						      			
						      		@if($video->type == TYPE_PUBLIC)

						      			<label class="text-green"><b>{{ucfirst(TYPE_PUBLIC)}}</b></label>

						      		@else
						      			<label class="text-navyblue"><b>{{ucfirst(TYPE_PRIVATE)}}</b></label>
						      		@endif

						      	</td>

						      	<td>
						      			
						      		@if($video->payment_status)

						      			<label class="text-red">{{tr('payment')}}</label>

						      		@else
						      			<label class="text-yellow">{{tr('free')}}</label>
						      		@endif

						      	</td>

						      	<td>
						      		@if($video->is_streaming)

                                        @if(!$video->status)

                                        <label class="text-green"><b>{{tr('video_call_started')}}</b></label>

                                        @else

                                            <label class="text-green"><b>{{tr('video_call_ended')}}</b></label>

                                        @endif

                                    @else

                                        <label class="text-navyblue"><b>{{tr('video_call_initiated')}}</b></label>

                                    @endif
						      	</td>

						      	<td>{{common_date($video->created_at, Auth::guard('admin')->user()->timezone)}}</td>

						      	<td>{{$video->viewer_cnt}}</td>

						    </tr>					

						@endforeach

					</tbody>
				</table>
              </div>

               <div class="tab-pane table-responsive" id="user_vod_videos">
                <blockquote>
	                <p>{{tr('vod_notes')}}</p>
	                <small>{{tr('to_view_more')}} <cite><a href="{{route('admin.vod-videos.index', $data->id)}}" target="_blank">{{tr('click_here')}}</a></cite></small>
	            </blockquote>

           		<table class="table table-bordered table-striped datatable-withoutpagination">
					<thead>
					    <tr>
					      <th>{{tr('id')}}</th>
					      <th>{{tr('title')}}</th>
					      	<th>{{tr('ppv_status')}}</th>
					      	<th>{{tr('ppv_amount')}}</th>
					      	<th>{{tr('total_amount')}}</th>
					      	<th>{{tr('admin_amount')}}</th>
					      	<th>{{tr('user_amount')}}</th>
					      	 <th>{{tr('uploaded_by')}}</th>
 							<th>{{tr('user_status')}}</th>
					      	<th>{{tr('admin_status')}}</th>
					    </tr>
					</thead>

					<tbody>

						@foreach($vod_videos as $i => $vod_video)

						    <tr>
						      	<td>{{$i+1}}</td>
						      		<td><a href="{{route('admin.vod-videos.view',['video_id' =>$vod_video->vod_id])}}">{{$vod_video->title}}</a></td>

						      	<td>
						      			
						      		@if($vod_video->amount != 0)

						      			<span class="label label-success">{{tr('yes')}}</span>

						      		@else
						      			<span class="label label-danger">{{tr('no')}}</span>
						      		@endif

						      	</td>

						      	<td>
						      			
						      		{{formatted_amount($vod_video->amount)}}

						      	</td>

						      	<td>
						      		{{formatted_amount($vod_video->admin_amount+$vod_video->user_amount)}}
						      	</td>

						      	<td>
						      		{{formatted_amount($vod_video->admin_amount)}}
						      	</td>

						      	<td>
									{{formatted_amount($vod_video->user_amount)}}
						      	</td>					      	

						      	<td class="text-capitalize">
						      		{{$vod_video->created_by ? $vod_video->created_by : 'User'}}
						      	</td>

						      	<td>
						      		@if($vod_video->status)

						      			<span class="label label-success">{{tr('approved')}}</span>

							      	@else

							      		<span class="label label-warning">{{tr('pending')}}</span>

						      		@endif
						      	</td>

						      	<td>
						      		@if($vod_video->admin_status)

						      			<span class="label label-success">{{tr('approved')}}</span>

							      	@else

							      		<span class="label label-warning">{{tr('pending')}}</span>

						      		@endif
						      	</td>

						    </tr>					

						@endforeach

					</tbody>
				</table>
              </div>
              <!-- /.tab-pane -->
            </div>
            <!-- /.tab-content -->
          </div>
          <!-- /.nav-tabs-custom -->
		</div>

  	</div>

   	<?php /* <li>
    	
    	<a href="{{route('admin.users.redeems' , $data->id)}}" class="btn btn-success check-redeem" style="background-color: #00a65a !important; color: #fff !important" >	

    	{{tr('check_redeem_requests')}}

    	</a>

    </li> */?>

	<!-- <div class="box box-widget widget-user-2">

    	<div class="widget-user-header bg-green">

      	    <h3 class="widget-user-username" style="margin-left: 0">{{tr('checkout')}} </h3>
      	    
    	</div>

    	<div class="box-footer no-padding">
    		
      		<ul class="nav nav-stacked">

      			<li>
                	<a href="#"><b>{{tr('paypal_email')}}</b> <span class="pull-right">{{$data->paypal_email}}</span></a>
                </li>

                <li><a href="#">{{tr('total')}} <span class="pull-right">{{Setting::get('currency' , '$')}} {{$data->total}}</span></a></li>

                <li><a href="#">{{tr('total_admin_amount')}} <span class="pull-right">{{Setting::get('currency' , '$')}} {{$data->total_admin_amount}}</span></a></li>

                <li><a href="#">{{tr('total_user_amount')}} <span class="pull-right">{{Setting::get('currency' , '$')}} {{$data->total_user_amount}}</span></a></li>

                <li><a href="#">{{tr('paid_amount')}} <span class="pull-right">{{Setting::get('currency' , '$')}} {{$data->paid_amount}}</span></a></li>

                <li><a href="#">{{tr('remaining_amount')}} <span class="pull-right">{{Setting::get('currency' , '$')}} {{$data->remaining_amount}}</span></a></li>

                <li style="padding: 10px;">
                	
                	<form class="" action="{{route('admin.users.payout')}}" method="POST">

                		<span>

                			<input type="hidden" name="user_id" value="{{$data->id}}">
                			
                			<input type="number" name="amount" class="form-control pull-left" style="width: 70%;margin-bottom: 10px" placeholder="Enter Amount to pay">

                			<button type="submit" class="btn btn-success pull-right" style="width: 20%" @if(!$data->paid_amount) disabled @endif>

                				<i class="fa fa-thumbs-up"></i> {{tr('submit')}}

                			</button>	

                		</span>	                		
                	</form>

                </li>



               
      		</ul>
    	</div>
  	
  	</div> -->

@endsection


