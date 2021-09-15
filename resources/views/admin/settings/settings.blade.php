@extends('layouts.admin')

@section('title', tr('settings'))

@section('content-header', tr('settings'))

@section('styles')

<style>
    
/*  streamview tab */
div.streamview-tab-container{
  z-index: 10;
  background-color: #ffffff;
  padding: 0 !important;
  border-radius: 4px;
  -moz-border-radius: 4px;
  border:1px solid #ddd;
  margin-top: 20px;
  margin-left: 50px;
  -webkit-box-shadow: 0 6px 12px rgba(0,0,0,.175);
  box-shadow: 0 6px 12px rgba(0,0,0,.175);
  -moz-box-shadow: 0 6px 12px rgba(0,0,0,.175);
  background-clip: padding-box;
  opacity: 0.97;
  filter: alpha(opacity=97);
}
div.streamview-tab-menu{
  padding-right: 0;
  padding-left: 0;
  padding-bottom: 0;
}
div.streamview-tab-menu div.list-group{
  margin-bottom: 0;
}
div.streamview-tab-menu div.list-group>a{
  margin-bottom: 0;
}
div.streamview-tab-menu div.list-group>a .glyphicon,
div.streamview-tab-menu div.list-group>a .fa {
  color: #1e5780;
}
div.streamview-tab-menu div.list-group>a:first-child{
  border-top-right-radius: 0;
  -moz-border-top-right-radius: 0;
}
div.streamview-tab-menu div.list-group>a:last-child{
  border-bottom-right-radius: 0;
  -moz-border-bottom-right-radius: 0;
}
div.streamview-tab-menu div.list-group>a.active,
div.streamview-tab-menu div.list-group>a.active .glyphicon,
div.streamview-tab-menu div.list-group>a.active .fa{
  background-color: #653bc8;
  background-image: #1e5780;
  color: #ffffff;
}
div.streamview-tab-menu div.list-group>a.active:after{
  content: '';
  position: absolute;
  left: 100%;
  top: 50%;
  margin-top: -13px;
  border-left: 0;
  border-bottom: 13px solid transparent;
  border-top: 13px solid transparent;
  border-left: 10px solid #653bc8;
}

div.streamview-tab-content{
  background-color: #ffffff;
  /* border: 1px solid #eeeeee; */
  padding-left: 20px;
  padding-top: 10px;
}

.box-body {
    padding: 0px;
}

div.streamview-tab div.streamview-tab-content:not(.active){
  display: none;
}

.sub-title {
    width: fit-content;
    color: #653bc8;
    font-size: 18px;
    /*border-bottom: 2px dashed #285a86;*/
    padding-bottom: 5px;
}

hr {
    margin-top: 15px;
    margin-bottom: 15px;
}
</style>
@endsection

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li class="active"><i class="fa fa-gears"></i> {{tr('settings')}}</li>
@endsection

@section('content')

<div class="row">

    <div class="col-md-12">

        @include('notification.notify')

    </div>

    <div class="col-lg-11 col-md-11 col-sm-11 col-xs-11 streamview-tab-container">

        <div class="col-lg-2 col-md-2 col-sm-2 col-xs-2 streamview-tab-menu">
            
        <div class="list-group">
                <a href="#" class="list-group-item active text-left text-uppercase">
                    <!-- <h4 class="fa fa-globe"></h4><br/> -->

                    {{tr('site_settings')}}
                </a>
                <a href="#" class="list-group-item text-left text-uppercase">
                    <!-- <h4 class="glyphicon glyphicon-road"></h4><br/> -->
                    {{tr('video_settings')}}
                </a>

                <a href="#" class="list-group-item text-left text-uppercase">
                    <!-- <h4 class="glyphicon glyphicon-home"></h4><br/> -->

                    {{tr('social_settings')}}
                </a>
                <a href="#" class="list-group-item text-left text-uppercase">
                    <!-- <h4 class="glyphicon glyphicon-cutlery"></h4><br/> -->

                    {{tr('email_settings')}}
                </a>
                
                <a href="#" class="list-group-item text-left text-uppercase">
                    <!-- <h4 class="glyphicon glyphicon-credit-card"></h4><br/> -->

                    {{tr('revenue_settings')}}
                </a>

                <a href="#" class="list-group-item text-left text-uppercase">
                    <!-- <h4 class="glyphicon glyphicon-credit-card"></h4><br/> -->

                    {{tr('payment_settings')}}
                </a>

                <a href="#" class="list-group-item text-left text-uppercase">
                    <!-- <h4 class="glyphicon glyphicon-credit-card"></h4><br/> -->

                    {{tr('site_url_settings')}}
                </a>

                <a href="#" class="list-group-item text-left text-uppercase">
                    <!-- <h4 class="glyphicon glyphicon-credit-card"></h4><br/> -->

                    {{tr('app_url_settings')}}
                </a>

                <a href="#" class="list-group-item text-left text-uppercase">
                    {{ tr('notification_settings') }}
                </a>

                <a href="#" class="list-group-item text-left text-uppercase">
                    <!-- <h4 class="glyphicon glyphicon-credit-card"></h4><br/> -->

                    {{tr('seo_settings')}}
                </a>

                <a href="#" class="list-group-item text-left text-uppercase">
                    <!-- <h4 class="glyphicon glyphicon-credit-card"></h4><br/> -->

                    {{tr('other_settings')}}
                </a>

                <a href="#" class="list-group-item text-left text-uppercase">
                    <!-- <h4 class="glyphicon glyphicon-credit-card"></h4><br/> -->

                    {{tr('contact_information_settings')}}
                </a>


            </div>


        </div>

        <div class="col-lg-9 col-md-9 col-sm-9 col-xs-9 streamview-tab">
            
            <!-- Site section -->
            
            <div class="streamview-tab-content active">

                <form action="{{(Setting::get('admin_delete_control') == 1) ? '' : route('admin.save.settings')}}" method="POST" enctype="multipart/form-data" role="form">

                    <div class="box-body">

                        <div class="row">

                            <div class="col-md-12">

                                <h3 class="settings-sub-header text-uppercase"><b>{{tr('site_settings')}}</b></h3>

                                <hr>

                            </div>

                            <div class="col-md-6">

                                <div class="form-group">
                                    
                                    <label for="sitename">{{tr('site_name')}}</label>

                                    <input type="text" class="form-control" name="site_name" value="{{ old('site_name') ?: Setting::get('site_name')}}" id="sitename" placeholder="{{tr('enter_sitename')}}">

                                </div>



                                <div class="form-group">
                                   
                                    <label for="site_logo">{{tr('site_logo')}}</label>

                                    <br>

                                    @if(Setting::get('site_logo'))
                                        <img style="height: 50px; width:75px;margin-bottom: 15px; border-radius:2em;" src="{{Setting::get('site_logo')}}">
                                    @endif

                                    <input type="file" id="site_logo" name="site_logo" accept="image/png, image/jpeg">
                                    <p class="help-block">{{tr('please_upload_image')}}</p>
                                </div>

                            </div>

                            <div class="col-lg-6">


                                <div class="form-group">
                                    
                                    <label for="sitename">{{tr('ANGULAR_URL')}}</label>

                                    <input type="text" class="form-control" name="ANGULAR_URL" value="{{ old('ANGULAR_URL') ?: Setting::get('ANGULAR_URL')  }}" id="ANGULAR_URL" placeholder="{{tr('ANGULAR_URL')}}">

                                </div>

                                <div class="form-group">

                                    <label for="site_logo">{{tr('site_icon')}}</label>

                                    <br>

                                    @if(Setting::get('site_icon'))
                                            <img style="height: 50px; width:75px; margin-bottom: 15px; border-radius:2em;" src="{{Setting::get('site_icon')}}">
                                    @endif
                                        <label for="site_icon">{{tr('site_icon')}}</label>
                                        <input type="file" id="site_icon" name="site_icon" accept="image/png, image/jpeg">
                                        <p class="help-block">{{tr('please_upload_image')}}</p>
                                </div> 


                                <?php /*

                                <div class="form-group">

                                   @if(Setting::get('home_bg_image'))

                                            <?php $pathinfo = pathinfo(Setting::get('home_bg_image'));

                                                $extension = $pathinfo['extension'];?>

                                            @if($extension == 'jpg' || $extension == 'jpeg' || $extension == 'png')
                                            <img style="height: 50px; width:75px;margin-bottom: 15px; border-radius:2em;" src="{{Setting::get('home_bg_image')}}">
                                            @else

                                                <video playsinline autoplay muted loop poster="" id="bgvid" style="height: 50px; width:75px;">
                                                      <source src="{{Setting::get('home_bg_image')}}">
                                                </video>

                                            @endif
                                        @endif

                                        <label for="home_bg_image">{{tr('home_bg_image')}}</label>
                                        <input type="file" id="home_bg_image" name="home_bg_image" accept="image/png, image/jpeg, video/webm">
                                        <p class="help-block"> {{tr('upload_image_extension')}} </p>

                                        <div class="form-group">
                                        @if(Setting::get('common_bg_image'))
                                            <img style="height: 50px; width:75px; margin-bottom: 15px; border-radius:2em;" src="{{Setting::get('common_bg_image')}}">
                                        @endif
                                        <label for="site_icon">{{tr('common_bg_image')}}</label>
                                        <input type="file" id="common_bg_image" name="common_bg_image" accept="image/png">
                                        <p class="help-block">{{tr('please_upload_image')}}</p>
                                    </div>
                                </div> 
                                */?>

                            </div>

                        </div>

                    </div>

                    <!-- /.box-body -->

                    <div class="box-footer">

                        <button type="reset" class="btn btn-warning">{{tr('reset')}}</button>

                        @if(Setting::get('admin_delete_control') == 1)
                            <button type="submit" class="btn btn-primary pull-right" disabled>{{tr('submit')}}</button>
                        @else
                            <button type="submit" class="btn bg-blue pull-right">{{tr('submit')}}</button>
                        @endif
                    </div>
                
                </form>

            </div>

            <!-- Video section -->
            <div class="streamview-tab-content">
                
                <form action="{{(Setting::get('admin_delete_control') == 1) ? '' : route('admin.save.settings')}}" method="POST" enctype="multipart/form-data" role="form">

                    <div class="box-body">

                        <div class="row">

                            <div class="col-md-12">

                                <h3 class="settings-sub-header text-uppercase"><b>{{tr('video_settings')}}</b></h3>

                                <hr>

                            </div>

                            <div class="col-lg-6">
                                <div class="form-group">
                                        <label for="sitename">{{tr('SOCKET_URL')}}</label>

                                        <p class="example-note">{{tr('example_ip_address')}}</p>

                                        <input type="text" class="form-control" name="SOCKET_URL" value="{{ old('SOCKET_URL') ?: Setting::get('SOCKET_URL')  }}" id="SOCKET_URL" placeholder="{{tr('SOCKET_URL')}}">
                                    </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="form-group">

                                    <label for="sitename">{{tr('kurento_socket_url')}}</label>

                                    <p class="example-note">{{tr('example_ip_address_domain')}}</p>

                                    <input type="text" class="form-control" name="kurento_socket_url" value="{{ old('kurento_socket_url') ?: Setting::get('kurento_socket_url')  }}" id="KRUENTO_SOCKET_URL" placeholder="{{tr('kurento_socket_url')}}">
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="wowza_server_url">{{tr('wowza_server_url')}}</label>

                                    <p class="example-note">{{tr('example_ip_address_8007')}}</p>

                                    <input type="text" class="form-control" name="wowza_server_url" value="{{ old('wowza_server_url') ?: Setting::get('wowza_server_url')  }}" id="wowza_server_url" placeholder="{{tr('wowza_server_url')}}">
                                </div>
                            </div>

                            <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="cross_platform_url">{{tr('cross_platform_url')}}</label>
                                        <p class="example-note">{{tr('example_ip_address_1935')}}</p>

                                        <input type="text" class="form-control" name="cross_platform_url" value="{{ old('cross_platform_url') ?: Setting::get('cross_platform_url')  }}" id="cross_platform_url" placeholder="{{tr('cross_platform_url')}}">
                                    </div>
                                </div>

                                 <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="chat_socket_url">{{tr('chat_socket_url')}}</label>
                                        <p class="example-note">{{tr('example_ip_address_3002')}}</p>

                                        <input type="text" class="form-control" name="chat_socket_url" value="{{ old('chat_socket_url') ?: Setting::get('chat_socket_url')  }}" id="chat_socket_url" placeholder="{{tr('chat_socket_url')}}">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">

                                        <label for="sitename">{{tr('wowza_ip_address')}}</label>
                                        <p class="example-note">{{tr('example_127')}}</p>

                                        <input type="text" class="form-control" name="wowza_ip_address" value="{{ old('wowza_ip_address') ?: Setting::get('wowza_ip_address')  }}" id="wowza_ip_address" placeholder="{{tr('wowza_ip_address')}}">
                                    </div>
                                </div>

                                <div class="clearfix"></div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="sitename">{{tr('delete_video_hour')}}</label>
                                        <br>
                                        <p>{{tr('short_notes_video_hour')}}</p>
                                        <input type="text" class="form-control" name="delete_video_hour" value="{{ old('delete_video_hour') ?: Setting::get('delete_video_hour')  }}" id="delete_video_hour" placeholder="{{tr('delete_video_hour')}}" pattern="[0-9]{0,}">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="sitename">{{tr('jwplayer_key')}}</label>
                                        <input type="text" class="form-control" name="jwplayer_key" value="{{ old('jwplayer_key') ?: Setting::get('jwplayer_key')  }}" id="jwplayer_key" placeholder="{{tr('jwplayer_key')}}">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="sitename">{{tr('mobile_rtmp')}}</label>
                                        <input type="text" class="form-control" name="mobile_rtmp" value="{{ old('mobile_rtmp') ?: Setting::get('mobile_rtmp')  }}" id="mobile_rtmp" placeholder="{{tr('mobile_rtmp')}}">
                                    </div>
                                </div>
                                <div class="clearfix"></div>
                        </div>

                    </div>

                    <div class="box-footer">

                        <button type="reset" class="btn btn-warning">{{tr('reset')}}</button>

                        @if(Setting::get('admin_delete_control') == 1)
                            <button type="submit" class="btn btn-primary pull-right" disabled>{{tr('submit')}}</button>
                        @else
                            <button type="submit" class="btn bg-blue pull-right">{{tr('submit')}}</button>
                        @endif
                    </div>
                
                </form>

            </div>

            <!-- Social settings -->

            <div class="streamview-tab-content">
                
                <form action="{{ (Setting::get('admin_delete_control') == 1) ? '' : route('admin.save.common-settings')}}" method="POST" enctype="multipart/form-data" role="form">
                    <div class="box-body">

                        <div class="row">

                            <div class="col-md-12">

                                <h3 class="settings-sub-header text-uppercase"><b>{{tr('social_settings')}}</b></h3>

                                <hr>

                            </div>

                            <div class="col-md-12">

                                <h5 class="sub-title" >{{tr('fb_settings')}}</h5>

                            </div>

                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="fb_client_id">{{tr('FB_CLIENT_ID')}}</label>
                                     <input type="text" class="form-control" name="FB_CLIENT_ID" id="fb_client_id" placeholder="{{tr('FB_CLIENT_ID')}}" value="{{old('FB_CLIENT_ID') ?: $result['FB_CLIENT_ID']}}">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="fb_client_secret">{{tr('FB_CLIENT_SECRET')}}</label>    
                                    <input type="text" class="form-control" name="FB_CLIENT_SECRET" id="fb_client_secret" placeholder="{{tr('FB_CLIENT_SECRET')}}" value="{{old('FB_CLIENT_SECRET') ?: $result['FB_CLIENT_SECRET']}}">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="fb_call_back">{{tr('FB_CALL_BACK')}}</label>    
                                    <input type="text" class="form-control" name="FB_CALL_BACK" id="fb_call_back" placeholder="{{tr('FB_CALL_BACK')}}" value="{{old('FB_CALL_BACK') ?: $result['FB_CALL_BACK']}}">
                                </div>
                            </div>
                            <div class="clearfix"></div>
                            <div class="col-md-12">

                                <h5 class="sub-title" >{{tr('google_settings')}}</h5>

                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="google_client_id">{{tr('GOOGLE_CLIENT_ID')}}</label>
                                    <input type="text" class="form-control" name="GOOGLE_CLIENT_ID" id="google_client_id" placeholder="{{tr('GOOGLE_CLIENT_ID')}}" value="{{old('GOOGLE_CLIENT_ID') ?: $result['GOOGLE_CLIENT_ID']}}">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="google_client_secret">{{tr('GOOGLE_CLIENT_SECRET')}}</label>    
                                    <input type="text" class="form-control" name="GOOGLE_CLIENT_SECRET" id="google_client_secret" placeholder="{{tr('GOOGLE_CLIENT_SECRET')}}" value="{{old('GOOGLE_CLIENT_SECRET') ?: $result['GOOGLE_CLIENT_SECRET']}}">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="google_call_back">{{tr('GOOGLE_CALL_BACK')}}</label>    
                                    <input type="text" class="form-control" name="GOOGLE_CALL_BACK" id="google_call_back" placeholder="{{tr('GOOGLE_CALL_BACK')}}" value="{{old('GOOGLE_CALL_BACK') ?: $result['GOOGLE_CALL_BACK']}}">
                                </div>
                            </div>
                            <div class='clearfix'></div>

                        </div>
                    
                    </div>
                   
                    <div class="box-footer">

                        <button type="reset" class="btn btn-warning">{{tr('reset')}}</button>

                        @if(Setting::get('admin_delete_control') == 1)
                            <button type="submit" class="btn btn-primary pull-right" disabled>{{tr('submit')}}</button>
                        @else
                            <button type="submit" class="btn bg-blue pull-right">{{tr('submit')}}</button>
                        @endif
                    </div>

                </form>
            </div>

            <!-- Email settings -->

            <div class="streamview-tab-content">
                <form action="{{ (Setting::get('admin_delete_control') == 1) ? '' : route('admin.save.common-settings')}}" method="POST" enctype="multipart/form-data" role="form">
                            
                    <div class="box-body">

                        <div class="row">

                            <div class="col-md-12">

                                <h3 class="settings-sub-header text-uppercase"><b>{{tr('email_settings')}}</b></h3>

                                <hr>

                            </div>

                            <div class="col-md-6">

                                <div class="form-group">
                                    <label for="MAIL_MAILER">{{tr('MAIL_MAILER')}}</label>
                                    <input type="text" class="form-control" name="MAIL_MAILER" id="MAIL_MAILER" placeholder="{{tr('MAIL_MAILER')}}" value="{{old('MAIL_MAILER') ?: $result['MAIL_MAILER']}}">
                                </div>

                                <div class="form-group">
                                    <label for="MAIL_HOST">{{tr('MAIL_HOST')}}</label>
                                    <input type="text" class="form-control" name="MAIL_HOST" id="MAIL_HOST" placeholder="{{tr('MAIL_HOST')}}" value="{{old('MAIL_HOST') ?: $result['MAIL_HOST']}}">
                                </div>

                                <div class="form-group">
                                    <label for="MAIL_PORT">{{tr('MAIL_PORT')}}</label>
                                    <input type="text" class="form-control" name="MAIL_PORT" id="MAIL_PORT" placeholder="{{tr('MAIL_PORT')}}" value="{{old('MAIL_PORT') ?: $result['MAIL_PORT']}}">
                                </div>

                            </div>

                            <div class="col-md-6">

                                <div class="form-group">
                                    <label for="MAIL_USERNAME">{{tr('MAIL_USERNAME')}}</label>
                                    <input type="text" class="form-control" name="MAIL_USERNAME" id="MAIL_USERNAME" placeholder="{{tr('MAIL_USERNAME')}}" value="{{old('MAIL_USERNAME') ?: $result['MAIL_USERNAME']}}">
                                </div>

                                <div class="form-group">
                                    <label for="MAIL_PASSWORD">{{tr('MAIL_PASSWORD')}}</label>
                                    <input type="password" class="form-control" name="MAIL_PASSWORD" id="MAIL_PASSWORD" placeholder="{{tr('MAIL_PASSWORD')}}" value="{{old('MAIL_PASSWORD') ?: $result['MAIL_PASSWORD']}}">
                                </div>

                                <div class="form-group">
                                    <label for="MAIL_ENCRYPTION">{{tr('MAIL_ENCRYPTION')}}</label>
                                    <input type="text" class="form-control" name="MAIL_ENCRYPTION" id="MAIL_ENCRYPTION" placeholder="{{tr('MAIL_ENCRYPTION')}}" value="{{old('MAIL_ENCRYPTION') ?: $result['MAIL_ENCRYPTION']}}">
                                </div>

                            </div>

                            @if($result['MAIL_MAILER'] == 'mailgun')

                            <div class="col-md-12">

                                <div class="form-group">
                                    <label for="MAILGUN_DOMAIN">{{ tr('MAILGUN_DOMAIN') }}</label>
                                    <input type="text" class="form-control" value="{{ old('MAILGUN_DOMAIN') ?: $result['MAILGUN_DOMAIN']  }}" name="MAILGUN_DOMAIN" id="MAILGUN_DOMAIN" placeholder="{{ tr('MAILGUN_DOMAIN') }}">
                                </div>

                                <div class="form-group">
                                    <label for="MAILGUN_SECRET">{{ tr('MAILGUN_SECRET') }}</label>
                                    <input type="text" class="form-control" name="MAILGUN_SECRET" id="MAILGUN_SECRET" placeholder="{{ tr('MAILGUN_SECRET') }}" value="{{old('MAILGUN_SECRET') ?: $result['MAILGUN_SECRET'] }}">
                                </div>

                            </div>

                            @endif

                        </div>

                    </div>

                    <div class="box-footer">

                        <button type="reset" class="btn btn-warning">{{tr('reset')}}</button>

                        @if(Setting::get('admin_delete_control') == 1)
                            <button type="submit" class="btn btn-primary pull-right" disabled>{{tr('submit')}}</button>
                        @else
                            <button type="submit" class="btn bg-blue pull-right">{{tr('submit')}}</button>
                        @endif

                    </div>

                </form>
            </div>

            <!-- Revenue settings -->

            <div class="streamview-tab-content">

                <form action="{{ (Setting::get('admin_delete_control') == 1) ? '' : route('admin.save.common-settings')}}" method="POST" enctype="multipart/form-data" role="form">
                    
                    <div class="box-body">

                        <div class="row">

                            <div class="col-md-12">

                                <h3 class="settings-sub-header text-uppercase"><b> {{tr('revenue_settings')}}</b></h3>

                                <hr>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sitename">{{tr('admin_commission')}} {{tr('in_percentage')}}</label>
                                    <input type="number" class="form-control" name="admin_commission" value="{{ old('admin_commission') ?: Setting::get('admin_commission') }}" id="admin_commission" placeholder="{{tr('admin_commission')}}" min="0">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sitename">{{tr('user_commission')}} {{tr('in_percentage')}}</label>
                                    <input type="number" class="form-control" name="user_commission" value="{{ old('user_commission') ?: Setting::get('user_commission')  }}" id="user_commission" placeholder="{{tr('user_commission')}}" disabled min="0">
                                </div>
                            </div>
                            <div class="clearfix"></div>

                            <div class="col-md-12">

                                <h3 class="settings-sub-header text-uppercase"><b>{{tr('vod_commission_spilt')}}</b></h3>

                                <hr>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sitename">{{tr('admin_commission')}} {{tr('in_percentage')}}</label>
                                    <input type="number" class="form-control" name="admin_vod_commission" value="{{ old('admin_vod_commission') ?: Setting::get('admin_vod_commission') }}" id="admin_vod_commission" placeholder="{{tr('admin_vod_commission')}}" min="0">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sitename">{{tr('user_commission')}} {{tr('in_percentage')}}</label>
                                    <input type="number" class="form-control" name="user_vod_commission" value="{{ old('user_vod_commission') ?: Setting::get('user_vod_commission') }}" id="user_vod_commission" placeholder="{{tr('user_vod_commission')}}" disabled min="0">
                                </div>
                            </div>
                            <div class="clearfix"></div>

                        </div>
                        
                    </div>

                    <div class="box-footer">

                        <button type="reset" class="btn btn-warning">{{tr('reset')}}</button>

                        @if(Setting::get('admin_delete_control') == 1)
                            <button type="submit" class="btn btn-primary pull-right" disabled>{{tr('submit')}}</button>
                        @else
                            <button type="submit" class="btn bg-blue pull-right">{{tr('submit')}}</button>
                        @endif
                    </div>

                </form>
            
            </div>
            
            <!-- Payment settings -->

            <div class="streamview-tab-content">
                
                <form action="{{ (Setting::get('admin_delete_control') == 1) ? '' : route('admin.save.common-settings')}}" method="POST" enctype="multipart/form-data" role="form">
                   
                    <div class="box-body">

                        <div class="row">

                            <div class="col-md-12">

                                <h3 class="settings-sub-header text-uppercase"><b>{{tr('payment_settings')}}</b></h3>

                                <hr>

                            </div>

                            <div class="col-md-12">

                                <h5 class="sub-title" >{{tr('paypal_settings')}}</h5>

                            </div>

                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="paypal_id">{{tr('PAYPAL_ID')}}</label>
                                    <input type="text" class="form-control" name="PAYPAL_ID" id="paypal_id" placeholder="{{tr('PAYPAL_ID')}}" value="{{old('PAYPAL_ID') ?: $result['PAYPAL_ID']}}">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="paypal_secret">{{tr('PAYPAL_SECRET')}}</label>    
                                    <input type="text" class="form-control" name="PAYPAL_SECRET" id="paypal_secret" placeholder="{{tr('PAYPAL_SECRET')}}" value="{{old('PAYPAL_SECRET') ?: $result['PAYPAL_SECRET']}}">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="paypal_mode">{{tr('PAYPAL_MODE')}}</label>    
                                    <input type="text" class="form-control" name="PAYPAL_MODE" id="paypal_mode" placeholder="{{tr('PAYPAL_MODE')}}" value="{{old('PAYPAL_MODE') ?: $result['PAYPAL_MODE']}}">
                                </div>
                            </div>

                            <div class="clearfix"></div>

                            <div class="col-md-12">

                                <h5 class="sub-title" >{{tr('stripe_settings')}}</h5>

                            </div>

                             <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="stripe_publishable_key">{{tr('stripe_publishable_key')}}</label>
                                    <input type="text" class="form-control" name="stripe_publishable_key" id="stripe_publishable_key" placeholder="{{tr('stripe_publishable_key')}}" value="{{old('stripe_publishable_key') ?: Setting::get('stripe_publishable_key')}}">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="stripe_secret_key">{{tr('stripe_secret_key')}}</label>
                                    <input type="text" class="form-control" name="stripe_secret_key" id="stripe_secret_key" placeholder="{{tr('stripe_secret_key')}}" value="{{old('stripe_secret_key') ?: Setting::get('stripe_secret_key')}}">
                                </div>
                            </div>

                        </div>

                    </div>

                    <div class="box-footer">

                        <button type="reset" class="btn btn-warning">{{tr('reset')}}</button>

                        @if(Setting::get('admin_delete_control') == 1)
                            <button type="submit" class="btn btn-primary pull-right" disabled>{{tr('submit')}}</button>
                        @else
                            <button type="submit" class="btn bg-blue pull-right">{{tr('submit')}}</button>
                        @endif
                    </div>
                </form>
            
            </div>

            <!-- Company site Settings  -->

            <div class="streamview-tab-content">
               
                <form action="{{ (Setting::get('admin_delete_control') == 1) ? '' : route('admin.save.settings')}}" method="POST" enctype="multipart/form-data" role="form">
                    
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-12">

                                <h3 class="settings-sub-header text-uppercase"><b>{{tr('site_url_settings')}}</b></h3>

                                <hr>

                            </div>

                            <div class="col-md-6">
                                <div class="form-group">

                                    <label for="facebook_link">{{tr('facebook_link')}}</label>

                                    <input type="url" class="form-control" name="facebook_link" id="facebook_link"
                                        value="{{old('facebook_link') ?: Setting::get('facebook_link')}}" placeholder="{{tr('facebook_link')}}">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="linkedin_link">{{tr('linkedin_link')}}</label>

                                    <input type="url" class="form-control" name="linkedin_link" value="{{old('linkedin_link') ?: Setting::get('linkedin_link')  }}" id="linkedin_link" placeholder="{{tr('linkedin_link')}}">

                                </div>
                            </div>

                             <div class="col-md-6">
                                <div class="form-group">

                                    <label for="twitter_link">{{tr('twitter_link')}}</label>

                                    <input type="url" class="form-control" name="twitter_link" value="{{old('twitter_link') ?: Setting::get('twitter_link')  }}" id="twitter_link" placeholder="{{tr('twitter_link')}}">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="google_plus_link">{{tr('google_plus_link')}}</label>
                                    <input type="url" class="form-control" name="google_plus_link" value="{{old('google_plus_link') ?: Setting::get('google_plus_link')  }}" id="google_plus_link" placeholder="{{tr('google_plus_link')}}">
                                </div>
                            </div>


                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pinterest_link">{{tr('pinterest_link')}}</label>
                                    <input type="url" class="form-control" name="pinterest_link" value="{{old('pinterest_link') ?: Setting::get('pinterest_link')  }}" id="pinterest_link" placeholder="{{tr('pinterest_link')}}">
                                </div>
                            </div>
                            <div class="clearfix"></div>
                            
                        </div>
                    
                    </div>
                    
                    <div class="box-footer">

                        <button type="reset" class="btn btn-warning">{{tr('reset')}}</button>

                        @if(Setting::get('admin_delete_control') == 1)
                            <button type="submit" class="btn btn-primary pull-right" disabled>{{tr('submit')}}</button>
                        @else
                            <button type="submit" class="btn bg-blue pull-right">{{tr('submit')}}</button>
                        @endif
                    </div>
                </form>
            </div>          

            <!-- app url Settings  -->

            <div class="streamview-tab-content">
               
                <form action="{{ (Setting::get('admin_delete_control') == 1) ? '' : route('admin.save.settings')}}" method="POST" enctype="multipart/form-data" role="form">
                    
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-12">
                            <h3 class="settings-sub-header text-uppercase"><b>{{tr('app_url_settings')}}</b></h3>
                                <hr>

                                <div class="col-md-12">

                                    <!-- <h5 class="sub-title" >{{tr('app_url_settings')}}</h5> -->

                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">

                                        <label for="upload_max_size">{{tr('appstore')}}</label>

                                        <input type="url" class="form-control" name="appstore" id="appstore"
                                        value="{{old('appstore') ?: Setting::get('appstore')}}" placeholder="{{tr('appstore')}}">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="upload_max_size">{{tr('playstore')}}</label>

                                        <input type="url" class="form-control" name="playstore" value="{{old('playstore') ?: Setting::get('playstore')  }}" id="playstore" placeholder="{{tr('playstore')}}">

                                    </div>
                                </div>
                            </div>
                    
                        </div>
                    
                    </div>
                    
                    <div class="box-footer">

                        <button type="reset" class="btn btn-warning">{{tr('reset')}}</button>

                        @if(Setting::get('admin_delete_control') == 1)
                            <button type="submit" class="btn btn-primary pull-right" disabled>{{tr('submit')}}</button>
                        @else
                            <button type="submit" class="btn bg-blue pull-right">{{tr('submit')}}</button>
                        @endif
                    </div>
                </form>
            </div>
            <!-- Push Notificaion settings -->

             <div class="streamview-tab-content">
               
                <form action="{{route('admin.save.settings')}}" method="POST" enctype="multipart/form-data" r  ole="form">
                    
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-12">

                                <h3 class="settings-sub-header text-uppercase"><b>{{ tr('notification_settings') }}</b></h3>

                                <hr>

                            </div>

                            <div class="col-md-6">
                                <div class="form-group">

                                    <label for="user_fcm_sender_id">{{ tr('user_fcm_sender_id') }}</label>

                                    <input type="text" class="form-control" name="user_fcm_sender_id" id="user_fcm_sender_id"
                                    value="{{ old('user_fcm_sender_id') ?: Setting::get('user_fcm_sender_id') }}" placeholder="{{ tr('user_fcm_sender_id') }}">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="user_fcm_server_key">{{ tr('user_fcm_server_key') }}</label>

                                    <input type="text" class="form-control" name="user_fcm_server_key" value="{{ old('user_fcm_server_key') ?: Setting::get('user_fcm_server_key') }}" id="user_fcm_server_key" placeholder="{{ tr('user_fcm_server_key') }}">

                                </div>
                            </div>

                            <div class="clearfix"></div>
                            
                        </div>
                    
                    </div>
                    
                    <div class="box-footer">
                        
                        <button type="reset" class="btn btn-warning">{{ tr('reset') }}</button>

                        <button type="submit" class="btn btn-primary pull-right"  @if(Setting::get('admin_delete_control') == YES )  disabled @endif >{{ tr('submit') }}</button>
                    </div>

                </form>

            </div>
            <!-- SEO Settings -->

            <div class="streamview-tab-content">
                <form action="{{route('admin.save.settings')}}" method="POST" enctype="multipart/form-data" r  ole="form">
                            
                    <div class="box-body"> 
                        <div class="row"> 

                            <div class="col-md-12">

                                <h3 class="settings-sub-header text-uppercase"><b>{{tr('seo_settings')}}</b></h3>

                                <hr>

                            </div>

                                <div class="col-md-6">

                                    <div class="form-group">
                                        <label>{{ tr('meta_title') }}</label>
                                         <input type="text" name="meta_title" value="{{ old('meta_title') ?: Setting::get('meta_title', '')  }}" required class="form-control">
                                    </div>
                                   
                                </div>

                                <div class="col-md-6">

                                    <div class="form-group">
                                        <label for="meta_author">{{tr('meta_author')}}</label>

                                        <!-- <p class="note_content">{{tr('meta_author_note')}}</p> -->
                                        <input type="text" class="form-control" value="{{old('meta_author') ?: Setting::get('meta_author')  }}" name="meta_author" id="meta_author" placeholder="{{tr('meta_author')}}">
                                    </div> 
                                
                                </div>

                                <div class="clearfix"></div>

                                <div class="col-md-12">

                                    <div class="form-group">
                                        <label for="meta_keywords">{{tr('meta_keywords')}}</label>
                                        <textarea class="form-control" id="meta_keywords" name="meta_keywords">{{old('meta_keywords') ?: Setting::get('meta_keywords')}}</textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="meta_description">{{tr('meta_description')}}</label>
                                        <textarea class="form-control" id="meta_description" name="meta_description">{{old('meta_description') ?: Setting::get('meta_description')}}</textarea>
                                    </div>  

                                </div>

                        </div>
                    </div>
                          <!-- /.box-body -->

                    <div class="box-footer">

                        <button type="reset" class="btn btn-warning">{{tr('reset')}}</button>

                        @if(Setting::get('admin_delete_control') == 1)
                            <button type="submit" class="btn btn-primary pull-right" disabled>{{tr('submit')}}</button>
                        @else
                            <button type="submit" class="btn bg-blue pull-right">{{tr('submit')}}</button>
                        @endif
                    </div>
                </form>
            </div>


            <!-- other_settings Settings -->

            <div class="streamview-tab-content">
                <form action="{{(Setting::get('admin_delete_control') == 1) ? '' : route('admin.save.settings')}}" method="POST" enctype="multipart/form-data" role="form">
                            
                    <div class="box-body"> 
                        <div class="row"> 

                            <div class="col-md-12">

                                <h3 class="settings-sub-header text-uppercase"><b>{{tr('other_settings')}}</b></h3>

                                <hr>

                            </div>

                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="token_expiry_hour">{{tr('token_expiry_hour')}}</label>
                                        <input class="form-control" id="token_expiry_hour" name="token_expiry_hour" type="number" value="{{old('token_expiry_hour') ?: Setting::get('token_expiry_hour')}}" min="0">
                                    </div>   
                                </div>

                            <div class="clearfix"></div>

                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="google_analytics">{{tr('google_analytics')}}</label>
                                        <textarea class="form-control" id="google_analytics" name="google_analytics" style="resize: none;" rows="6">{{old('google_analytics') ?: Setting::get('google_analytics')}}</textarea>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="header_scripts">{{tr('header_scripts')}}</label>
                                        <textarea class="form-control" id="header_scripts" name="header_scripts" style="resize: none;" rows="4">{{old('header_scripts') ?: Setting::get('header_scripts')}}</textarea>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="body_scripts">{{tr('body_scripts')}}</label>
                                        <textarea class="form-control" id="body_scripts" name="body_scripts" style="resize: none;" rows="4">{{old('body_scripts') ?: Setting::get('body_scripts')}}</textarea>
                                    </div>
                                </div>   
                        </div>
                    </div>
                          <!-- /.box-body -->

                    <div class="box-footer">

                        <button type="reset" class="btn btn-warning">{{tr('reset')}}</button>

                        @if(Setting::get('admin_delete_control') == 1)
                            <button type="submit" class="btn btn-primary pull-right" disabled>{{tr('submit')}}</button>
                        @else
                            <button type="submit" class="btn bg-blue pull-right">{{tr('submit')}}</button>
                        @endif
                    </div>
                </form>
            </div>

             

            <div class="streamview-tab-content">
                <form action="{{(Setting::get('admin_delete_control') == 1) ? '' : route('admin.save.settings')}}" method="POST" enctype="multipart/form-data" role="form">
                            
                    <div class="box-body"> 
                        <div class="row"> 

                            <div class="col-md-12">

                                <h3 class="settings-sub-header text-uppercase"><b>{{tr('contact_information_settings')}}</b></h3>

                                <hr>

                            </div>

                            <div class="col-lg-6">
                                <label for="contact_mobile">{{tr('contact_mobile')}}</label>
                                <input type="text" class="form-control" id="contact_mobile" name="contact_number" placeholder="Enter {{tr('contact_mobile')}}" value="{{Setting::get('contact_number')}}" required>
                            </div>


                                <div class="col-lg-6">
                                <label for="contact_email">{{tr('contact_email')}}</label>
                                <input type="email" class="form-control" id="contact_email" name="contact_email" placeholder="Enter {{tr('contact_email')}}" value="{{Setting::get('contact_email')}}" required>
                                </div>

                                <div class="col-lg-6">
                                <label for="contact_address">{{tr('contact_address')}}</label>
                                <textarea id="ckeditor" class="form-control" name="contact_address" placeholder="Enter {{tr('contact_address')}}" required>{{Setting::get('contact_address') ?? ''}}</textarea>
                                </div>

                               
                        </div>
                    </div>
                          <!-- /.box-body -->

                    <div class="box-footer">

                        <button type="reset" class="btn btn-warning">{{tr('reset')}}</button>

                        @if(Setting::get('admin_delete_control') == 1)
                            <button type="submit" class="btn btn-primary pull-right" disabled>{{tr('submit')}}</button>
                        @else
                            <button type="submit" class="btn bg-blue pull-right">{{tr('submit')}}</button>
                        @endif
                    </div>
                </form>
            </div>












        </div>
    
    </div>
    
    <div class="clearfix"></div>

</div>


@endsection


@section('scripts')

<script type="text/javascript">
    
    $(document).ready(function() {
        $("div.streamview-tab-menu>div.list-group>a").click(function(e) {
            e.preventDefault();
            $(this).siblings('a.active').removeClass("active");
            $(this).addClass("active");
            var index = $(this).index();
            $("div.streamview-tab>div.streamview-tab-content").removeClass("active");
            $("div.streamview-tab>div.streamview-tab-content").eq(index).addClass("active");
        });
    });

    $(document).ready(function(){

    var setting_success_msg = "{{Session::get('flash_success')}}";

    if(setting_success_msg){

        "{{Session::forget('flash_success')}}";
    }
    });
</script>
@endsection







