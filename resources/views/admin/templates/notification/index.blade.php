@extends('layouts.admin')

@section('title', tr('notification_templates'))

@section('content-header', tr('templates'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li><a href="{{route('admin.templates.notification_template_index')}}"><i class="fa fa-envelope"></i> {{tr('notification_templates')}}</a></li>
    <li class="active"><i class="fa fa-eye"></i> {{tr('view_users')}}</li>
@endsection

@section('content')

    @include('notification.notify')

    <div class="row">
        <div class="col-xs-12">

        <div class="box box-primary">

            <div class="box-header label-primary">
               <b>@yield('title')</b>
            </div>

            <div class="box-body">

                <table id="datatable-withoutpagination" class="table table-bordered table-striped">

                    <thead>
                    
                        <tr>
                            <th>{{tr('id')}}</th>
                            <th>{{tr('type')}}</th>
                            <th>{{tr('subject')}}</th>
                            <th>{{tr('status')}}</th>
                            <th>{{tr('action')}}</th>
                        </tr>

                    </thead>

                    <tbody>

                        @foreach($model as $i => $data)
                
                            <tr>
                                <td>{{showEntries($_GET,$i + 1)}}</td>

                                <td>{{$data->type}}</td>

                                <td>{{$data->subject}}</td>

                                <td>
                                    @if($data->status)
                                        <span class="label label-success">{{tr('enabled')}}</span>
                                    @else
                                        <span class="label label-warning">{{tr('disabled')}}</span>
                                    @endif
                                </td>

                                <td>

                                    <div class="dropdown">
                                            
                                        <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            {{tr('action')}}
                                            <span class="caret"></span>
                                        </button>

                                        <ul class="dropdown-menu" aria-labelledby="dropdownMenu">
                                            <li>
                                                @if(Setting::get('admin_demo_control'))
                                                    <a href="javascript:;" class="btn disabled" style="text-align: left"><b>{{tr('edit')}}</b></a>
                                                @else
                                                    <a href="{{route('admin.templates.notification_template_edit', array('id' => $data->id))}}"><b>{{tr('edit')}}</b></a>
                                                @endif
                                            </li>

                                            <li>
                                               
                                                <a href="{{route('admin.templates.notification_template_credential', array('id' => $data->id))}}"><b>
                                                         @if($data->status){{tr('disable')}}@else {{tr('enable')}} @endif</b></a>
                                              
                                                
                                            </li>

                                            <li>
                                                @if(Setting::get('admin_demo_control'))
                                                    <a href="javascript:;" class="btn disabled" style="text-align: left"><b>{{tr('view')}}</b></a>

                                                @else
                                                    <a href="{{route('admin.templates.notification_template_view',array('id' => $data->id))}}"><b>{{tr('view')}}</b></a>

                                                @endif

                                            </li>                                    

                                        </ul>

                                    </div>
                                </td>
                            
                            </tr>

                        @endforeach

                    </tbody>
                </table>
                <div align="right">{{$model->links()}}</div>
            </div>
          </div>
        </div>
    </div>

@endsection