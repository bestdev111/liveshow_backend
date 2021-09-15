
@extends('layouts.admin')

@section('meta_tags')

<meta property="og:locale" content="en_US" />
<meta property="og:type" content="article" />
<meta property="og:title" content="@if(Setting::get('site_name')) {{Setting::get('site_name') }} @else {{tr('site_name')}} @endif" />
<meta property="og:description" content="{{$video->title}}" />
<meta property="og:url" content="" />
<meta property="og:site_name" content="@if(Setting::get('site_name')) {{Setting::get('site_name') }} @else {{tr('site_name')}} @endif" />
<meta property="og:image" content="{{$video->snapshot}}" />

<meta name="twitter:card" content="summary"/>
<meta name="twitter:description" content="{{$video->title}}"/>
<meta name="twitter:title" content="@if(Setting::get('site_name')) {{Setting::get('site_name') }} @else {{tr('site_name')}} @endif"/>
<meta name="twitter:image:src" content="{{$video->snapshot}}"/>

@endsection

@section('scripts')

<script type="text/javascript">

var route_url = "{{Setting::get('ANGULAR_URL')}}";
	
window.location.href = route_url+"join/video/"+"{{$video->id}}";

</script>

@endsection



