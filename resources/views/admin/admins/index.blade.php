@extends('layouts.admin')

@section('title', tr('view_admins'))

@section('content-header', tr('admins'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li><a href="{{route('admin.admins.index')}}"><i class="fa fa-user"></i> {{tr('admins')}}</a></li>
    <li class="active"><i class="fa fa-eye"></i> {{tr('view_admins')}}</li>
@endsection

@section('content')

	@include('notification.notify')

	<div class="row">
        <div class="col-xs-12">
          	
          	<div class="box box-primary">
          	
	          	<div class="box-header label-primary">
	                
	          		<b>@yield('title')</b>
	               
	                <a href="{{route('admin.admins.create')}}" class="btn btn-default pull-right">{{tr('create_admin')}}</a>

	                <!-- EXPORT OPTION START -->

					<?php /*@if(count($users) > 0 )
	                
		                <ul class="admin-action btn btn-default pull-right" style="margin-right: 20px">
		                 	
							<li class="dropdown">
				                <a class="dropdown-toggle" data-toggle="dropdown" href="#">
				                  {{tr('export')}} <span class="caret"></span>
				                </a>
				                <ul class="dropdown-menu">
				                  	<li role="presentation">
				                  		<a role="menuitem" tabindex="-1" href="{{route('admin.users.export' , ['format' => 'xls'])}}">
				                  			<span class="text-red"><b>{{tr('excel_sheet')}}</b></span>
				                  		</a>
				                  	</li>

				                  	<li role="presentation">
				                  		<a role="menuitem" tabindex="-1" href="{{route('admin.users.export' , ['format' => 'csv'])}}">
				                  			<span class="text-blue"><b>{{tr('csv')}}</b></span>
				                  		</a>
				                  	</li>
				                </ul>
							</li>
						</ul>

					@endif */?>

	                <!-- EXPORT OPTION END -->

	            </div>

            
	            <div class="box-body">

	            	<div class="table-responsive" style="padding: 35px 0px"> 
	            		
		            		<div class="table table-responsive">
				              	
				              	@if(count($data) > 0)

				              	<table id="example1" class="table table-bordered table-striped ">

									<thead>
									    <tr>
											<th>{{tr('id')}}</th>
											<th>{{tr('username')}}</th>
											<th>{{tr('email')}}</th>
											<th>{{tr('mobile')}}</th>
											<th>{{tr('status')}}</th>
											<th>{{tr('action')}}</th>
									    </tr>
									
									</thead>

									<tbody>
										@foreach($data as $i => $value)

										    <tr>

										      	<td>{{$i+$data->firstItem()}}</td>

										      	<td>
										      		<a href="{{route('admin.users.view' , ['user_id'=>$value->id])}}">
										      			{{$value->name}}
										      		</a>
										      	</td>

										      	<td>{{$value->email}}</td>
										      
										      	<td>
										      		{{$value->mobile}}
										      	</td>
										      	
										      	<td>
											      	@if($value->is_activated)

											      		<span class="label label-success">{{tr('approve')}}</span>

											      	@else

											      		<span class="label label-warning">{{tr('pending')}}</span>

											      	@endif

										     	</td>
										 
										      	<td>
			            							<ul class="admin-action btn btn-default">
			            								<li class="@if($i < 2) dropdown @else dropup @endif">
											                <a class="dropdown-toggle" data-toggle="dropdown" href="#">
											                  {{tr('action')}} <span class="caret"></span>
											                </a>
											                <ul class="dropdown-menu dropdown-menu-right">
											                  	<li role="presentation"><a role="menuitem" tabindex="-1" href="{{route('admin.admins.edit' , array('id' => $value->id))}}">{{tr('edit')}}</a></li>

											                  	<li role="presentation"><a role="menuitem" tabindex="-1" href="{{route('admin.admins.view' , ['id'=>$value->id])}}">{{tr('view')}}</a></li>

											                  	@if($value->is_activated)
											                  		<li role="presentation"><a role="menuitem" onclick="return confirm(&quot;{{$value->name}} - {{tr('admin_decline_confirmation')}}&quot;);" tabindex="-1" href="{{route('admin.admins.status' , array('id'=>$value->id))}}"> {{tr('decline')}}</a></li>
											                  	 @else 
											                  	 	<li role="presentation"><a role="menuitem" onclick="return confirm(&quot;{{$value->name}} - {{tr('admin_approve_confirmation')}}&quot;);" tabindex="-1" href="{{route('admin.users.status.change' , array('id'=>$value->id))}}"> 
											                  		{{tr('approve')}} </a></li>
											                  	@endif

											                  
											                  	<li role="presentation" class="divider"></li>

											                  <li role="presentation"><a role="menuitem" tabindex="-1" href="{{route('admin.admins.delete' , ['id'=>$value->id])}}">{{tr('delete')}}</a></li>

											                </ul>
			              								</li>
			            							</ul>
										      	
										      	</td>
										    </tr>

										@endforeach
									
									</tbody>
								
								</table>
								
								<div align="right" id="paglink">{{ $data->links() }}</div>

								@else
								    <center><h3>{{tr('no_results_found')}}</h3></center>
								@endif
							
							</div>
												
					</div>
	            </div>
          	</div>
        </div>
    
    </div>

@endsection
