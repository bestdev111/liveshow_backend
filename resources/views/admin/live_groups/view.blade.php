@extends('layouts.admin.layout')

@section('title', tr('live_groups_view'))

@section('content-header', tr('live_groups_view'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li><a href="{{route('admin.live_groups.index')}}"><i class="fa fa-group"></i> {{tr('live_groups')}}</a></li>
    <li class="active"><i class="fa fa-eye"></i> {{tr('live_groups_view')}}</li>
@endsection

@section('content')

    @include('notification.notify')

    <div class="row">
        
        <div class="col-xs-12">
            
            <div class="box box-primary">
                
                <div class="box-header with-border">

                    <h3 class="box-title">{{$group_details->live_group_name}}</h3>

                    <div class="box-tools pull-right">
                        <span style="font: 14px;font-weight: 500;">
                            {{$group_details->updated_at}}
                        </span>

                        <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
                <!-- /.box-header -->
                <div class="box-body">

                    <div class="row">

                        <div class="col-md-12">
                            <img src="{{$group_details->live_group_picture}}" alt="{{$group_details->live_group_name}}" class="direct-chat-img">
                            <p style="margin-left: 50px">{{$group_details->live_group_description}}</p>
                        </div>

                    </div>

                    <div class="clearfix"></div>

                    <hr>

                    <div class="row">

                        @if(count($members) > 0)

                            @foreach($members as $member_details)

                                <div class="col-xs-4">

                                    <ul class="products-list product-list-in-box">

                                        <li class="item">
                                           
                                            <div class="product-img">
                                                <img src="{{$member_details->member_picture ? $member_details->member_picture : asset('background_picture.jpg')}}" alt="{{$group_details->live_group_name}}">
                                            </div>
                                           
                                            <div class="product-info">
                                                <a href="{{route('admin.users.view' , ['user_id' => $member_details->member_id])}}" class="product-title">{{$member_details->member_name}}
                                                </a>
                                                
                                                <span class="product-description">
                                                {{$member_details->member_description}}
                                                </span>
                                            </div>
                                        </li>

                                    </ul>

                                </div>

                            @endforeach

                        @else
                            <center><h3>{{tr('not_added_participants')}}</h3></center>
                        @endif
                    
                    </div>

                </div>
            
            </div>

        </div>

    </div>

@endsection