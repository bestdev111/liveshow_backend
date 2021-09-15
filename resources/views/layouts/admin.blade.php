<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">

    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="{{Setting::get('meta_description')}}">

    <meta name="keywords" content="{{Setting::get('meta_keywords')}}">
    
    <meta name="author" content="{{Setting::get('meta_author')}}">

    <title>{{Setting::get('site_name')}} - @yield('title')</title>

    <meta name="robots" content="noindex">

    @yield('meta_tags')
    
    <!-- Select Multiple dropdown -->

    <link rel="stylesheet" href="{{ asset('theme/plugins/select2/select2.css')}}">

    <!-- Bootstrap 3.3.6 -->
    <link rel="stylesheet" href="{{asset('theme/bootstrap/css/bootstrap.css')}}">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tour/0.11.0/css/bootstrap-tour.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{asset('theme/dist/css/AdminLTE.css')}}">
    <!-- AdminLTE Skins. Choose a skin from the css/skins
       folder instead of downloading all of them to reduce the load. -->
    <link rel="stylesheet" href="{{asset('theme/dist/css/skins/_all-skins.css')}}">
    <!-- iCheck -->
<!--     <link rel="stylesheet" href="{{asset('theme/plugins/iCheck/flat/blue.css')}}">
 -->    <!-- iCheck -->
<!--     <link rel="stylesheet" href="{{asset('theme/plugins/iCheck/square/blue.css')}}"> -->
    <!-- Morris chart -->
    <link rel="stylesheet" href="{{asset('theme/plugins/morris/morris.css')}}">
    <!-- jvectormap -->
    <link rel="stylesheet" href="{{asset('theme/plugins/jvectormap/jquery-jvectormap-1.2.2.css')}}">
    <!-- Date Picker -->
    <link rel="stylesheet" href="{{asset('theme/plugins/datepicker/datepicker3.css')}}">
    <!-- Daterange picker -->
    <link rel="stylesheet" href="{{asset('theme/plugins/daterangepicker/daterangepicker.css')}}">
    <!-- bootstrap wysihtml5 - text editor -->
    <link rel="stylesheet" href="{{asset('theme/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css')}}">

    <!-- Select2  -->
    <link rel="stylesheet" href="{{ asset('theme/plugins/select2/select2.css')}}">

    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('theme/plugins/datatables/dataTables.bootstrap.css')}}">

    <link rel="stylesheet" href="{{ asset('theme/dist/css/custom.css')}}">

    <link rel="stylesheet" href="{{asset('theme/plugins/datepicker/datepicker3.css')}}">

    <link rel="shortcut icon" type="image/png" href="{{Setting::get('site_icon' , asset('img/favicon.png'))}}"/>

    @yield('styles')

    <?php echo Setting::get('header_scripts'); ?>

</head>

<body class="hold-transition skin-blue sidebar-mini login-page">

        <div class="{{Auth::guard('admin')->check() ? 'wrapper' : ''}}">

            @if(Auth::guard('admin')->check())

                @include('layouts.admin.header')

                @include('layouts.admin.sidebar')

                <div class="content-wrapper">

                    <section class="content-header">

                        <h1>@yield('content-header')<small>@yield('content-sub-header')</small></h1>

                        <ol class="breadcrumb">@yield('breadcrumb')</ol>
                        
                    </section>
                    <!-- Main content -->
                    <section class="content">
                        @yield('content')
                    </section>

                </div>
                @include('layouts.admin.footer')

            @else

                @yield('content')

            @endif

        </div>

    </div>


    <!-- jQuery 2.2.3 -->
    <script src="{{asset('theme/plugins/jQuery/jquery-2.2.3.min.js')}}"></script>
    <!-- Bootstrap 3.3.6 -->
    <script src="{{asset('theme/bootstrap/js/bootstrap.min.js')}}"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tour/0.11.0/js/bootstrap-tour.min.js"></script>

    <!-- iCheck -->
<!--     <script src="{{asset('theme/plugins/iCheck/icheck.min.js')}}"></script>
 -->

    <script src="{{asset('theme/plugins/datatables/jquery.dataTables.min.js')}}"></script>

    <script src="{{asset('theme/plugins/datatables/dataTables.bootstrap.min.js')}}"></script>

    <!-- Select2 -->
    <script src="{{asset('theme/plugins/select2/select2.full.min.js')}}"></script>
    <!-- InputMask -->
    <script src="{{asset('theme/plugins/input-mask/jquery.inputmask.js')}}"></script>
    <script src="{{asset('theme/plugins/input-mask/jquery.inputmask.date.extensions.js')}}"></script>

    <script src="{{asset('theme/plugins/input-mask/jquery.inputmask.extensions.js')}}"></script>

    <!-- SlimScroll -->
    <script src="{{asset('theme/plugins/slimScroll/jquery.slimscroll.min.js')}}"></script>
    <!-- FastClick -->
    <script src="{{asset('theme/plugins/fastclick/fastclick.js')}}"></script>
    <!-- AdminLTE App -->
    <script src="{{asset('theme/dist/js/app.min.js')}}"></script>

    <!-- jvectormap -->
    <script src="{{asset('theme/plugins/jvectormap/jquery-jvectormap-1.2.2.min.js')}}"></script>

    <script src="{{asset('theme/plugins/jvectormap/jquery-jvectormap-world-mill-en.js')}}"></script>

    <script src="{{asset('theme/plugins/chartjs/Chart.min.js')}}"></script>

    <script src = "{{asset('theme/plugins/datepicker/bootstrap-datepicker.js')}}"></script>

    <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
    <!-- <script src="{{asset('theme/dist/js/pages/dashboard2.js')}}"></script> -->

    <script src="{{asset('theme/dist/js/demo.js')}}"></script>

    <!-- page script -->

    <script type="text/javascript">
        
    $(document).ready(function() {
        $('#expiry_date').datepicker({
            autoclose:true,
            format : 'dd-mm-yyyy',
            startDate: 'today',
        });
    });

    </script>

    <script>

        $(function () {

            $('.select2').select2();

            $("#example1").DataTable();

            $(".datatable-withoutpagination").DataTable({
                 "paging": false,
                 "lengthChange": false,
                 "language": {
                       "info": ""
                }
            });

            $("#datatable-withoutpagination").DataTable({
                 "paging": false,
                 "lengthChange": false,
                 "language": {
                       "info": ""
                }
            });
            

            $('#example2').DataTable({
                "paging": true,
                "lengthChange": false,
                "searching": false,
                "ordering": true,
                "info": true,
                "autoWidth": true,
            });

          /*  $('input').iCheck({
                checkboxClass: 'icheckbox_square-blue',
                radioClass: 'iradio_square-blue',
                increaseArea: '20%' // optional
            });*/
        });

        @if(isset($page))
            $("#{{$page}}").addClass("active");
            @if(isset($sub_page)) $("#{{$sub_page}}").addClass("active"); @endif
        @endif

    </script>   

    <script type="text/javascript">
        $(function(){

             //Initialize Select2 Elements
            $(".select2").select2();
        });
    </script>
    
    <?php echo Setting::get('body_scripts'); ?>

    @yield('scripts')
</body>
</html>
