@extends('layouts.admin')

@section('title',tr('view_coupon'))

@section('content-header',tr('coupons'))

@section('breadcrumb')
<li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
<li><a href="{{route('admin.coupon.list')}}"><i class="fa fa-gift"></i> {{tr('coupons')}}</a></li>
<li class="active"><i class="fa fa-eye"></i> {{tr('view_coupons')}}</li>
@endsection

@section('content')

@include('notification.notify')

<div class="row">

	<div class="col-xs-12">

		<div class="box box-primary">

			<div class="box-header label-primary">
				<b>@yield('title')</b>
				<a href="{{route('admin.add.coupons')}}" class="btn btn-default pull-right"><i class="fa fa-plus"></i> {{tr('add_coupon')}}</a>
			</div>

			<div class="box-body table-responsive">

				<div class="col-md-12 mb-2">

					<form action="{{route('admin.coupon.list') }}" method="GET" method="GET" role="search">
						{{ csrf_field() }}
						<div class="col-sm-offset-6 mb-2">
							<div class="col-md-1">
								<input type="text" class="form-control search_input" name="search_key" value="{{Request::get('search_key')??''}}" placeholder="{{tr('coupon_search_placeholder')}}">

							</div>

							<div class="col-md-4">
								<select class="form-control" name="amount_type">
									<option value="">{{tr('select_amount_type')}}</option>
									<option value="{{YES}}" @if(Request::get('amount_type')==YES) selected @endif>{{tr('absoulte_amount')}}</option>
									<option value="{{NO}}" @if(Request::get('amount_type')==NO && Request::get('amount_type')!='' ) selected @endif>{{tr('percentage')}}</option>
								</select>
							</div>

							<div class="col-md-3">
								<select class="form-control" name="status">
									<option value="">{{tr('select')}}</option>
									<option value="{{APPROVED}}" @if(Request::get('status')== APPROVED ) selected @endif>{{tr('approved')}}</option>
									<option value="{{DECLINED}}" @if(Request::get('status')== DECLINED && Request::get('status') !='' ) selected @endif>{{tr('declined')}}</option>
								</select>
							</div>

							<button type="submit" class="btn btn-warning">
								<span class="glyphicon glyphicon-search"> {{tr('search')}}</span>
							</button>

							<a class="btn btn-danger" href="{{route('admin.coupon.list')}}">{{tr('clear')}}</a>


							</span>
						</div>


					</form>
				</div><br>

				@if(count($coupons)>0)

				<table class="table table-bordered table-striped">
					<thead>
						<tr>
							<th>{{tr('id')}}</th>
							<th>{{tr('title')}}</th>
							<th>{{tr('coupon_code')}}</th>
							<th>{{tr('amount_type')}}</th>
							<th>{{tr('amount')}}</th>
							<th>{{tr('expiry_date')}}</th>
							<th>{{tr('status')}}</th>
							<th>{{tr('action')}}</th>
						</tr>
					</thead>
					<tbody>

						@foreach($coupons as $i=> $value)

						<tr>
							<td>{{$i+$coupons->firstItem()}}</td>

							<td><a href="{{route('admin.coupon.view',$value->id)}}">{{$value->title}}</a></td>

							<td>{{$value->coupon_code}}</td>

							<td>
								@if($value->amount_type == 0)
								<span class="label label-info">{{tr('percentage')}}</span>
								@else
								<span class="label label-warning">{{tr('absoulte')}}</span>
								@endif
							</td>

							<td>
								@if($value->amount_type == 0)
								{{$value->amount}} %
								@else
								{{formatted_amount($value->amount)}}
								@endif
							</td>

							<td>
								{{ common_date($value->expiry_date,Auth::guard('admin')->user()->timezone) }}
							</td>

							<td>
								@if($value->status ==0)
								<span class="label label-warning">{{tr('declined')}}</span>
								@else
								<span class="label label-success">{{tr('approved')}}</span>
								@endif
							</td>

							<td>
								<ul class="admin-action btn btn-default">
									<li class="dropdown">
										<a class="dropdown-toggle" data-toggle="dropdown" href="#">
											{{tr('action')}} <span class="caret"></span>
										</a>

										<ul class="dropdown-menu">

											<li role="presentation">
												<a class="menuitem" tabindex="-1" href="{{route('admin.edit.coupons',$value->id)}}">{{tr('edit')}}</a>
											</li>

											<li role="presentation">
												<a class="menuitem" tabindex="-1" href="{{route('admin.coupon.view',$value->id)}}">{{tr('view')}}</a>
											</li>

											<li role="presentation">
												<a class="menuitem" tabindex="-1" href="{{route('admin.delete.coupon',$value->id)}}" onclick="return confirm(&quot;{{ tr('coupon_delete_confirmation', $value->title) }}&quot;);">{{tr('delete')}}</a>
											</li>

											<li role="presentation">
												@if($value->status == 0)
												<a class="menuitem" tabindex="-1" href="{{route('admin.coupon.status',['id'=>$value->id, 'status'=>1])}}">{{tr('approve')}} </a>
												@else
												<a class="menuitem" tabindex="-1" href="{{route('admin.coupon.status', ['id'=>$value->id, 'status'=>0])}}" onclick="return confirm(&quot;{{ tr('coupon_decline_confirmation', $value->title) }}&quot;);">{{tr('decline')}}</a>
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

				<div align="right" id="paglink">{{ $coupons->appends(request()->input())->links() }}</div>

				@else
				<center>
					<h3>{{tr('coupon_result_not_found_error')}}</h3>
				</center>
				@endif

			</div>

		</div>

	</div>

</div>

@endsection