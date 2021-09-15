@extends('layouts.admin')

@section('title', tr('edit_custom_live_video'))

@section('content-header', tr('custom_live_videos'))

@section('breadcrumb')
    
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li><a href="{{route('admin.custom.live')}}"><i class="fa fa-wifi"></i> {{tr('custom_live_videos')}}</a></li>
    <li class="active"><i class="fa fa-pencil"></i> {{tr('edit_custom_live_video')}}</li>

@endsection

@section('content')

@include('admin.custom_live_videos._form')

@endsection
