@extends('layouts.admin')

@section('title', tr('cancelled_subscribers'))

@section('content-header', tr('subscriptions'))

@section('breadcrumb')
<li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
<li><a href="{{route('admin.subscriptions.index')}}"><i class="fa fa-key"></i> {{tr('subscriptions')}}</a></li>
<li class="active"><i class="fa fa-key"></i> {{tr('cancelled_subscribers')}}</li>
@endsection

@section('styles')

<style>
    .subscription-image {
        overflow: hidden !important;
        position: relative !important;
        height: 15em !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
        background-size: cover !important;
        margin: 0 !important;
        width: 100%;
    }

    .subscription-desc {
        max-height: 100px;
        overflow-y: auto;
        margin-bottom: 10px !important;
        min-height: 100px;
    }
</style>

@endsection

@section('content')

<div class="row">

    <div class="col-xs-12">

        @include('notification.notify')

        <div class="box box-primary">

            <div class="box-header label-primary">
                <b>@yield('title')</b>
                <ul class="admin-action btn btn-default pull-right" style="margin-right: 20px;">

                    <li class="dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                            {{tr('export')}} <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li role="presentation">
                                <a role="menuitem" tabindex="-1" href="{{route('admin.cancelled.subscribers.export', ['format' => 'xlsx'])}}">
                                    <span class="text-red"><b>{{tr('excel_sheet')}}</b></span>
                                </a>
                            </li>

                            <li role="presentation">
                                <a role="menuitem" tabindex="-1" href="{{route('admin.cancelled.subscribers.export' , ['format' => 'csv'])}}">
                                    <span class="text-blue"><b>{{tr('csv')}}</b></span>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>

            <div class="box-body table-responsive">

                @if(count($payments) > 0)

                <table id="example1" class="table table-bordered table-striped">

                    <thead>
                        <tr>
                            <th>{{tr('id')}}</th>
                            <th>{{tr('username')}}</th>
                            <th width="130">{{tr('subscription_name')}}</th>
                            <th>{{tr('amount')}}</th>
                            <th>{{tr('expiry_date')}}</th>
                            <th>{{tr('reason')}}</th>
                            <th>{{tr('action')}}</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($payments as $i => $payment)

                        <tr>
                            <td>{{$i+1}}</td>
                            <td>@if($payment->user_name)<a href="{{route('admin.users.view' , ['user_id' => $payment->user_id])}}"> {{($payment->user_name) ? $payment->user_name : ''}} </a>@endif</td>
                            <td>
                                @if($payment->subscription_name)
                                <a href="{{route('admin.subscriptions.view' , $payment->subscription_id)}}" target="_blank"> {{($payment->subscription_name) ? $payment->subscription_name : ''}} </a> @endif
                            </td>
                            <td>{{formatted_amount($payment->amount)}}</td>
                            <td>
                                {{common_date($payment->expiry_date,Auth::guard('admin')->user()->timezone,'Y-m-d H:i:s')}}
                            </td>
                            <td>{{$payment->cancel_reason?$payment->cancel_reason:'-'}}</td>
                            <td class="text-center">

                                <?php $enable_subscription_notes = tr('enable_subscription_notes'); ?>

                                <a onclick="return confirm('{{$enable_subscription_notes}}')" href="{{route('admin.automatic.subscription.enable', ['id'=>$payment->user_id])}}" class="pull-left btn btn-sm btn-success">{{tr('enable_subscription')}}</a>

                            </td>
                        </tr>

                        @endforeach

                    </tbody>

                </table>

                @else
                <center>
                    <h3>{{tr('no_results_found')}}</h3>
                </center>
                @endif

            </div>

        </div>

    </div>

</div>

@endsection