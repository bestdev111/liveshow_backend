<!DOCTYPE html>
<html>

<head>
    <title>{{tr('subscription_management')}}</title>
     <style type="text/css">
        
        table{
            font-family: arial, sans-serif;
            border-collapse: collapse;
        }

        .first_row_design{
            background-color: #653bc8;
            color: #ffffff;
        }

        .row_col_design{
            background-color: #cccccc;
        }

        th{
            border: 1px solid #dddddd;
            text-align: left;
            padding: 8px;
            font-weight: bold;

        }

        td {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 8px;

        }
    </style>
</head>

<body>


    <table >

        <!------ HEADER START  ------>

        <tr class="first_row_design">

            <th>{{tr('s_no')}}</th>

            <th>{{tr('username')}}</th>

            <th>{{tr('plan')}}</th>

            <th>{{tr('payment_id')}}</th>

            <th>{{tr('amount')}}</th>

            <th>{{tr('expiry_date')}}</th>

            <th>{{tr('payment_mode')}}</th>

            <th>{{tr('coupon_code')}}</th>

            <th>{{tr('coupon_amount')}}</th>

            <th>{{tr('plan_amount')}}</th>

            <th>{{tr('final_amount')}}</th>

            <th>{{tr('expiry_date')}}</th>

            <th>{{tr('is_coupon_applied')}}</th>

            <th>{{tr('coupon_reason')}}</th>

            <th>{{tr('status')}}</th>
        </tr>

        <!------ HEADER END  ------>

        @foreach($subscription as $i => $subscription_details)

            <tr @if($i % 2 == 0) class="row_col_design" @endif >

                <td>{{$i+1}}</td>
               
                <td> {{($subscription_details->user) ? $subscription_details->user->name : tr('user_not_available')}}</td>

                <td>
                    {{$subscription_details->subscription ? $subscription_details->subscription->title : ''}}
                </td>

                <td>{{$subscription_details->payment_id}}</td>

                <td>
                   {{formatted_amount($subscription_details->amount)}}
                </td>

                <td>
                    {{common_date($subscription_details->expiry_date,Auth::guard('admin')->user()->timezone)}}
                </td>

                <td>
                    {{$subscription_details->payment_mode}}
                </td>

                <td>{{$subscription_details->coupon_code ? $subscription_details->coupon_code :"-"}}</td>

                <td>{{formatted_amount($subscription_details->coupon_amount)}}</td>

                <td>{{formatted_amount($subscription_details->subscription_amount)}}</td>

                <td>{{formatted_amount($subscription_details->amount)}}</td>
                
                <td>
                    {{common_date($subscription_details->expiry_date,Auth::guard('admin')->user()->timezone)}}</td>
                <td>
                    @if($subscription_details->is_coupon_applied)
                        {{tr('yes')}}
                    @else
                        {{tr('no')}}
                    @endif
                </td>
                <td>
                    {{$subscription_details->coupon_reason ? $subscription_details->coupon_reason : '-'}}
                </td>


                <td>
               @if($subscription_details->status) 

                    {{tr('paid')}}
                @else

                    {{tr('not_paid')}}

                @endif
                </td>
            </tr>

        @endforeach
    </table>

</body>

</html>