@extends('layouts.admin')

@section('title', tr('settings'))

@section('content-header', tr('settings'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li class="active"><i class="fa fa-money"></i> {{tr('settings')}}</li>
@endsection

@section('content')

@include('notification.notify')

    <div class="row">

        <div class="col-md-6">
            <div class="box box-danger">
                <div class="box-header with-border">

                    <h3 class="box-title">{{tr('ios_settings')}}</h3>

                </div>

                    <form action="{{route('admin.ios_control.save')}}" method="POST" role="form">

                    <div class="box-body">

                        <div class="form-group">
                            <label>{{ tr('ios_payment_subscription_status') }}</label>
                            <br>
                            <label>
                                <input required type="radio" name="ios_payment_subscription_status" value="1" class="flat-red" @if(Setting::get('ios_payment_subscription_status') == 1) checked @endif>
                                {{tr('yes')}}
                            </label>

                            <label>
                                <input required type="radio" name="ios_payment_subscription_status" class="flat-red"  value="0" @if(Setting::get('ios_payment_subscription_status') == 0) checked @endif>
                                {{tr('no')}}
                            </label>
                        
                        </div>
                  </div>
                  <!-- /.box-body -->

                  <div class="box-footer">
                    <button type="submit" class="btn btn-primary">{{tr('submit')}}</button>
                  </div>
                </form>

            </div>
        </div>

    </div>


@endsection