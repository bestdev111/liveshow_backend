@extends('layouts.admin')

@section('title', tr('users'))

@section('content-header')
 
<span style="color: green;">{{ tr('followers') }} - </span> {{$user ? $user->name : ""}}

@endsection

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li><a href="{{route('admin.users.index')}}"><i class="fa fa-user"></i> {{tr('users')}}</a></li>
    <li class="active"><i class="fa fa-user"></i> {{tr('followings')}}</li>
@endsection

@section('content')

	@include('notification.notify')

	<div class="row">
        <div class="col-xs-12">
          <div class="box box-primary">

          	<div class="box-header label-primary">
                <b>{{tr('followers')}}</b>
                <a href="{{route('admin.users.create')}}" style="float:right" class="btn btn-default">{{tr('add_user')}}</a>
            </div>
            
            <div class="box-body">

              	<table id="example1" class="table table-bordered table-striped">

					<thead>					    
						<tr>
					      	<th>{{tr('id')}}</th>
					      	<th>{{tr('username')}}</th>
					      	<th>{{tr('email')}}</th>
					      	<th>{{tr('picture')}}</th>
					    </tr>
					</thead>

					<tbody>

						@foreach($model as $i => $user)

						    <tr>
						      	<td>{{$i+1}}</td>

						      	<td><a href="{{route('admin.users.view', ['user_id' => $user->follower_id])}}" target="_blank">{{$user->name}}</a></td>

						      	<td>{{$user->email}}</td>

						      	<td><img src="{{$user->picture}}" style="width: 40px;height: 40px"></td>

						    </tr>
						@endforeach
						
					</tbody>
				
				</table>
			
            </div>
          </div>
        </div>
    </div>

@endsection