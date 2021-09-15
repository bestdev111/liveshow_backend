<!DOCTYPE html>
<html>

<head>
    <title>{{tr('vod_management')}}</title>
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

            <th>{{tr('published_time')}}</th>

            <th>{{tr('publish_status')}}</th>

            <th>{{tr('ppv_status')}}</th>

            <th>{{tr('image')}}</th>

            <th>{{tr('video')}}</th>

            <th>{{tr('description')}}</th>

            <th>{{tr('ppv_amount')}}</th>

            <th>{{tr('total_amount')}}</th>

            <th>{{tr('admin_amount')}}</th>

            <th>{{tr('user_amount')}}</th>

            <th>{{tr('uploaded_to')}}</th>

            <th>{{tr('uploaded_by')}}</th>

            <th>{{tr('user_status')}}</th>

            <th>{{tr('admin_status')}}</th>

            <th>{{tr('created_at')}}</th>
            
            <th>{{tr('updated_at')}}</th>

        </tr>

        <!------ HEADER END  ------>

        @foreach($data as $i => $vod_video_details)

            <tr @if($i % 2 == 0) class="row_col_design" @endif >

                <td>{{$i+1}}</td>

                <td>
                    {{$vod_video_details->title}}
                </td>

                <td>
                    {{date('d-m-Y h:i a', strtotime($vod_video_details->publish_time))}}
                </td>
                <td>
                    @if($vod_video_details->publish_status > 0)
                        {{tr('published')}}

                    @else
                        {{tr('not_yet_published')}}
                    @endif
                </td>

                <td> 
                    @if($vod_video_details->amount != 0)

                        {{tr('yes')}}

                    @else
                        {{tr('no')}}

                    @endif
                    
                </td>
                <td>
                    {{$vod_video_details->image}}
                </td>

                <td>
                    {{$vod_video_details->video}}
                </td>

                <td>
                    {{$vod_video_details->description}}
                </td>

                <td>
                  {{formatted_amount($vod_video_details->amount)}}

                </td>

                <td> 
                    {{formatted_amount($vod_video_details->admin_amount+$vod_video_details->user_amount)}}
                </td>


                <td>
                {{formatted_amount($vod_video_details->admin_amount)}}
                </td>
                
                <td>
                {{formatted_amount($vod_video_details->user_amount)}}
                </td>

                <td>{{$vod_video_details->getUser ? $vod_video_details->getUser->name : tr('user_not_available') }}</td>

                <td>
                {{$vod_video_details->created_by ? $vod_video_details->created_by : 'User'}}
                </td>

                <td>
                    @if($vod_video_details->status)

                        {{tr('approve')}}

                    @else

                        {{tr('pending')}}

                    @endif
                </td>

                <td>
                    @if($vod_video_details->admin_status)

                       {{tr('approve')}}

                    @else

                        {{tr('pending')}}

                    @endif
                </td>

                <td>{{$vod_video_details->created_at ? $vod_video_details->created_at->diffForHumans() : '' }}</td>

                <td>{{$vod_video_details->updated_at ? $vod_video_details->updated_at->diffForHumans() : '' }}</td>
    
            </tr>

        @endforeach
    </table>

</body>

</html>