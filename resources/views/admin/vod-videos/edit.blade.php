@extends('layouts.admin')

@section('title', tr('edit_vod_video'))

@section('content-header', tr('vod_videos'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li><a href="{{route('admin.vod-videos.index')}}"><i class="fa fa-video-camera"></i> {{tr('vod_videos')}}</a></li>
    <li class="active"><i class="fa fa-pencil"></i> {{tr('edit_vod_video')}}</li>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{asset('theme/plugins/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css')}}">

    <link rel="stylesheet" href="{{asset('theme/plugins/iCheck/all.css')}}">
@endsection

@section('content')

    @include('notification.notify')

    <div class="row">

        <div class="col-md-10 ">

            <div class="box box-primary">

                <div class="box-header label-primary">
                    <b style="font-size: 18px;">@yield('title')</b>
                    <a href="{{route('admin.vod-videos.index')}}" style="float:right" class="btn btn-default"><i class="fa fa-eye"></i> {{tr('view_custom_live_videos')}}</a>
                </div>

                <form class="form-horizontal" action="{{route('admin.vod-videos.save')}}" method="POST" enctype="multipart/form-data" role="form">

                    <div class="box-body">

                        <div class="row">

                            <div class="col-lg-12">

                                <p class="text-danger"><b>{{tr('note')}} : </b><small>{{tr('upload_video_notes')}}</small></p>

                            </div>

                        </div>

                        <input type="hidden" name="video_id" value="{{$video_edit->unique_id}}">

                        <div class="form-group">
                            <label for="title" class="col-sm-2 control-label">{{tr('username')}} *</label>

                            <div class="col-sm-10">
                                <select class="form-control select2" name="user_id" required>
                                    <option value="">{{tr('select_user')}}</option>
                                    @foreach($users as $user)
                                    <option value="{{$user->id}}" @if($user->id == $video_edit->user_id) selected @endif>{{$user->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="title" class="col-sm-2 control-label">{{tr('title')}} *</label>

                            <div class="col-sm-10">
                                <input type="text" required name="title" class="form-control" id="title" value="{{old('title') ?: $video_edit->title}}" placeholder="{{tr('title')}}">
                            </div>
                        </div>

                        @if(!$video_edit->publish_status)

                         <div class="form-group">

                            <label for="reviews" class="col-sm-2 control-label">{{tr('publish_type')}}</label>
         
                            <div class="radio radio-primary radio-inline">
                                <input type="radio" id="now" value="{{PUBLISH_NOW}}" name="publish_type">
                                <label for="now"> {{tr('now')}} </label>
                            </div>

                            <div class="radio radio-primary radio-inline">
                                <input type="radio" id="later" value="{{PUBLISH_LATER}}" name="publish_type" checked>
                                <label for="later"> {{tr('later')}} </label>
                            </div>

                        </div>

                        <div class="form-group" id="time_li">
                            <label for="title" class="col-sm-2 control-label">{{tr('publish_time')}} * </label>

                            <div class="col-sm-10">
                                <input type="text" required name="publish_time" class="form-control" id="publish_time" value="{{old('publish_time') ?: $video_edit->publish_time}}" placeholder="{{tr('publish_time')}}" readonly>
                            </div>
                        </div>

                        @endif

                        <div class="form-group">

                            <label for="image" class="col-sm-2 control-label">{{tr('image')}}</label>

                            <div class="col-sm-10">

                                <input type="file" name="image"  name="image" accept="image/png,image/jpeg">

                                <br>

                                @if($video_edit->image)

                                <img src="{{$video_edit->image}}" style="width: 150px; height: 100px" id="image_preview">

                                @endif

                            </div>
                        </div>

                        <div class="form-group">

                            <label for="video" class="col-sm-2 control-label"> {{tr('video')}}</label>

                            <div class="col-sm-10">
                                <input type="file" id="video" accept="video/mp4,video/x-matroska" name="video" placeholder="{{tr('picture')}}" value="{{$video_edit->video}}">
                               
                            </div>
                        </div>

                        <div class="form-group">

                            <label for="description" class="col-sm-2 control-label">{{tr('description')}} *</label>
                            <div class="col-sm-10">
                                <textarea name="description" class="form-control" required>{{old('description') ?: $video_edit->description}}
                                </textarea>
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

        $('#upload_video_btn').on('click', function(){

            setTimeout(function(){

                $('#upload_video_btn').attr("disabled", true);

                $('#upload_video_btn').text("Uploading...", true);

            }, 1000);
            // alert('paypal action');
            
        });
  
        function loadFile(event,id){

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

            //$("#datepicker").prop('required',false);
            $("#datepicker").val("");
            
            if(e == 2) {
                $("#time_li").show();
               // $("#datepicker").prop('required',true);
            }

        });

    </script>

@endsection
