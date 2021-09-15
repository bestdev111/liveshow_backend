<!DOCTYPE html>
<html>

<head>
    <title>{{tr('users_management')}}</title> 
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

            <th>{{tr('username')}}</th>

            <th>{{tr('email')}}</th>

            <th>{{tr('picture')}}</th>

            <th>{{tr('chat_picture')}}</th>

            <th>{{tr('is_content_creator')}}</th>

            <th>{{tr('followers')}}</th>

            <th>{{tr('followings')}}</th>

            <th>{{tr('status')}}</th>

            <th>{{tr('user_type')}}</th>

            <th>{{tr('social_unique_id')}}</th>

            <th>{{tr('blocked_me_by_others')}}</th>

            <th>{{tr('blocked_users_by_me')}}</th>

            <th>{{tr('description')}}</th>

            <th>{{tr('payment_mode')}}</th>

            <th>{{tr('is_verified')}}</th>

            <th>{{tr('total_live_videos')}}</th>

            <th>{{tr('paid_videos')}}</th>

            <th>{{tr('free_videos')}}</th>

            <th>{{tr('paypal_email')}}</th>

            <th>{{tr('total_admin_amount')}}</th>

            <th>{{tr('total_user_amount')}}</th>

            <th>{{tr('total')}}</th>

            <th>{{tr('wallet_balance')}}</th>

            <th>{{tr('paid_amount')}}</th>

            <th>{{tr('login_status')}}</th>

            <th>{{tr('push_status')}}</th>

            <th>{{tr('no_of_days')}}</th>

            <th>{{tr('expiry_date')}}</th>

            <th>{{tr('register_type')}}</th>

            <th>{{tr('login_type')}}</th>

            <th>{{tr('device_type')}}</th>

            <th>{{tr('joined')}}</th>

            <th>{{tr('updated')}}</th>


        </tr>

        <!------ HEADER END  ------>

        @foreach($users as $i => $user_details)

            <tr @if($i % 2 == 0) class="row_col_design" @endif >

                <td>{{$i+1}}</td>

                <td>{{$user_details->name}}</td>

                <td>{{$user_details->email}}</td>


                <td>
                    @if($user_details->picture) {{$user_details->picture}} @else {{asset('placeholder.png')}} @endif
                </td>


                <td>
                    @if($user_details->chat_picture) {{$user_details->chat_picture}} @else {{asset('placeholder.png')}} @endif
                </td>

                <td>
                    @if($user_details->is_content_creator)

                        {{tr('yes')}}

                    @else

                        {{tr('no')}}

                    @endif
                </td>

                <td>
                    {{@count(followers($user_details->id))}}
                </td>

                <td>
                    {{@count(followings($user_details->id))}}
                </td>

                <td>
                    @if($user_details->status) 

                        {{tr('approved')}}

                    @else 

                        {{tr('pending')}}

                    @endif
                </td>

                <td>
                    @if($user_details->user_type)

                        {{tr('premium')}}

                    @else 

                        {{tr('normal')}}

                    @endif
                </td>

                <td>
                    {{$user_details->social_unique_id ? $user_details->social_unique_id : "-"}}
                </td>

                <td>
                    {{@count($user_details->getBlockUsers)}} {{tr('users')}}
                </td>
                
                <td>
                    {{@count($user_details->blockedUsersByme)}} {{tr('users')}}
                </td>

                <td>{{$user_details->description}}</td>

                <td>
                    {{$user_details->payment_mode ? $user_details->payment_mode : "-"}}
                </td>

                <td>
                    @if($user_details->is_verified)
                        {{tr('yes')}}
                    @else
                        {{tr('no')}}
                    @endif
                </td>

                <td>
                    {{@count($user_details->getLiveVideos)}}
                </td>

                <td>
                    {{$user_details->getLiveVideos ? $user_details->getPaymentvideos->count() : "0"}}
                </td>

                <td>
                    {{$user_details->getLiveVideos ? $user_details->getFreevideos->count() : "0"}}
                </td>

                <td>
                    {{$user_details->paypal_email}}
                </td>

                <td>
                    {{formatted_amount($user_details->total_admin_amount)}}
                </td>

                <td>
                    {{formatted_amount($user_details->total_user_amount)}}
                </td>

                <td>
                    {{formatted_amount($user_details->userRedeem ? $user_details->userRedeem->total : '0.00')}}
                </td>

                
                <td>
                    {{formatted_amount($user_details->userRedeem ? $user_details->userRedeem->remaining : '0.00')}}
                </td>

                <td>
                    {{formatted_amount($user_details->userRedeem ? $user_details->userRedeem->paid : '0.00')}}
                </td>


                <td>
                    @if($user_details->login_status)
                        {{tr('login')}}
                    @else
                        {{tr('logout')}}
                    @endif
                </td>

                <td>
                    @if($user_details->push_status)
                        {{tr('on')}}
                    @else
                        {{tr('off')}}
                    @endif
                </td>

                <td>
                    {{$user_details->no_of_days}}
                </td>

                <td>
                    {{$user_details->expiry_date}}
                </td>

                <td>
                    {{$user_details->register_type ? $user_details->register_type : "-"}}
                </td>

                <td>
                    {{$user_details->login_by ? $user_details->login_by : "-"}}
                </td>

                <td>
                    {{$user_details->device_type ? $user_details->device_type : "-"}}
                </td>

                <td>
                    {{common_date($user_details->created_at , Auth::guard('admin')->user()->timezone)}}
                </td>

                <td>
                    {{common_date($user_details->updated_at,Auth::guard('admin')->user()->timezone)}}
                </td>
            </tr>

        @endforeach
    </table>

</body>

</html>