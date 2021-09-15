<!DOCTYPE html>
<html>

<head>
    <title>{{tr('live_videos_management')}}</title> 
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

            <th>{{tr('name')}}</th>

            <th>{{tr('title')}}</th>

            <th>{{tr('video_type')}}</th>

            <th>{{tr('payment')}}</th>

            <th>{{tr('is_streaming')}}</th>

            <th>{{tr('streamed_at')}}</th>

            <th>{{tr('viewers_count')}}</th>

            <th>{{tr('picture')}}</th>
            
            <th>{{tr('browser_name')}}</th>

            <th>{{tr('description')}}</th>

            <th>{{tr('start_time')}}</th>

            <th>{{tr('end_time')}}</th>

            <th>{{tr('no_of_minuts')}}</th>

            <th>{{tr('video_amount')}}</th>

            <th>{{tr('total_amount')}}</th>

            <th>{{tr('user_commission')}}</th>

            <th>{{tr('admin_commission')}}</th>
            
            <th>{{tr('created_at')}}</th>

            <th>{{tr('updated_at')}}</th>


        </tr>

        <!------ HEADER END  ------>

        @foreach($live_videos as $i => $video_details)
           
            <tr @if($i % 2 == 0) class="row_col_design" @endif >
                
                <td>{{$i+1}}</td>
                
                <td>{{$video_details->user ? $video_details->user->name : tr('user_not_available')}}</td>

                <td>
                    {{$video_details->title}}
                </td>

                <td>
                    {{$video_details->type}}
                </td>

                <td>
              
                    @if($video_details->payment_status)

                        {{tr('payment')}}

                    @else

                        {{tr('free')}}

                    @endif
                </td>

                <td>

                 @if($video_details->is_streaming)

                    @if(!$video_details->status)

                        {{tr('video_call_started')}}

                    @else

                        {{tr('video_call_ended')}}

                    @endif

                @else

                    {{tr('video_call_initiated')}}

                @endif

                </td>

                <td>{{$video_details->created_at ? $video_details->created_at->diffForHumans() : '-'}}</td>

                <td>
                {{$video_details->viewer_cnt ? $video_details->viewer_cnt : "0"}}
                </td>

                <td>
                    {{$video_details->user ?  $video_details->user->picture : asset('placeholder.png') }}
                </td>           

                <td>
                    {{$video_details->browser_name ? $video_details->browser_name : "-"}}
                </td>

                <td>
                {{$video_details->description}}
                </td>                

                <td>
                {{$video_details->start_time ? $video_details->start_time : "-"}}
                </td>

                <td>
                {{$video_details->end_time ? $video_details->end_time : "-"}}
                </td>

                <td>
                {{$video_details->no_of_minutes}}
                </td>

                <td>
                 {{formatted_amount($video_details->amount)}}
                </td>

                <td>
                    <?php $commission_split = each_video_payment($video_details->id);?>
                {{formatted_amount($commission_split['user_amount'])}}
                </td>

                <td>
                {{formatted_amount($commission_split['user_amount'])}}
                </td>

                <td>
                {{formatted_amount($commission_split['admin_amount'])}}
                </td>

                <td>
                {{common_date($video_details->created_at,Auth::guard('admin')->user()->timezone)}}
                </td>

                <td>
                {{common_date($video_details->updated_at,Auth::guard('admin')->user()->timezone)}}
                </td>

            </tr>

        @endforeach
    </table>

</body>

</html>