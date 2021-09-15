@extends('layouts.admin')

@section('title', tr('live_groups_views'))

@section('content-header', tr('live_groups'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li class="active"><i class="fa fa-group"></i> {{tr('live_groups')}}</li>
@endsection

@section('content')

	@include('notification.notify')

	<div class="row">
        <div class="col-xs-12">
          <div class="box box-primary">

          	<div class="box-header label-primary">
                <b>@yield('title')</b>

                  <ul class="admin-action btn btn-default action_button pull-right">

                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                            {{ tr('admin_bulk_action') }} <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li role="presentation" class="action_list" id="bulk_delete">
                                <a role="menuitem" tabindex="-1" href="#">  <span class="text-red"><b>{{ tr('delete') }}</b> </span></a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
            
            <div class="box-body table-responsive">


            	 <div class="bulk_action">

                    <form  action="{{route('admin.live_groups.bulk_action_delete')}}" id="live_form" method="POST" role="search">

                        @csrf

                        <input type="hidden" name="action_name" id="action" value="">

                        <input type="hidden" name="selected_livegroup_id" id="selected_ids" value="">

                        <input type="hidden" name="page_id" id="page_id" value="{{ (request()->page) ? request()->page : '1' }}">

                    </form>
                </div>

            	@if(count($data) > 0)

            	<form action="{{route('admin.live_groups.index') }}" method="POST" method="POST" role="search">
            	    {{ csrf_field() }}
            	    <div class="input-group">
            	        <input type="text" value="{{Request::get('search_key') ?? ''}}" class="form-control" style="width:30%!important;" name="search_key" placeholder="Search by {{ tr('group_name')}}, {{tr('user_name')}}"> 
            	        	<span class="input-group-btn">
            	            <button type="submit" class="btn btn-default">
            	                <span class="glyphicon glyphicon-search"></span>
                            </button> 
								
							<a class="btn btn-danger group-box-left" href="{{route('admin.live_groups.index')}}">{{tr('clear')}}</a>
            	        </span>
            	    </div>
            	</form>     

              	<table class="table table-bordered table-striped">

					<thead>
					    <tr>
					    	<th>
                                <input id="check_all" type="checkbox">
                            </th>
					      	<th>{{tr('id')}}</th>
					      	<th>{{tr('username')}}</th>
					      	<th>{{tr('group_name')}}</th>
					      	<th>{{tr('total_members')}}</th>
					      	<th>{{tr('action')}}</th>
					    </tr>
					</thead>

					<tbody>
					
						@foreach($data as $i => $value)

						    <tr>

						    	<td><input type="checkbox" name="row_check" class="faChkRnd" id="live_{{$value->live_group_id}}" value="{{$value->live_group_id}}"></td>

							    <td>{{$i+$data->firstItem()}}</td>

						      	<td><a href="{{route('admin.users.view' ,['user_id' =>  $value->owner_id])}}"> {{$value->owner_name ?? tr('not_available')}}</a></td>

						      	<td>
						      		<a href="{{route('admin.live_groups.view' , ['live_group_id' => $value->live_group_id])}}">
						      			{{$value->live_group_name ?? tr('not_available')}}
						      		</a>
						      	</td>

						      	<td>{{$value->members_count ?? '0'}}</td>

						      	<td>
      								@if(Setting::get('admin_delete_control'))

      				                  	<a href="javascript:;" class="btn btn-danger btn-block btn_css">
      				                  		{{tr('delete')}}
      				                  	</a>

      				                @else
      		                  			<a onclick="return confirm(&quot;{{ tr('live_group_delete_confirmation', $value->live_group_name) }}&quot;)" href="{{route('admin.live_groups.delete', ['live_group_id' => $value->live_group_id] )}}" class="btn btn-danger btn-block"> {{tr('delete')}} </a>
      		                  		@endif

						      	</td>
						      
						    </tr>

						@endforeach
					
					</tbody>					
				
				</table>
				
				<div align="right" id="paglink">
				{{$data->appends(['search_key' => $search_key ?? ""])->links()}}
				</div>

				@else
				    <center><h3>{{tr('no_results_found')}}</h3></center>
				@endif
			
            </div>
          </div>
        </div>
    </div>

@endsection

@section('scripts')
    
@if(Session::has('bulk_action'))
<script type="text/javascript">
    $(document).ready(function(){
        localStorage.clear();
    });
</script>
@endif

<script type="text/javascript">

    $(document).ready(function(){
        get_values();

        $('.action_list').click(function(){
            var selected_action = $(this).attr('id');
            if(selected_action != undefined){
                $('#action').val(selected_action);
                if($("#selected_ids").val() != ""){
                    if(selected_action == 'bulk_delete'){
                        var message = "<?php echo tr('admin_live_group_delete_confirmation') ?>";
                    }
                    var confirm_action = confirm(message);

                    if (confirm_action == true) {
                      $( "#live_form" ).submit();
                    }
                    // 
                }else{
                    alert('Please select the check box');
                }
            }
        });
    // single check
    var page = $('#page_id').val();
    $(':checkbox[name=row_check]').on('change', function() {
        var checked_ids = $(':checkbox[name=row_check]:checked').map(function() {
            return this.value;
        })
        .get();

        localStorage.setItem("live_group_checked_items"+page, JSON.stringify(checked_ids));

        get_values();

    });
    // select all checkbox
    $("#check_all").on("click", function () {
        if ($("input:checkbox").prop("checked")) {
            $("input:checkbox[name='row_check']").prop("checked", true);
            var checked_ids = $(':checkbox[name=row_check]:checked').map(function() {
                return this.value;
            })
            .get();

            localStorage.setItem("live_group_checked_items"+page, JSON.stringify(checked_ids));
            get_values();
        } else {
            $("input:checkbox[name='row_check']").prop("checked", false);
            localStorage.removeItem("live_group_checked_items"+page);
            get_values();
        }

    });


    function get_values(){
        var pageKeys = Object.keys(localStorage).filter(key => key.indexOf('live_group_checked_items') === 0);
        var values = Array.prototype.concat.apply([], pageKeys.map(key => JSON.parse(localStorage[key])));

        if(values){
            $('#selected_ids').val(values);
        }

        for (var i=0; i<values.length; i++) {
            $('#live_' + values[i] ).prop("checked", true);
        }

}

});
</script>

@endsection