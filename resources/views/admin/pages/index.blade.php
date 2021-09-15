@extends('layouts.admin')

@section('title', tr('view_pages'))

@section('content-header', tr('pages'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li><a href="{{route('admin.pages.index')}}"><i class="fa fa-book"></i> {{tr('pages')}}</a></li>
    <li class="active"><i class="fa fa-eye"></i> {{tr('view_pages')}}</li>

@endsection

@section('content')

    @include('notification.notify')

    <div class="row">
        <div class="col-xs-12">

          <div class="box box-primary">

            <div class="box-header label-primary">
                <b>@yield('title')</b>
                <a href="{{route('admin.pages.create')}}" style="float:right" class="btn btn-default"><i class="fa fa-plus"></i> {{tr('add_page')}}</a>
            </div>

            <div class="box-body table-responsive">
                
                @if(count($data) > 0)

                <table id="datatable-withoutpagination" class="table table-bordered table-striped" >

                    <thead>
                        <tr>
                          <th>{{tr('id')}}</th>
                          <th>{{tr('heading')}}</th>
                          <th>{{tr('page_type')}}</th>
                          <th>{{tr('action')}}</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($data as $i => $result)
                
                            <tr>

                                <td >{{$i+$data->firstItem()}}</td>
                                
                                <td>{{$result->heading}}</td>
                               
                                <td>{{ucfirst($result->type)}}</td>
                                
                                <td>

                                    <div class="dropdown">
                                        
                                        <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            {{tr('action')}}
                                            <span class="caret"></span>
                                        </button>

                                        <ul class="dropdown-menu" aria-labelledby="dropdownMenu">

                                            <li>
                                               
                                                <a href="{{route('admin.pages.view', array('id' => $result->id))}}"><b>{{tr('view')}}</b></a>
                                            </li>

                                            <li>
                                                @if(Setting::get('admin_delete_control'))
                                                    <a href="javascript:;" class="btn disabled"><b>{{tr('edit')}}</b></a>
                                                @else
                                                    <a href="{{route('admin.pages.edit', array('id' => $result->id))}}"><b>{{tr('edit')}}</b></a>
                                                @endif
                                            </li>

                                            <li>
                                                @if(Setting::get('admin_delete_control'))
                                                    <a href="javascript:;" class="btn disabled" style="text-align: left"><b>{{tr('delete')}}</b></a>

                                                @else
                                                    <a onclick="return confirm(&quot;{{ tr('page_delete_confirmation', $result->heading) }}&quot;)"  href="{{route('admin.pages.delete',array('id' => $result->id))}}"><b>{{tr('delete')}}</b></a>

                                                @endif

                                            </li>                                

                                        </ul>

                                    </div>
                                    
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

@endsection