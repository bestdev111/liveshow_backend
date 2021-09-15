@extends('layouts.admin')

@section('title', tr('add_image'))

@section('content-header', tr('users'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li><a href="{{route('admin.users.index')}}"><i class="fa fa-user"></i> {{tr('users')}}</a></li>
    <li><a href="{{route('admin.users.view', ['user_id' => $user_details->id])}}"> <i class="fa fa-eye"></i> {{tr('view_user')}}</a></li>
    <li><a href="{{route('admin.streamer_galleries.list', $user_details->id)}}"><i class="fa fa-image"></i> {{tr('galleries')}}</a></li>
    <li class="active"> <i class="fa fa-plus"></i> {{tr('add_image')}}</li>
@endsection

@section('styles')
<style type="text/css">
    
.image_overlay {
  padding: 5px 10px;
  top: 0;
  right: 15px;
  position: absolute;
  background: #000;
  opacity: 0.6;
  color: #fff;
  cursor: pointer;
}

</style>
@endsection

@section('content')

@include('notification.notify')

    <div class="row">

        <div class="col-md-10 ">

            <div class="box box-primary">

                <div class="box-header label-primary">
                    <b>{{tr('add_image')}} - <a style="color: white" href="{{route('admin.users.view', ['user_id' => $user_details->id])}}">  {{$user_details->name}}</a></b>
                   
                    <a href="{{route('admin.streamer_galleries.list', $user_details->id)}}" style="float:right" class="btn btn-default"><i class="fa fa-eye"></i> {{tr('view_gallery')}}</a>
                </div>

                <form class="form-horizontal" action="{{route('admin.streamer_galleries.save')}}" method="POST" enctype="multipart/form-data" role="form">

                    <input type="hidden" name="user_id" value="{{$user_details->id}}">

                    <div class="box-body">

                        
                         <div class="form-group">
                            <label for="mobile" class="col-sm-2 control-label">{{tr('description')}}</label>

                            <div class="col-sm-8">

                                <textarea class="form-control" name="gallery_description">{{ $user_details->gallery_description}}</textarea>
                            
                            </div>
                            
                        </div>

                        <div class="form-group">
                            <label for="mobile" class="col-sm-2 control-label">{{tr('picture')}}</label>

                            <div class="col-sm-8">
                                <input type="file" name="image[]" id="picture_id" style="width: 200px;" accept="image/png,image/jpeg"multiple />

                                <p class="help-block">{{tr('image_square')}}. {{tr('upload_message')}}</p>
                                <br>
                            
                            </div>
                            
                        </div>

                         <div class="form-group">

                            <div class="row">

                                <div id="image_preview"></div>

                                <input type="hidden" name="removed_index" id="removed_index"/>

                            </div>

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

<!-- Add Js files and inline js here -->

<script type="text/javascript">


window.onload = function(){
    //Check File API support
    if(window.File && window.FileList && window.FileReader) {

        var filesInput = document.getElementById('picture_id');

        

        filesInput.addEventListener("change", function(event){

            $("#image_preview").html("");

            var files = event.target.files; //FileList object
            var output = document.getElementById("image_preview");

            var image_idx = 0;

            for(var i = 0; i< files.length; i++)
            {
                var file = files[i];

                //Only pics
                if(!file.type.match("image"))
                    continue;
                    var picReader = new FileReader();

                    picReader.addEventListener("load",function(event){

                        var picFile = event.target;

                        console.log("post"+image_idx);

                        

                        var div = document.createElement("div");

                        div.innerHTML = "<div class='col-lg-3' id='image_"+image_idx+"' style='position:relative'><img style='width:100%;height:150px;' src= '" + picFile.result + "'/><div class='image_overlay' onclick='deletePreview("+image_idx+")'><i class='fa fa-times'></i></div></div>";
                        output.insertBefore(div,null);

                        image_idx = image_idx+1;
                    });

                    console.log("get");
                //Read the image
                picReader.readAsDataURL(file);
            }
        });


    } else{
        console.log("Your browser does not support File API");
    } 
}


function deletePreview(i) {

    //alert(i);

    $("#image_"+i).remove();

    $("#image_"+i).fadeOut(500);

    var remove_idx = $("#removed_index").val();

    if (remove_idx == '' || remove_idx == undefined) {

        $('#removed_index').val(i);

    } else {

        var value = remove_idx +','+ i;

        $('#removed_index').val(value);

    }

}
</script>

@endsection