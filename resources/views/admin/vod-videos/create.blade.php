@extends('layouts.admin')

@section('title', tr('upload_vod_video'))

@section('content-header', tr('vod_videos'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li><a href="{{route('admin.vod-videos.index')}}"><i class="fa fa-video-camera"></i> {{tr('vod_videos')}}</a></li>
    <li class="active"><i class="fa fa-plus"></i> {{tr('upload_video')}}</li>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{asset('theme/plugins/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css')}}">

    <link rel="stylesheet" href="{{asset('theme/plugins/iCheck/all.css')}}">

    <style type="text/css">
        
        .section_loader {

            position: absolute;

            align-items: center;

            display: flex;

            justify-content: center;

            opacity: 0.7;

            background: #fff;

            width: 100%;

            height: 100%;
        }
    </style>
@endsection

@section('content')

@include('notification.notify')

    <div class="row">

        <div class="col-md-10">

            <div class="box box-primary">

                <div class="box-header label-primary">
                    <b class="font_size_css">@yield('title')</b>
                    
                    <a href="{{route('admin.vod-videos.index')}}" style="float:right" class="btn btn-default"> <i class="fa fa-eye"></i> {{tr('view_vod_videos')}}</a>
                </div>                

                <form class="form-horizontal" action="{{route('admin.vod-videos.save')}}" method="POST" enctype="multipart/form-data" role="form">

                    <div class="box-body">

                        <div class="row">

                            <div class="col-lg-12">

                                <p class="text-danger"><b>{{tr('note')}} : </b><small>{{tr('upload_video_notes')}}</small></p>

                            </div>

                        </div>

                        <div class="form-group">
                            <label for="title" class="col-sm-2 control-label">{{tr('username')}} *</label>

                            <div class="col-sm-10">
                                <select class="form-control select2" name="user_id" required>
                                    <option value="">{{tr('select_user')}}</option>
                                    @foreach($users as $user)
                                    <option value="{{$user->id}}">{{$user->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>


                        <div class="form-group">
                            <label for="title" class="col-sm-2 control-label">{{tr('title')}} * </label>

                            <div class="col-sm-10">
                                <input type="text" required name="title" class="form-control" id="title" value="{{old('title')}}" placeholder="{{tr('title')}}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="reviews" class="col-sm-2 control-label">{{tr('publish_type')}}</label>
         
                            <div class="radio radio-primary radio-inline">
                                <input type="radio" id="now" value="{{PUBLISH_NOW}}" name="publish_type" checked="" >
                                <label for="now"> {{tr('now')}} </label>
                            </div>

                            <div class="radio radio-primary radio-inline">
                                <input type="radio" id="later" value="{{PUBLISH_LATER}}" name="publish_type">
                                <label for="later"> {{tr('later')}} </label>
                            </div>
                        </div>

                        <div class="form-group" id="time_li" style="display: none">
                            <label for="title" class="col-sm-2 control-label">{{tr('publish_time')}} * </label>
                            <div class="col-sm-10">
                                <input type="text" name="publish_time" class="form-control" id="publish_time" value="{{old('publish_time')}}" placeholder="{{tr('publish_time')}}" readonly>
                            </div>
                        </div>

                        <div class="form-group">

                            <label for="image" class="col-sm-2 control-label">{{tr('image')}} * </label>

                            <div class="col-sm-10">
                                <input type="file" name="image"  name="image" accept="image/png,image/jpeg" required onchange="loadFile(this,'image_preview')">

                                <p class="help-block">{{tr('note')}} : {{tr('image_validate')}} {{tr('rectangle_image')}}</p>

                                <img id="image_preview" style="width: 100px;height: 100px;display: none; margin: 5px;">
                            </div>

                        </div>

                        <div class="form-group">

                            <label for="video" class="col-sm-2 control-label">{{tr('video')}} *</label>

                            <div class="col-sm-10">
                                <input type="file" id="video" accept="video/mp4" name="video" required>
                                <p class="help-block">{{tr('note')}} : {{tr('video_validate')}}</p>  
                            </div>
                        </div>

                        <div class="form-group">

                            <label for="description" class="col-sm-2 control-label">{{tr('description')}} *</label>
                            <div class="col-sm-10">
                                <textarea name="description" class="form-control" required></textarea>
                            </div>
                            
                        </div>

                    </div>

                    <div class="box-footer">
                        <a href="" class="btn btn-danger">{{tr('cancel')}}</a>
                        <button type="submit" class="btn btn-success pull-right" id="upload_video_btn">{{tr('submit')}}</button>
                    </div>

                </form>

            </div>

        </div>

    </div>

@endsection

@section('scripts')

    <script src="{{asset('theme/plugins/bootstrap-datetimepicker/js/moment.min.js')}}"></script> 

    <script src="{{asset('theme/plugins/bootstrap-datetimepicker/js/bootstrap-datetimepicker.js')}}"></script> 

    <script src="{{asset('theme/plugins/iCheck/icheck.min.js')}}"></script>

    <script type="text/javascript">

        $('#upload_video_btn').on('click', function() {
            setTimeout(function(){

                $('#upload_video_btn').attr("disabled", true);

                $('#upload_video_btn').text("Uploading...", true);

            }, 1000);            
        });

        
        function loadFile(event,id) {

            $('#'+id).show();

            var reader = new FileReader();

            reader.onload = function(){

                var output = document.getElementById(id);
              
                output.src = reader.result;
            };

            reader.readAsDataURL(event.files[0]);
        }

        $('#publish_time').datetimepicker({
             minDate: moment(),
            autoclose:true,
            format:'mm/dd/yyyy',
            minView: "month",
        });

        $('input[type="radio"]').change(function (event) {
            $("#time_li").hide();

            var e = $('input:radio[name="publish_type"]:checked').val();

            $("#datepicker").attr('required',false);
            $("#datepicker").val("");
            if(e == 2) {
                $("#time_li").show();
                $("#datepicker").attr('required',true);
            }
        });

    </script>

@endsection

