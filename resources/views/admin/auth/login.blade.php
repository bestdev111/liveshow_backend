@extends('layouts.admin')

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
    </style>
@endsection

@section('content')


<div class="admin-bg-login" >
    <div class="login-box">
      <!-- /.login-logo -->
      <div class="login-box-body">

         @include('notification.notify')

        <div class="login-logo">
            
            <a href=""><b>{{Setting::get('site_name') ? Setting::get('site_name') : "Live Streaming" }}</b></a>
    
        </div>

        <p class="login-box-msg">{{tr('signin_content')}}</p>

        <form role="form" method="POST" action="{{ route('admin.login.post') }}">
        
            {{ csrf_field() }}

            <input type="hidden" name="timezone" value="" id="userTimezone">
            
            <div class="form-group has-feedback {{ $errors->has('email') ? ' has-error' : '' }}">
                
                <input id="email" type="email" class="form-control" name="email" value="{{old('email') ? old('email') : Setting::get('admin_demo_email') }}" placeholder="{{tr('email')}}">
                
                <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
                @if ($errors->has('email'))
                    <span class="help-block">
                        <strong>{{ $errors->first('email') }}</strong>
                    </span>
                @endif
            </div>

            <div class="form-group has-feedback {{ $errors->has('password') ? ' has-error' : '' }}">
                
                <input id="password" type="password" class="form-control" name="password" placeholder="{{tr('password')}}" value="{{old('password') ? old('password'): Setting::get('admin_demo_password')}}">
                
                <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                
                @if ($errors->has('password'))
                    <span class="help-block">
                        <strong>{{ $errors->first('password') }}</strong>
                    </span>
                @endif
            </div>

            <div class="row">

                <!-- <div class="col-xs-8">
                    <div class="checkbox icheck">
                    
                         <label>
                        <input type="checkbox"> Remember Me
                        </label> 

                        <a href="#">{{tr('forgot_password')}}</a>
                    </div>
                </div> -->
                <!-- /.col -->
                <div class="col-xs-4 login_btn_css">
                
                  <button type="submit" class="btn btn-primary btn-block btn-flat">{{tr('login')}}</button>
                </div>

                @if(!Setting::get('admin_delete_control'))
                    <div class="register-footer mt-4 text-center">
                        <p>
                        <b><a href="{{route('admin.reset_password.request')}}">{{tr('forgot_password')}} ?</a></b>
                        </p>
                    </div>
               @endif


                <!-- /.col -->
            </div>
        </form>

      </div>
      <!-- /.login-box-body -->
    </div>
</div>
@endsection

@section('scripts')
<script src="{{asset('common/js/jstz.min.js')}}"></script>
<script>
    
    $(document).ready(function() {

        var dMin = new Date().getTimezoneOffset();
        var dtz = -(dMin/60);
        // alert(dtz);
        $("#userTimezone").val(jstz.determine().name());
    });

</script>

@endsection

