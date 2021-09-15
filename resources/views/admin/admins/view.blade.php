@extends('layouts.admin')

@section('title', tr('view_admin'))

@section('content-header', tr('admins'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li><a href="{{route('admin.admins.list')}}"><i class="fa fa-user"></i> {{tr('admins')}}</a></li>
    <li class="active"><i class="fa fa-user-plus"></i> {{tr('view_admin')}}</li>
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

	<div class="row">

		<div class="col-md-10 col-md-offset-1">

    		<div class="box box-widget widget-user-2">

            	<div class="widget-user-header bg-gray">
            		<div class="pull-left">
	              		<div class="widget-user-image">

	                		<img class="img-circle" src=" @if($model->picture) {{$model->picture}} @else {{asset('admin-css/dist/img/avatar.png')}} @endif" alt="{{$model->name}}">
	              		</div>

	              		<h3 class="widget-user-username">{{$model->name}} </h3>
	      				<h5 class="widget-user-desc">{{tr('user')}}</h5>
      				</div>
      				<div class="pull-right">
      					<a href="{{route('admin.admins.edit' , array('id' => $model->id))}}" class="btn btn-sm btn-warning">{{tr('edit')}}</a>
      				</div>
      				<div class="clearfix"></div>
            	</div>	
            	
            	<div class="box-footer no-padding">
            		<div class="col-md-6">
              		<ul class="nav nav-stacked">

		                <li><a href="#">{{tr('username')}} <span class="pull-right">{{$model->name}}</span></a></li>
		                <li><a href="#">{{tr('email')}} <span class="pull-right">{{$model->email}}</span></a></li>
		                <li><a href="#">{{tr('mobile')}} <span class="pull-right">{{$model->mobile}}</span></a></li>
		                
		             

		                <li>
		                	<a href="#">{{tr('status')}} 
		                		<span class="pull-right">
		                			@if($model->is_activated) 
						      			<span class="label label-success">{{tr('approved')}}</span>
						       		@else 
						       			<span class="label label-warning">{{tr('pending')}}</span>
						       		@endif
		                		</span>
		                	</a>
		                </li>

		                <li>
		                	<a href="#">

		                		{{tr('description')}}

		                		<br>
		                		<br>

		                		<p class="">{{$model->description}}</span></p>
		                	</a>
		                </li>
		             
              		</ul>
            	</div>
            	<div class="col-md-6">
            		<ul class="nav nav-stacked">
            			


		                <li><a href="#">{{tr('timezone')}} <span class="pull-right">{{$model->timezone ? $model->timezone : "-"}}</span></a></li>

		                <li><a href="#">{{tr('created_at')}} <span class="pull-right">{{convertTimeToUSERzone($model->created_at, Auth::guard('admin')->user()->timezone, 'd-m-Y H:i a')}}</span></a></li>

		                <li><a href="#">{{tr('updated_at')}} <span class="pull-right">{{convertTimeToUSERzone($model->updated_at, Auth::guard('admin')->user()->timezone, 'd-m-Y H:i a')}}</span></a></li>


              		</ul>
            	</div>
          	</div>
          	</div>

		</div>

    </div>

@endsection




