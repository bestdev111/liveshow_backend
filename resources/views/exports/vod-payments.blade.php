<!DOCTYPE html>
<html>

<head>
    <title>{{tr('vod_management')}}</title>
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

            <th>{{tr('id')}}</th>

            <th>{{tr('title')}}</th>

            <th>{{tr('name')}}</th>

            <th>{{tr('payment_id')}}</th>

            <th>{{tr('amount')}}</th>

            <th>{{tr('admin_commission')}}</th>

            <th>{{tr(('user_commission'))}}</th>

            <th>{{tr(('payment_mode'))}}</th>

            <th>{{tr('coupon_code')}}</th>

            <th>{{tr('coupon_amount')}}</th>

            <th>{{tr('plan_amount')}}</th>

            <th>{{tr('final_amount')}}</th>

            <th>{{tr('is_coupon_applied')}}</th>

            <th>{{tr('coupon_reason')}}</th>

            <th>{{tr('status')}}</th>

        </tr>

        <!------ HEADER END  ------>

        @foreach($data as $i => $vod_details)

            <tr @if($i % 2 == 0) class="row_col_design" @endif >

                <td>{{$i+1}}</td>

                <td>
                    {{$vod_details->vodVideo ? $vod_details->vodVideo->title : "-" }}
                </td>

                <td> 
                    {{$vod_details->userVideos ? $vod_details->userVideos->name : "-" }}
                    
                </td>

                <td>
                   {{$vod_details->payment_id}}
                </td>

                <td> {{formatted_amount($vod_details->amount)}}</td>


                <td>
               {{formatted_amount($vod_details->admin_amount)}}
                </td>
                
                <td>
                {{formatted_amount($vod_details->user_amount)}}
                </td>

                <td>
                {{$vod_details->payment_mode}}
                </td>

                <td>{{$vod_details->coupon_code ? $vod_details->coupon_code : "-"}}</td>

                <td>{{formatted_amount($vod_details->coupon_amount)}}</td>

                <td>{{formatted_amount($vod_details->subscription_amount)}}</td>

                <td>{{formatted_amount($vod_details->amount)}}</td>
                
                <td>
                    @if($vod_details->is_coupon_applied)
                        {{tr('yes')}}
                    @else
                        {{tr('no')}}
                    @endif
                </td>
                <td>
                    {{$vod_details->coupon_reason ? $vod_details->coupon_reason : '-'}}
                </td>

                <td>
                @if($vod_details->status)
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