@extends('layouts.admin')

@section('title', tr('view_page'))

@section('content-header', tr('pages'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li><a href="{{route('admin.pages.index')}}"><i class="fa fa-book"></i> {{tr('pages')}}</a></li>
    <li class="active"> <i class="fa fa-eye"></i> {{tr('view_page')}}</li>
@endsection

@section('content')

    @include('notification.notify')
    
    <div class="row">

        <div class="col-md-12">

            <div class="box box-primary">

                <div class="box-header label-primary">
                    <div class="pull-left">
                        <h3 class="box-title" style="color: white"><b><b>@yield('title')</h3>
                    </div>

                        <div class="pull-right">
                           
                            <a href="{{route('admin.pages.edit',$data->id)}}" class="btn btn-sm btn-warning"><i class="fa fa-pencil"></i> {{tr('edit')}}</a>
                        </div>
                    <div class="clearfix"></div>
                </div>

                <div class="box-body">

                    <strong><i class="fa fa-book margin-r-5"></i> {{tr('title')}}</strong>

                    <p class="text-muted">{{$data->title}}</p>

                    <hr>

                    <strong><i class="fa fa-book margin-r-5"></i> {{tr('description')}}</strong>

                    <p class="text-muted"><?= $data->description ?></p>

                    <hr>

                </div>

            </div>
            <!-- /.box -->
        </div>

    </div>
@endsection


