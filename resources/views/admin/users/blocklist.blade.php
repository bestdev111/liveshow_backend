@extends('layouts.admin')

@section('title', tr('users'))

@section('content-header')
 
{{ tr('block_list') }} of <span class="text-red"> {{$block_user->name}}</span>

@endsection

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li class="active"><i class="fa fa-user"></i> {{tr('users')}}</li>
@endsection

@section('content')

	@include('notification.notify')

	<div class="row">
        <div class="col-xs-12">
          <div class="box box-primary">

          	<div class="box-header label-primary">
                <b>{{tr('block_list')}}</b>
                <a href="{{route('admin.users.create')}}" style="float:right" class="btn btn-default">{{tr('add_user')}}</a>
            </div>
            
            <div class="box-body">

              	<table id="example1" class="table table-bordered table-striped">

					<thead>					    
						<tr>
					      	<th>{{tr('id')}}</th>
					      	<th>{{tr('username')}}</th>
					      	<!-- <th>{{tr('mobile')}}</th> -->
					      	<th>{{tr('email')}}</th>
					      	<!-- <th>{{tr('blocked_by')}}</th> -->
					      	<th>{{tr('action')}}</th>
					    </tr>
					</thead>

					<tbody>

						@foreach($data as $i => $user)

						    <tr>
						      	<td>{{$i+1}}</td>

						      	<td>{{$user->name}}</td>

						      	<!-- <td>{{$user->mobile}}</td> -->

						      	<td>{{$user->email}}</td>


						      	<!-- <td>
						      		<a href="{{route('admin.users.block_list' , $user->id)}}" class="btn btn-xs btn-warning">

						      			<b>{{count($user->blockCount)}} {{tr('users')}}</b>
						      		</a>
						      	</td> -->
						      	
								<td>
									<div class="dropdown">
										
										<button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
											{{tr('action')}}
											<span class="caret"></span>
										</button>

										<ul class="dropdown-menu" aria-labelledby="dropdownMenu">
											<li>
												@if(Setting::get('admin_delete_control'))
													<a href="javascript:;" class="btn disabled"><b><i class="fa fa-edit"></i> {{tr('edit')}}</b></a>
												@else
													<a href="{{route('admin.users.edit' , array('id' => $user->id))}}"><b><i class="fa fa-edit"></i> {{tr('edit')}}</b></a>
												@endif
											</li>

											<li>
												<a href="{{route('admin.users.view' , ['user_id' => $user->id])}}">		
													<span class="text-green"><b><i class="fa fa-eye"></i> {{tr('view')}}</b></span>
												</a>

											</li>

					
											<li class="divider" role="presentation"></li>

											<li>
												@if(Setting::get('admin_delete_control'))

								                  	<a href="javascript:;" class="btn disabled" style="text-align: left">
								                  		<span class="text-red"><b><i class="fa fa-close"></i> {{tr('delete')}}</b></span>
								                  	</a>

								                @else
						                  			<a  onclick="return confirm(&quot;{{ tr('block_user_delete_confirmation', $user->name) }}&quot;)" href="{{route('admin.users.delete', array('id' => $user->id))}}">
						                  				<span class="text-red"><b><i class="fa fa-close"></i> {{tr('delete')}}</b></span>
						                  			</a>
						                  		@endif

											</li>

										</ul>

									</div>

								</td>

						    </tr>
						@endforeach
						
					</tbody>
				
				</table>
			
            </div>
          </div>
        </div>
    </div>

@endsection
