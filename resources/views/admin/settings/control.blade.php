@extends('layouts.admin')

@section('title', tr('settings'))

@section('content-header', tr('settings'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li class="active"><i class="fa fa-money"></i> {{tr('settings')}}</li>
@endsection

@section('content')

@include('notification.notify')

    <div class="row">

        <div class="col-md-6">
            <div class="box box-danger">
                <div class="box-header with-border">

                    <h3 class="box-title">{{tr('settings')}}</h3>

                </div>

                    <form action="{{url('save_admin_control')}}" method="POST" role="form">

                    <div class="box-body">

                        <div class="form-group">
                            <label>{{ tr('email_verify_control') }}</label>
                            <br>
                            <label>
                                <input required type="radio" name="email_verify_control" value="1" class="flat-red" @if(Setting::get('email_verify_control') == 1) checked @endif>
                                {{tr('yes')}}
                            </label>

                            <label>
                                <input required type="radio" name="email_verify_control" class="flat-red"  value="0" @if(Setting::get('email_verify_control') == 0) checked @endif>
                                {{tr('no')}}
                            </label>
                        
                        </div>

                        <div class="form-group">
                            <label>{{ tr('email_notification') }}</label>
                            <br>
                            <label>
                                <input required type="radio" name="email_notification" value="1" class="flat-red" @if(Setting::get('email_notification') == 1) checked @endif>
                                {{tr('yes')}}
                            </label>

                            <label>
                                <input required type="radio" name="email_notification" class="flat-red"  value="0" @if(Setting::get('email_notification') == 0) checked @endif>
                                {{tr('no')}}
                            </label>
                        
                        </div>

                        

                        <div class="form-group">
                            <label>{{ tr('admin_delete_control') }}</label>
                            <br>
                            <label>
                                <input required type="radio" name="admin_delete_control" value="1" class="flat-red" @if(Setting::get('admin_delete_control') == 1) checked @endif>
                                {{tr('yes')}}
                            </label>

                            <label>
                                <input required type="radio" name="admin_delete_control" class="flat-red"  value="0" @if(Setting::get('admin_delete_control') == 0) checked @endif>
                                {{tr('no')}}
                            </label>
                        </div>


                        <div class="form-group">
                            <label>{{ tr('enable_delete_video') }}</label>
                            <br>
                            <label>
                                <input required type="radio" name="delete_video" value="1" class="flat-red" @if(Setting::get('delete_video') == 1) checked @endif>
                                {{tr('yes')}}
                            </label>

                            <label>
                                <input required type="radio" name="delete_video" class="flat-red"  value="0" @if(Setting::get('delete_video') == 0) checked @endif>
                                {{tr('no')}}
                            </label>
                        </div>

                        <div class="form-group">
                            <label>{{ tr('no_of_static_pages') }}</label>
                            <br>
                            <input type="text" name="no_of_static_pages" class="form-control" value="{{Setting::get('no_of_static_pages')}}" pattern="[0-9]{1,}" title="Enter digits between 7 to 15" required>
                        </div>

                  </div>
                  <!-- /.box-body -->

                  <div class="box-footer">
                    <button type="submit" class="btn btn-primary">{{tr('submit')}}</button>
                  </div>
                </form>

            </div>
        </div>

    </div>


@endsection