@extends('layouts.admin')

<!-- Main Content -->
@section('title', Setting::get('site_name'))

@section('styles')

<style>
    .form-control {
        height: 45px !important;
    }

    .admin-bg-login {
        background-image:url("{{Setting::get('common_bg_image')}}");
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-repeat: no-repeat;
        background-attachment: fixed;
        background-size: 100%;
        opacity: 0.85;
        filter:alpha(opacity=80);
    }

    .container{
        margin-top:10%;
    }
    </style>
@endsection

@section('content')

<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">{{tr('reset_password')}}</div>
                <div class="panel-body">
                @include('notification.notify')


                <form class="form-horizontal" role="form" method="POST" action="{{route('admin.reset_password.update')}}">
                        {{ csrf_field() }}
                         
                        @if(Request::get('token'))

                        <input type="hidden" id="reset_token" name="reset_token" value="{{Request::get('token') ?? ''}}">
                        
                        @endif

                        <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                            <label for="password" class="col-md-4 control-label">{{tr('password')}}</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control" name="password">

                                @if ($errors->has('password'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('password_confirmation') ? ' has-error' : '' }}">
                            <label for="password-confirm" class="col-md-4 control-label">{{tr('confirm_password')}}</label>
                            <div class="col-md-6">
                                <input id="password_confirmation" type="password" class="form-control" name="password_confirmation">

                                @if ($errors->has('password_confirmation'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('password_confirmation') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-md-6 col-md-offset-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-btn fa-refresh"></i> {{tr('reset_password')}}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
