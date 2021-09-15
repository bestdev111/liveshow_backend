@extends('layouts.admin')

@section('title',tr('add_coupon'))

@section('content-header',tr('coupons'))

@section('breadcrumb')

	<li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>

	<li><a href="{{route('admin.coupon.list')}}"><i class="fa fa-gift"></i>{{tr('coupons')}}</a></li>

	<li class="active"><i class="fa fa-plus"></i> {{tr('add_coupon')}}</li>

@endsection

@section('content')

	@include('notification.notify')

	<div class="row">

		<div class="col-md-10">

			<div class="box box-primary">

				<div class="box-header label-primary">

					<b >{{tr('add_coupon')}}</b>

					<a href="{{route('admin.coupon.list')}}" class="btn btn-default pull-right"><i class="fa fa-eye"></i> {{tr('view_coupon')}}</a>

				</div>

				<form action="{{route('admin.save.coupon')}}" method="POST" class="form-horizontal" role="form">

					<div class="box-body">

						<div class="form-group">

							<label for = "title" class="col-sm-2 control-label">{{tr('title')}} * </label>

							<div class="col-sm-10">
								<input type="text" name="title" role="title" min="5" max="20" class="form-control" value="{{ old('title') }}" required placeholder="{{tr('enter_coupon_title')}}">
							</div>

						</div> 

						
						<div class="form-group">
							<label for = "coupon_code" class="col-sm-2 control-label">{{tr('coupon_code')}} * </label>
							<div class="col-sm-10">
								<input type="text" name="coupon_code" min="1" max="10" class="form-control" value="{{old('coupon_code')}}" required pattern="[A-Z0-9]{1,10}"  placeholder="{{tr('enter_coupon_code')}}" title="{{tr('validation')}}"><p class="help-block">{{tr('note')}} : {{tr('coupon_code_note')}}</p>
							</div>
						</div>

						<div class="form-group floating-label">
							<label for = "amount_type" class="col-sm-2 control-label">
							{{tr('amount_type')}} * </label>
							<div class="col-sm-10">

							<select id ="amount_type" name="amount_type" class="form-control select2" required>
								<option value="" selected="">{{tr('select_option')}}</option>
								<option value="{{PERCENTAGE}}">{{tr('percentage_amount')}}</option>
								<option value="{{ABSOULTE}}">{{tr('absoulte_amount')}}</option>
							</select> 
							</div>
						</div>

						<div class="form-group">
							<label for="amount" class="col-sm-2 control-label">{{tr('amount')}} * </label>
							<div class="col-sm-10">
								<input type="number" name="amount" min="1" max="5000" step="any" class="form-control" placeholder="{{tr('amount')}}" value="{{old('amount')}}" required title="{{tr('only_number')}}">
							</div>
						</div>

						<div class="form-group">
							<label for="expiry_date" class="col-sm-2 control-label">{{tr('expiry_date')}} * </label>
							<div class="col-sm-10">
								<input type="text" id="expiry_date" name="expiry_date" class="form-control" placeholder="{{tr('expiry_date_coupon')}}" value="{{old('expiry_date')}}" required onkeypress="return false;">
							</div>
						</div>

						<div class="form-group">
							<label for="no_of_users_limit" class="col-sm-2 control-label"> * {{tr('no_of_users_limit')}}</label>
							<div class="col-sm-10">
								<input type="text" pattern="[0-9]{1,4}" name="no_of_users_limit" class="form-control" placeholder="{{tr('no_of_users_limit')}}" value="{{old('no_of_users_limit')}}" required title="{{tr('no_of_users_limit_notes')}}">
							</div>
						</div>

						<div class="form-group">
							<label for="amount" class="col-sm-2 control-label"> * {{tr('per_users_limit')}}</label>
							<div class="col-sm-10">
								<input type="text" pattern="[0-9]{1,2}" name="per_users_limit" class="form-control" placeholder="{{tr('per_users_limit')}}" value="{{old('per_users_limit')}}" required title="{{tr('per_users_limit_notes')}}">
							</div>
						</div>

						<div class="form-group">
							<label for = "description" class="col-sm-2 control-label">{{tr('description')}}</label>
							<div class="col-sm-10">
								<textarea name="description" class="form-control" max="255"></textarea>
							</div>
						</div>
					</div> 

					<div class="box-footer">
						<button class="btn btn-danger" type="reset">{{tr('reset')}}</button>
						<button type="submit" class="btn btn-success pull-right">{{tr('submit')}}</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	
@endsection