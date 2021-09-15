@extends('layouts.admin')

@section('title', tr('subscription_payments'))

@section('content-header',tr('payments'))

@section('breadcrumb')
<li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
<li><i class="fa fa-money"></i> {{tr('payments')}}</li>
<li class="active"><i class="fa fa-money"></i> {{tr('subscription_payments')}}</li>
@endsection

@section('content')

@include('notification.notify')


@section('styles')

<style>
    dt {
        padding: 4px !important;
    }

    dd {
        padding: 4px !important;
    }

    table {
        font-family: arial, sans-serif;
        border-collapse: collapse;
        width: 100%;
    }

    td,
    th {
        border: 1px solid #dddddd;
        text-align: left;
        padding: 8px;
    }

    tr:nth-child(even) {
        background-color: #f1f1f1;
    }

    td:nth-child(odd) {
        color: #0000008a;
    }

    .rv-desc {
        line-height: 1.6;
        letter-spacing: 0.6px;
        font-size: 14px;
    }
</style>



@endsection

@section('breadcrumb')
<li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
<li><i class="fa fa-money"></i> {{tr('payments')}}</li>
<li class="active"><i class="fa fa-money"></i> {{tr('subscription_payments')}}</li>
@endsection


@section('content')


<div class="row">

    <div class="col-lg-12">

        <div class="box box-warning">

            <div class="box-header table-header-theme">

                <div class="clearfix"></div>
            </div>
            <!-- /.box-header -->

            <div class="box-body">

                <section id="video-details-with-images">

                    <div class="row">

                        <div class="col-md-6">

                            <h4 class="text-uppercase text-red"><b>{{tr('subscription')}} {{tr('payment')}} {{tr('details')}}</b></h4>

                            <table>

                                <tr>
                                    <td><b>{{tr('username')}}</b></td>

                                    <td>
                                        <a href="{{route('admin.users.view',['user_id' => $payments->user_id])}}">{{$payments->user_name}}</a>
                                    </td>

                                </tr>

                                <tr>
                                    <td><b>{{tr('email')}}</b></td>
                                    <td>{{$payments->email ?: tr('not_available')}}</td>

                                </tr>

                                <tr>
                                    <td><b>{{tr('mobile')}}</b></td>
                                    <td>{{$payments->mobile ?: tr('not_available')}}</td>

                                </tr>

                                <tr>
                                    <td><b>{{tr('plan')}}</b></td>
                                    <td><a href="{{route('admin.subscriptions.view',$payments->subscription_id)}}">{{$payments->subscription_name ?: tr('not_available')}}</a></td>

                                </tr>







                            </table>


                        </div>


                        <div class="col-md-6">
                            <br><br>

                            <table>

                                <tr>
                                    <td><b>{{tr('status')}}</b></td>
                                    <td>
                                        @if($payments->status)
                                        <span class="label label-success">{{tr('paid')}}</span>
                                        @else
                                        <span class="label label-danger">{{tr('not_paid')}}</span>
                                        @endif
                                    </td>
                                </tr>

                                <tr>
                                    <td><b>{{tr('payment_id')}}</b></td>
                                    <td>{{$payments->payment_id}}</td>

                                </tr>

                                <tr>
                                    <td><b>{{tr('payment_mode')}}</b></td>
                                    <td>{{$payments->payment_mode ?: 'free-plan'}}</td>

                                </tr>

                                <tr>
                                    <td><b>{{tr('referral_amount')}}</b></td>
                                    <td>{{formatted_amount($payments->wallet_amount ?? "0.00")}}</td>

                                </tr>

                                <tr>
                                    <td><b>{{tr('paid_amount')}}</b></td>
                                    <td> {{formatted_amount($payments->amount ?? "0.00")}}</td>

                                </tr>
                                <tr>
                                    <td><b>{{tr('is_coupon_applied')}}</b></td>
                                    <td>
                                        @if($payments->is_coupon_applied)
                                        <span class="label label-success">{{tr('yes')}}</span>
                                        @else
                                        <span class="label label-danger">{{tr('no')}}</span>
                                        @endif
                                    </td>

                                </tr>

                                <tr>
                                    <td><b>{{tr('coupon_reason')}}</b></td>
                                    <td> {{$payments->coupon_reason ?:tr('not_available')}}</td>

                                </tr>

                                <tr>
                                    <td><b>{{tr('coupon_code')}}</b></td>
                                    <td> {{$payments->coupon_code ?:tr('not_available')}}</td>

                                </tr>
                                <tr>
                                    <td><b>{{tr('coupon_amount')}}</b></td>
                                    <td> {{formatted_amount($payments->coupon_amount ?? "0.00")}}</td>

                                </tr>


                            </table>
                        </div>




                    </div>

                    <hr>

                </section>






                <!-- /.box-body -->
            </div>
        </div>
    </div>

</div>


@endsection