@extends('layouts.admin')

@section('title', tr('edit_template'))

@section('content-header', tr('templates'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li><a href="{{route('admin.templates.notification_template_index')}}"><i class="fa fa-envelope"></i> {{tr('notification_templates')}}</a></li>
    <li class="active"><i class="fa fa-pencil"></i> {{tr('edit_template')}}</li>
@endsection

@section('content')

@include('notification.notify')

<div class="row">

    <div class="col-md-10">

        <div class="box box-success">

            <div class="box-header with-border admin-panel-success">

            </div>

            <form  action="{{route('admin.templates.save_notification_template')}}" method="POST" role="form">

                <div class="box-body">
                    <input type="hidden" name="id" value="{{$model->id}}">

                    <div class="form-group">
                        <label for="title">{{tr('page_type')}}</label>
                        <input type="text" class="form-control" name="type" id="type" value="{{ $model->type ? $model->type : old('type')  }}" placeholder="{{tr('enter_type')}}" disabled="true">
                    </div>

                    <div class="form-group">
                        <label for="title">{{tr('subject')}}</label>
                        <input type="text" class="form-control" name="subject" id="subject" value="{{ $model->subject ? $model->subject : old('subject')  }}" placeholder="{{tr('enter_subject')}}">
                    </div> 

                    <div class="form-group">
                        <label for="content">{{tr('content')}}</label>

                        <br>

                        <small class="text-danger">{{tr('note')}} : {{tr('email_template_note')}}</small>

                        <br>

                        <textarea name="content" class="form-control" placeholder="{{tr('enter_text')}}">{{$model->content}}</textarea>
                        
                    </div>

                </div>

              <div class="box-footer">
                    <button type="reset" class="btn btn-danger">{{tr('cancel')}}</button>
                    <button type="submit" class="btn btn-success pull-right">{{tr('submit')}}</button>
              </div>

            </form>
        
        </div>

    </div>

</div>
   
@endsection
