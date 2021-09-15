@extends('layouts.admin')

@section('title', tr('account'))

@section('content-header', tr('account'))

@section('breadcrumb')
<li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
<li class="active"><i class="fa fa-diamond"></i> {{tr('account')}}</li>
@endsection

@section('content')

@include('notification.notify')

<div class="row">

    <div class="col-md-4">

        <div class="box box-primary">

            <div class="box-body box-profile">

                <img class="profile-user-img img-responsive img-circle" src="@if(Auth::guard('admin')->user()->picture) {{Auth::guard('admin')->user()->picture}} @else {{asset('placeholder.png')}} @endif" alt="User profile picture" style="height: 100px;">

                <h3 class="profile-username text-center">{{Auth::guard('admin')->user()->name}}</h3>

                <p class="text-muted text-center">{{tr('admin')}}</p>

                <ul class="list-group list-group-unbordered">
                    <li class="list-group-item">
                        <b>{{tr('username')}}</b> <a class="pull-right">{{Auth::guard('admin')->user()->name}}</a>
                    </li>
                    <li class="list-group-item">
                        <b>{{tr('email')}}</b> <a class="pull-right">{{Auth::guard('admin')->user()->email}}</a>
                    </li>

                    <li class="list-group-item">
                        <b>{{tr('mobile')}}</b> <a class="pull-right">{{Auth::guard('admin')->user()->mobile}}</a>
                    </li>

                    <li class="list-group-item">
                        <b>{{tr('address')}}</b>
                        <a class="text-word-wrap pull-right">{{Auth::guard('admin')->user()->address}}</a>
                    </li>
                    <!-- <div class="col-md-8 text-word-wrap pull-left"><a>{{Auth::guard('admin')->user()->address}}</a></div> -->
                </ul>

            </div>

        </div>

    </div>

    <div class="col-md-8">
        <div class="nav-tabs-custom">

            <ul class="nav nav-tabs">
                <li class="active"><a href="#adminprofile" data-toggle="tab">{{tr('update_profile')}}</a></li>
                <li><a href="#image" data-toggle="tab">{{tr('upload_image')}}</a></li>
                <li><a href="#password" data-toggle="tab">{{tr('change_password')}}</a></li>
            </ul>

            <div class="tab-content">

                <div class="active tab-pane" id="adminprofile">

                    @if(Setting::get('admin_delete_control'))

                    <form class="form-horizontal" action="#" role="form">

                        @else

                        <form class="form-horizontal" action="{{route('admin.save.profile')}}" method="POST" enctype="multipart/form-data" role="form">

                            @endif

                            <input type="hidden" name="id" value="{{Auth::guard('admin')->user()->id}}">

                            <div class="form-group">
                                <label for="name" required class="col-sm-2 control-label">{{tr('username')}}</label>

                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="name" name="name" value="{{old('name') ?: Auth::guard('admin')->user()->name}}" placeholder="{{tr('username')}}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="email" class="col-sm-2 control-label">{{tr('email')}}</label>

                                <div class="col-sm-10">
                                    <input type="email" required value="{{old('email') ?: Auth::guard('admin')->user()->email}}" name="email" class="form-control" id="email" placeholder="{{tr('email')}}">
                                </div>
                            </div>


                            <div class="form-group">
                                <label for="mobile" class="col-sm-2 control-label">{{tr('mobile')}}</label>

                                <div class="col-sm-10">
                                    <input type="text" required value="{{old('mobile') ?: Auth::guard('admin')->user()->mobile}}" name="mobile" class="form-control" id="mobile" placeholder="{{tr('mobile')}}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="address" class="col-sm-2 control-label">{{tr('address')}}</label>

                                <div class="col-sm-10">
                                    <input type="text" required value="{{old('address') ?: Auth::guard('admin')->user()->address}}" name="address" class="form-control" id="address" placeholder="{{tr('address')}}">
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-sm-offset-2 col-sm-10">
                                    <button type="submit" class="btn btn-danger" @if(Setting::get('admin_delete_control')) disabled @endif>{{tr('submit')}}</button>
                                </div>
                            </div>

                        </form>
                </div>

                <div class="tab-pane" id="image">

                    @if(Setting::get('admin_delete_control'))

                    <form class="form-horizontal" action="#" role="form">

                        @else

                        <form class="form-horizontal" action="{{route('admin.save.profile')}}" method="POST" enctype="multipart/form-data" role="form">

                            @endif

                            <input type="hidden" name="id" value="{{Auth::guard('admin')->user()->id}}">

                            @if(Auth::guard('admin')->user()->picture)
                            <img style="height: 90px; margin-bottom: 15px; border-radius:2em;" src="{{Auth::guard('admin')->user()->picture}}">
                            @else
                            <img style="margin-left: 15px;margin-bottom: 10px" class="profile-user-img img-responsive img-circle" src="{{asset('placeholder.png')}}">
                            @endif

                            <div class="form-group">
                                <label for="picture" class="col-sm-2 control-label">{{tr('picture')}}</label>
                                <div class="col-sm-10">
                                    <input type="file" required class="" name="picture" id="picture" accept="image/png, image/jpeg">
                                    <span> {{tr('upload_message')}}
                                    </span>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-sm-offset-2 col-sm-10">
                                    <button type="submit" class="btn btn-danger" @if(Setting::get('admin_delete_control')) disabled @endif>Submit</button>
                                </div>
                            </div>

                        </form>
                </div>

                <div class="tab-pane" id="password">

                    @if(Setting::get('admin_delete_control'))

                    <form class="form-horizontal" action="#" role="form">

                        @else

                        <form class="form-horizontal" action="{{route('admin.change.password')}}" method="POST" enctype="multipart/form-data" role="form">

                            @endif

                            <input type="hidden" name="id" value="{{Auth::guard('admin')->user()->id}}">

                            <div class="form-group">
                                <label for="old_password" class="col-sm-3 control-label">{{tr('old_password')}}</label>

                                <div class="col-sm-8">
                                    <input required type="password" class="form-control" name="old_password" id="old_password" placeholder="{{tr('old_password')}}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="password" class="col-sm-3 control-label">{{tr('new_password')}}</label>

                                <div class="col-sm-8">
                                    <input required type="password" class="form-control" name="password" id="new_password" placeholder="{{tr('new_password')}}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="password_confirmation" class="col-sm-3 control-label">{{tr('confirm_password')}}</label>

                                <div class="col-sm-8">
                                    <input required type="password" class="form-control" name="password_confirmation" id="password_confirmation" placeholder="{{tr('confirm_password')}}">
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-sm-offset-2 col-sm-10">
                                    <button type="submit" class="btn btn-danger change_password_submit" @if(Setting::get('admin_delete_control')) disabled @endif>{{tr('submit')}}</button>
                                </div>
                            </div>

                        </form>

                </div>

            </div>

        </div>
    </div>

</div>

@endsection

@section('scripts')

    <script type="text/javascript">
       
        $('body').on('click', '.change_password_submit', function() {
            
            if ($('#old_password').val() != '' && $('#new_password').val() != '' && $('#password_confirmation').val() != '') {
                var result = confirm("{{tr('password_change_confirmation')}}");

                if (!result)
                    return false;
            }


        });

    </script>
@endsection