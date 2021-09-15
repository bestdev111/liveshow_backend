@extends('layouts.admin')

@section('title', tr('redeem_payout'))

@section('content-header', tr('redeem_payout'))

@section('breadcrumb')
    <li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
    <li><a href="{{route('admin.users.redeems')}}"><i class="fa fa-users"></i> {{tr('redeems')}}</a></li>
    <li class="active">{{tr('redeem_payout')}}</li>
@endsection

@section('content')

    @include('notification.notify')

    <div class="row">

        <section class="invoice">
            <!-- title row -->
            <div class="row">
                <div class="col-xs-12">
                    <h2 class="page-header">
                        <i class="fa fa-globe"></i> {{Setting::get('site_name')}}
                        <small class="pull-right">Date: {{date('d/m/Y')}}</small>
                    </h2>
                </div>
                <!-- /.col -->
            </div>
            <!-- info row -->
            <div class="row invoice-info">
                <div class="col-sm-4 invoice-col">
                    From
                    <address>
                        <strong>{{Auth::guard('admin')->user()->name}}</strong><br>
                        {{Auth::guard('admin')->user()->address}}<br>
                        Email: {{Auth::guard('admin')->user()->email}}
                    </address>
                </div>
                <!-- /.col -->
                <div class="col-sm-4 invoice-col">
                    To
                    <address>
                        <strong>{{$data->user_details ? $data->user_details->name : ""}}</strong><br>
                        {{$data->user_details ? $data->user_details->address : ""}}<br>
                        Email: {{$data->user_details ? $data->user_details->email : ""}}
                    </address>
                </div>
                <!-- /.col -->
                <div class="col-sm-4 invoice-col">
                    <b>Invoice #{{rand()}}</b>
                    <br>
                    <br>
                    <b>Order ID:</b> {{rand()}}
                    <br>
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->

            <div class="row">
                <!-- accepted payments column -->
                
                <!-- /.col -->
                <div class="col-xs-6">
                    <p class="lead">{{tr('invoice')}}</p>

                    <div class="table-responsive">
                        <table class="table">
                            <tr>
                                <th style="width:50%">Subtotal:</th>
                                <td>{{formatted_amount($data->payout_amount)}}</td>
                            </tr>
                            <tr>
                                <th>Tax</th>
                                <td>formatted_amount(0.00)</td>
                            </tr>
                            <tr>
                                <th>Shipping:</th>
                                <td>formatted_amount(0.00)</td>
                            </tr>
                            <tr>
                                <th>{{tr('total')}}:</th>
                                <td>{{formatted_amount($data->payout_amount)}}</td>
                            </tr>
                        </table>
                    </div>
                
                </div>
                <!-- /.col -->

                <div class="col-xs-6">

                    @if(in_array($data->redeem_request_status ,[REDEEM_REQUEST_SENT , REDEEM_REQUEST_PROCESSING]))

                        @if($data->user_details->email)

                        <br>

                        <br>

                        <form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">
                            <input name = "cmd" value = "_cart" type = "hidden">
                            <input name = "upload" value = "1" type = "hidden">
                            <input name = "no_note" value = "0" type = "hidden">
                            <input name = "bn" value = "PP-BuyNowBF" type = "hidden">
                            <input name = "tax" value = "0" type = "hidden">
                            <input name = "rm" value = "2" type = "hidden">
                         
                            <input name = "business" value = "{{$data->user_details ? $data->user_details->email : ''}}" type = "hidden">
                            <input name = "handling_cart" value = "0" type = "hidden">
                            <input name = "currency_code" value = "USD" type = "hidden">
                            <input name = "lc" value = "GB" type = "hidden">

                            <input name = "return" value = "{{route('admin.payout.response' , ['user_id' => $data->user_id , 'redeem_request_id' => $data->redeem_request_id , 'success' => true])}}" type = "hidden">

                            <input name = "cbt" value = "Return to My Site" type = "hidden">

                            <input name = "cancel_return" value = "{{route('admin.payout.response' , ['user_id' => $data->user_id , 'redeem_request_id' => $data->redeem_request_id , 'success' => false])}}" type = "hidden">
                            <input name = "custom" value = "" type = "hidden">
                         
                            <div id = "item_1" class = "itemwrap">
                                <input name = "item_name_1" value = "{{$data->item_name}}" type = "hidden">
                                <input name = "quantity_1" value = "1" type = "hidden">
                                <input name = "amount_1" value = "{{$data->payout_amount}}" type = "hidden">
                                <input name = "shipping_1" value = "0" type = "hidden">
                            </div>

                            <input type="image" src="https://www.paypalobjects.com/webstatic/en_US/i/buttons/checkout-logo-large.png" alt="Checkout">

                            <img alt="" src="https://paypalobjects.com/en_US/i/scr/pixel.gif"
                            width="1" height="1">

                        </form>

                        <div class="clearfix"></div>

                        @endif

                        <form action="{{route('admin.payout.direct')}}" method="post">

                            <input type="hidden" name="redeem_request_id" value="{{$data->redeem_request_id}}">

                            <input type="hidden" name="paid_amount" value="{{$data->payout_amount}}">

                            <input type="hidden" name="user_id" value="{{$data->user_id}}">

                            <?php $confirm_message = tr('redeem_pay_confirm'); ?>

                            <button type="submit" class="btn btn-success btn-lg" onclick=' return confirm("{{$confirm_message}}")'>
                                <i class="fa fa-credit-card"></i> 
                                {{tr('direct_payment')}}
                                
                            </button>

                        </form>

                    @else
                        <span>-</span>
                    @endif

                    

                </div>
            </div>
            <!-- /.row -->

        </section>

        

    </div>

@endsection