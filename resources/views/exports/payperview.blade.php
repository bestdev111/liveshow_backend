<!DOCTYPE html>
<html>

<head>
    <title>{{tr('subscription_management')}}</title> 
    <meta name="robots" content="noindex">
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

    <table>

        <!------ HEADER START  ------>

        <tr class="first_row_design">
            <th>{{tr('s_no')}}</th>

            <th>{{tr('video')}}</th>

            <th>{{tr('name')}}</th>

            <th>{{tr('payment_id')}}</th>

            <th>{{tr('amount')}}</th>

            <th>{{tr('admin_commission')}}</th>

            <th>{{tr('user_commission')}}</th>

            <th>{{tr('payment_mode')}}</th>

            <th>{{tr('coupon_code')}}</th>

            <th>{{tr('coupon_amount')}}</th>

            <th>{{tr('plan_amount')}}</th>

            <th>{{tr('final_amount')}}</th>

            <th>{{tr('is_coupon_applied')}}</th>

            <th>{{tr('coupon_reason')}}</th>

            <th>{{tr('status')}}</th>
        </tr>

        <!------ HEADER END  ------>

        @foreach($data as $i => $payperview_details)

            <tr @if($i % 2 == 0) class="row_col_design" @endif >

                <td>{{$i+1}}</td>

                <td>
                    {{$payperview_details->getVideo ? $payperview_details->getVideo->title : ""}}
                </td>

                <td> @if($payperview_details->paiduser)

                    {{$payperview_details->paiduser ? $payperview_details->paiduser->name : ""}} 

                @else
                    -
                @endif
                </td>

                <td>
                   {{$payperview_details->payment_id}}
                </td>

                <td>{{formatted_amount($payperview_details->amount)}}</td>


                <td>
                {{formatted_amount($payperview_details->admin_amount)}}
                </td>
                
                <td>
                {{formatted_amount($payperview_details->user_amount)}}
                </td>

                <td>
                {{$payperview_details->payment_mode}}
                </td>

                <td>{{$payperview_details->coupon_code ? $payperview_details->coupon_code : "-"}}</td>

                <td>{{formatted_amount($payperview_details->coupon_amount)}}</td>

                <td>{{formatted_amount($payperview_details->subscription_amount)}}</td>

                <td>{{formatted_amount($payperview_details->amount)}}</td>
                
                <td>
                    @if($payperview_details->is_coupon_applied)
                        {{tr('yes')}}
                    @else
                        {{tr('no')}}
                    @endif
                </td>
                <td>
                    {{$payperview_details->coupon_reason ? $payperview_details->coupon_reason : '-'}}
                </td>

                <td>
                @if($payperview_details->status)
                    {{tr('paid')}}
                @else
                   {{tr('pending')}}
                @endif
                </td>

            </tr>

        @endforeach
    </table>

</body>

</html>