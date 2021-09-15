@extends('layouts.admin')

@section('title', tr('edit_page'))

@section('content-header', tr('pages'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li><a href="{{route('admin.pages.index')}}"><i class="fa fa-book"></i> {{tr('pages')}}</a></li>
    <li class="active"><i class="fa fa-pencil"></i> {{tr('edit_page')}}</li>
@endsection

@section('content')

@include('notification.notify')

<div class="row">

    <div class="col-md-10">

        <div class="box box-primary">

            <div class="box-header label-primary">
                <b>@yield('title')</b>
                <a href="{{route('admin.pages.create')}}" style="float:right" class="btn btn-default"> <i class="fa fa-plus"></i> {{tr('add_page')}}</a>
            </div>

            <form  action="{{route('admin.pages.save')}}" method="POST" enctype="multipart/form-data" role="form">

                <div class="box-body">
                    <input type="hidden" name="id" value="{{$data->id}}">

                    <div class="form-group">
                        <label for="title">{{tr('page_type')}} * </label>
                        <input type="text" class="form-control" name="type" id="title" value="{{ $data->type  }}" placeholder="{{tr('enter_type')}}" disabled="true">
                    </div>

                    <div class="form-group">
                        <label for="title">{{tr('title')}} * </label>
                        <input type="text" class="form-control" name="title" id="title" required value="{{ old('title') ?: $data->title  }}" placeholder="{{tr('enter_title')}}" disabled="true">
                    </div>

                    <div class="form-group">
                        <label for="heading">{{tr('heading')}} * </label>
                        <input type="text" class="form-control" name="heading" required value="{{ old('heading') ?: $data->heading  }}" id="heading" placeholder="{{tr('enter_heading')}}">
                    </div>

                    <div class="form-group">
                        <label for="description">{{tr('description')}} * </label>

                        <textarea id="ckeditor" name="description" class="form-control" required placeholder="{{tr('enter_text')}}">{{old('description') ?: $data->description}}</textarea>
                        
                    </div>

                </div>

              <div class="box-footer">
                    <a href="" class="btn btn-danger">{{tr('cancel')}}</a>
                    <button type="submit" class="btn btn-success pull-right">{{tr('submit')}}</button>
              </div>

            </form>
        
        </div>

    </div>

</div>
   
@endsection

@section('scripts')
    <script src="https://cdn.ckeditor.com/4.5.5/standard/ckeditor.js"></script>
    <script>
        CKEDITOR.replace( 'ckeditor' );
    </script>
@endsection
