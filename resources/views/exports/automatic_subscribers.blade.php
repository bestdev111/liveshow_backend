<!DOCTYPE html>
<html>

<head>
    <title>{{tr('automatic_subscribers')}}</title>
    <meta name="robots" content="noindex">
    <style type="text/css">
        table {
            font-family: arial, sans-serif;
            border-collapse: collapse;
        }

        .first_row_design {
            background-color: #653bc8;
            color: #ffffff;
        }

        .row_col_design {
            background-color: #cccccc;
        }

        th {
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

            <th>{{tr('username')}}</th>

            <th>{{tr('subscription_name')}}</th>

            <th>{{tr('amount')}}</th>

            <th>{{tr('expiry_date')}}</th>


        </tr>

        <!------ HEADER END  ------>


        @foreach($payments as $pay)

        @foreach($pay as $i=>$payment)
        <tr>

            <td>{{$i+1}}</td>

            <td>{{ $payment->user->name ?? ''}}</td>

            <td>
                {{ $payment->title ?? ''}}
            </td>

            <td>
                {{formatted_amount($payment->subscription->amount ?? '0.00')}}
            </td>

            <td>
                {{common_date($payment->expiry_date,Auth::guard('admin')->user()->timezone,'Y-m-d H:i:s')}}


            </td>

        </tr>

        @endforeach

        @endforeach
    </table>

</body>

</html>