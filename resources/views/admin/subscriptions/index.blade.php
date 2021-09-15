@extends('layouts.admin')

@section('title', tr('view_subscriptions'))

@section('content-header', tr('subscriptions'))

@section('breadcrumb')
<li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
<li><a href="{{route('admin.subscriptions.index')}}"><i class="fa fa-key"></i> {{tr('subscriptions')}}</a></li>
<li class="active"><i class="fa fa-pencil"></i> {{tr('view_subscriptions')}}</li>
@endsection

@section('content')

@include('notification.notify')

<div class="row">
	<div class="col-xs-12">
		<div class="box box-primary">

			<div class="box-header label-primary">
				<b>@yield('title')</b>
				<a href="{{route('admin.subscriptions.create')}}" style="float:right" class="btn btn-default"><i class="fa fa-plus"></i> {{tr('add_subscription')}}</a>
			</div>

			<div class="box-body table-responsive">

				<div class="col-md-12 mb-2">

					<form action="{{route('admin.subscriptions.index') }}" method="GET" method="GET" role="search">
						{{ csrf_field() }}
						<div class="col-sm-offset-6 mb-2 pull-right">

							<div class="col-md-3"></div>
							<div class="col-md-1">
								<input type="text" class="form-control search_input" name="search_key" value="{{Request::get('search_key')??''}}" placeholder="{{tr('subscriptions_search_placeholder')}}">

							</div>

							<div class="col-md-2">
								<select class="form-control" name="status" style="width:150%">
									<option value="">{{tr('select')}}</option>
									<option value="{{USER_APPROVED}}" @if(Request::get('status')== USER_APPROVED && Request::get('status')!='') selected @endif>{{tr('approved')}}</option>
									<option value="{{USER_DECLINED}}" @if(Request::get('status')== USER_DECLINED && Request::get('status')!='') selected @endif>{{tr('pending')}}</option>
								</select>

							</div>

							<div class="col-md-5">
							<button type="submit" class="btn btn-warning search-btn">
								<span class="glyphicon glyphicon-search"> {{tr('search')}}</span>
							</button> 

							<a class="btn btn-danger pull-right" href="{{route('admin.subscriptions.index')}}">{{tr('clear')}}</a>
							</div>
						</div>


					</form>
				</div><br>




				@if(count($data) > 0)

				<table class="table table-bordered table-striped">

					<thead>
						<tr>
							<th>{{tr('id')}}</th>
							<th>{{tr('title')}}</th>
							<th>{{tr('plan')}}</th>
							<th>{{tr('amount')}}</th>
							<th>{{tr('status')}}</th>
							<th>{{tr('popular')}}</th>
							<th>{{tr('subscribers')}}</th>
							<th>{{tr('action')}}</th>
						</tr>
					</thead>

					<tbody>

						@foreach($data as $i => $value)

						<tr>
							<td>{{$i+$data->firstItem()}}</td>

							<td>
								<a href="{{route('admin.subscriptions.view', $value->id)}}">{{$value->title}}</a>
							</td>

							<td>{{$value->plan}}</td>

							<td>{{formatted_amount($value->amount)}}</td>

							<td class="btn-left">
								@if($value->status)
								<span class="label label-success">{{tr('approved')}}</span>
								@else
								<span class="label label-warning">{{tr('pending')}}</span>
								@endif
							</td>

							<td class="btn-left">

								@if($value->popular_status)
								<a href="{{route('admin.subscriptions.popular.status' , $value->id)}}" class="btn  btn-xs btn-danger">
									{{tr('remove_popular')}}
								</a>
								@else
								<a href="{{route('admin.subscriptions.popular.status' , $value->id)}}" class="btn  btn-xs btn-success">
									{{tr('mark_popular')}}
								</a>
								@endif

							</td>

							<td><a href="{{route('admin.subscription.payments',['subscription_id'=>$value->id])}}">
									{{$value->total_subscriptions ?? 0 }}</a></td>

							<td>
								<ul class="admin-action btn btn-default">

									<li class="dropdown">

										<a class="dropdown-toggle" data-toggle="dropdown" href="#">
											{{tr('action')}} <span class="caret"></span>
										</a>

										<ul class="dropdown-menu">

											<li role="presentation">
												<a role="menuitem" tabindex="-1" href="{{route('admin.subscriptions.edit' , $value->id)}}"><i class="fa fa-edit"></i>&nbsp;{{tr('edit')}}
												</a>
											</li>

											<li role="presentation">
												<a role="menuitem" tabindex="-1" href="{{route('admin.subscriptions.view' , $value->id)}}"><span class="text-blue"><b><i class="fa fa-eye"></i>&nbsp;{{tr('view')}}</b></span>
												</a>
											</li>

											<li role="presentation" class="divider"></li>

											<li role="presentation">
												<a role="menuitem" tabindex="-1" href="{{route('admin.subscription.payments',['subscription_id'=>$value->id])}}">
													<span class="text-green"><b><i class="fa fa-user"></i>&nbsp;{{tr('subscribers')}}</b></span>
												</a>
											</li>

											<li role="presentation" class="divider"></li>

											@if($value->status)

											<li role="presentation">
												<a role="menuitem" tabindex="-1" onclick="return confirm(&quot;{{$value->title}} - {{tr('subscription_decline_confirmation')}} &quot;)" href="{{route('admin.subscriptions.status' , $value->id)}}">
													<span class="text-red"><b><i class="fa fa-close"></i>&nbsp;{{tr('decline')}}</b></span>
												</a>
											</li>

											@else

											<li role="presentation">
												<a role="menuitem" tabindex="-1" onclick="return confirm(&quot;{{$value->title}} - {{tr('subscription_approve_confirmation')}} &quot;)" href="{{route('admin.subscriptions.status' , $value->id)}}">
													<span class="text-green"><b><i class="fa fa-check"></i>&nbsp;{{tr('approve')}}</b></span>
												</a>
											</li>

											@endif
											<li role="presentation" class="divider"></li>

											<li role="presentation">

												@if(Setting::get('admin_delete_control'))
												<a role="button" href="javascript:;" class="btn disabled" style="text-align: left"><i class="fa fa-trash"></i>&nbsp;{{tr('delete')}}</a>
												@else
												<a role="menuitem" tabindex="-1" onclick="return confirm(&quot;{{ tr('subscription_delete_confirmation', $value->title) }}&quot;)" href="{{route('admin.subscriptions.delete', array('id' => $value->id))}}"><i class="fa fa-trash"></i>&nbsp;{{tr('delete')}}</a>
												@endif

											</li>

										</ul>

									</li>

								</ul>

							</td>

						</tr>

						@endforeach

					</tbody>

				</table>

				<div align="right" id="paglink">{{ $data->appends(request()->input())->links()}}</div>

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