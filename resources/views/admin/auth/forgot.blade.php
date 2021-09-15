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


                    <form class="form-horizontal" role="form" method="POST" @if($is_email_configured==YES) action="{{route('admin.forgot_password.update')}}" method="POST" @endif>
                        {{ csrf_field() }}

                        <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                            <label for="email" class="col-md-4 control-label">{{tr('email_address')}}</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}">

                                @if ($errors->has('email'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-md-6 col-md-offset-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-btn fa-envelope"></i> {{tr('send_password_reset_link')}}
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
