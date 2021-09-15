@extends('layouts.admin')

@section('title', tr('view_gallery'))

@section('content-header', tr('users'))

@section('breadcrumb')  
  <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
  <li><a href="{{route('admin.users.index')}}"><i class="fa fa-user"></i> {{tr('users')}}</a></li>
  <li><a href="{{route('admin.users.view', ['user_id' => $user_details->id])}}"><i class="fa fa-eye"></i> {{tr('view_user')}}</a></li>
  <li class="active"><i class="fa fa-image"></i> {{tr('view_gallery')}}</a></li>
@endsection

@section('styles')

<style type="text/css">

.gallery figure img {
  width: 100%;
  border-radius: 10px;
  -webkit-transition: all .3s ease-in-out;
  transition: all .3s ease-in-out;
  height: 200px;
}

.gallery figure a {
  position: absolute;
  top:0%;
  right:0%;
  background-color: #000;
  opacity: 0.7;
  padding: 7px 10px;
  border-radius: 50%;
  color: #fff;
}
</style>
@endsection

@section('content')

<div class="row">

  <div class="col-lg-12">

    @include('notification.notify')

    <div class="box-header label-primary">
        <b>{{tr('view_gallery')}} - <a style="color: white" href="{{route('admin.users.view', ['user_id' => $user_details->id])}}">  {{$user_details->name}}</a></b>
       
        <a href="{{route('admin.streamer_galleries.upload',['user_id'=>$user_details->id])}}" style="float:right" class="btn btn-default"><i class="fa fa-eye"></i> {{tr('add_image')}}</a>
    </div>
    <br>

    <div class="gallery">

      @if(count($streamer_galleries) > 0 )
       
        @foreach($streamer_galleries as $streamer_gallery_details)
          
          <figure class="col-lg-3 col-md-4 col-sm-6 col-xs-12">
            
            <img src="{{$streamer_gallery_details->image}}" alt=""/>
            
            <a href="{{route('admin.streamer_galleries.delete', ['gallery_id'=>$streamer_gallery_details->gallery_id, 'user_id'=>$user_details->id])}}" onclick="return confirm(&quot;{{ tr('gallery_image_delete_confirmation') }} &quot;);" title="{{tr('remove_image')}}"><i class="fa fa-times"></i>
            </a>

          </figure>
        @endforeach

        <div class="clearfix"></div>

        <div class="text-center">{{$streamer_galleries->links()}}</div>
      
      @else

        <div class="col-lg-12 text-center"><p>{{tr('gallery_empty')}}</p></div>

      @endif

    </div>

  </div>

</div>
@endsection


