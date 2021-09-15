@extends('layouts.admin')

@section('title', tr('revenue_system'))

@section('content-header',tr('payments'))

@section('breadcrumb')
<li><a href="{{route('admin.dashboard')}}"><i class="fa fa-dashboard"></i>{{tr('home')}}</a></li>
<li><i class="fa fa-money"></i> {{tr('payments')}}</li>
<li class="active"><i class="fa fa-money"></i> {{tr('revenue_system')}}</li>
@endsection

@section('content')

@include('notification.notify')

<div class="row">
  @if($total_revenue > 0.00)
  	<div class="col-md-6">

  	    <div class="box box-primary">

  	        <div class="box-header with-border">
  	            
                <b class="box-title title_css">@yield('title')</b>

  	            <div class="box-tools pull-right">
  	                
  	                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
  	                </button>

  	            </div>
  	        </div>

  	        <!-- /.box-header -->
            
    	        <div class="box-body">
    	            <div class="row">

    	                <div class="col-md-12">
    	                    <p class="text-center">
    	                        <strong></strong>
    	                    </p>
    	                    
    	                    <div class="chart-responsive">
    	                        <canvas id="subscribe_payments" height="200px"></canvas>
    	                    </div>
    	                </div>
    	            </div>
    	        
    	        </div>

    	        <div class="box-footer no-padding">
    	            <ul class="nav nav-pills nav-stacked">
    	                <li>
    	                    <a href="javascript:void(0);">
    	                        <strong class="text-red">{{tr('total_amount')}}</strong>
    	                        <span class="pull-right text-red">
    	                            <i class="fa fa-angle-right"></i>{{formatted_amount($total_revenue)}}
    	                        </span>
    	                    </a>
    	                </li>
    	          </ul>
    	        </div>
            
  	    </div>      
  	    
  	</div>

  @endif


	<div class="col-md-6">
	    <div class="box box-primary">

	        <div class="box-header with-border">
	            
	            <b class="box-title title_css">{{tr('video_subscribe_payments')}}</b>

	            <div class="box-tools pull-right">
	                
	                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
	                </button>
	                
	                <!-- <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button> -->
	            </div>
	        </div>

	        <!-- /.box-header -->

	        <div class="box-body">
	            <div class="row">

	                <div class="col-md-12">
	                    <p class="text-center">
	                        <strong></strong>
	                    </p>
	                    
	                    <div class="chart-responsive">
	                        <canvas id="video_subscribe_payments" height="200px"></canvas>
	                    </div>
	                </div>
	            </div>
	        
	        </div>

	        <div class="box-footer no-padding">
	            <ul class="nav nav-pills nav-stacked">
	                <li>
	                    <a href="javascript:void(0);">
	                        <strong class="text-red">{{tr('total_amount')}}</strong>
	                        <span class="pull-right text-red">
	                            <i class="fa fa-angle-right"></i> {{formatted_amount($video_amount)}}
	                        </span>
	                    </a>
	                </li>

	                <li>
	                    <a href="javascript:void(0);">
	                        <strong class="text-green">{{tr('total_admin_amount')}} </strong>
	                        <span class="pull-right text-green">
	                            <i class="fa fa-angle-right"></i> {{formatted_amount($admin_amount)}}
	                        </span>
	                    </a>
	                </li>

	                <li>
	                    <a href="javascript:void(0);">
	                        <strong class="text-yellow">{{tr('total_user_amount')}}</strong>
	                        <span class="pull-right text-yellow">
	                            <i class="fa fa-angle-right"></i> {{formatted_amount($user_amount)}}
	                        </span>
	                    </a>
	                </li>
	          </ul>
	        </div>
	    </div>      
	    
	</div>

    <div class="clearfix"></div>

  <div class="col-md-6">
      <div class="box">

          <div class="box-header with-border">
              
          <b class="box-title title_css">{{tr('vod_payments')}}</b>

              <div class="box-tools pull-right">
                  
                  <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                  </button>
                  
                  <!-- <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-times"></i></button> -->
              </div>
          </div>

          <!-- /.box-header -->

          <div class="box-body">
              <div class="row">

                  <div class="col-md-12">
                      <p class="text-center">
                          <strong></strong>
                      </p>
                      
                      <div class="chart-responsive">
                          <canvas id="vod_payments" height="200px"></canvas>
                      </div>
                  </div>
              </div>
          
          </div>

          <div class="box-footer no-padding">
              <ul class="nav nav-pills nav-stacked">
                  <li>
                      <a href="javascript:void(0);">
                          <strong class="text-red">{{tr('total_amount')}}</strong>
                          <span class="pull-right text-red">
                              <i class="fa fa-angle-right"></i> {{formatted_amount($vod_amount)}}
                          </span>
                      </a>
                  </li>

                  <li>
                      <a href="javascript:void(0);">
                          <strong class="text-green">{{tr('total_admin_amount')}} </strong>
                          <span class="pull-right text-green">
                              <i class="fa fa-angle-right"></i> {{formatted_amount($vod_admin_amt)}}
                          </span>
                      </a>
                  </li>

                  <li>
                      <a href="javascript:void(0);">
                          <strong class="text-yellow">{{tr('total_user_amount')}}</strong>
                          <span class="pull-right text-yellow">
                              <i class="fa fa-angle-right"></i> {{formatted_amount($vod_user_amt)}}
                          </span>
                      </a>
                  </li>
            </ul>

          </div>

      </div>    

  </div>

</div>
@endsection


@section('scripts')



<script type="text/javascript">
    

//-------------
  //- PIE CHART -
  //-------------
  // Get context with jQuery - using jQuery's .get() method.
  var pieChartCanvas = $("#subscribe_payments").get(0).getContext("2d");
  var pieChart = new Chart(pieChartCanvas);
  var PieData = [
    {
      value: {{$total_revenue}},
      color: "#00a65a",
      highlight: "#00a65a",
      label: "Total Subscription Amount"
    },
  ];
  var pieOptions = {
    //Boolean - Whether we should show a stroke on each segment
    segmentShowStroke: true,
    //String - The colour of each segment stroke
    segmentStrokeColor: "#fff",
    //Number - The width of each segment stroke
    segmentStrokeWidth: 1,
    //Number - The percentage of the chart that we cut out of the middle
    percentageInnerCutout: 50, // This is 0 for Pie charts
    //Number - Amount of animation steps
    animationSteps: 100,
    //String - Animation easing effect
    animationEasing: "easeOutBounce",
    //Boolean - Whether we animate the rotation of the Doughnut
    animateRotate: true,
    //Boolean - Whether we animate scaling the Doughnut from the centre
    animateScale: false,
    //Boolean - whether to make the chart responsive to window resizing
    responsive: true,
    // Boolean - whether to maintain the starting aspect ratio or not when responsive, if set to false, will take up entire container
    maintainAspectRatio: false,
    //String - A legend template
    legendTemplate: "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<segments.length; i++){%><li><span style=\"background-color:<%=segments[i].fillColor%>\"></span><%if(segments[i].label){%><%=segments[i].label%><%}%></li><%}%></ul>",
    //String - A tooltip template
    tooltipTemplate: "<%=label%> - $<%=value %>"
  };
  //Create pie or douhnut chart
  // You can switch between pie and douhnut using the method below.
  pieChart.Doughnut(PieData, pieOptions);
  //-----------------
  //- END PIE CHART -
  //-----------------

   //-----------------------
  //- MONTHLY SALES CHART -
  //-----------------------




//-------------
  //- PIE CHART -
  //-------------
  // Get context with jQuery - using jQuery's .get() method.
  var subscribe_canvas = $("#video_subscribe_payments").get(0).getContext("2d");
  var subscribeChart = new Chart(subscribe_canvas);
  var subscribeData = [
    {
      value: {{$admin_amount}},
      color: "#00a65a",
      highlight: "#00a65a",
      label: "Admin Commission"
    },
    {
      value: {{$user_amount}},
      color: "#f39c12",
      highlight: "#f39c12",
      label: "User Commission"
    }
  ];
  var subscribeOptions = {
    //Boolean - Whether we should show a stroke on each segment
    segmentShowStroke: true,
    //String - The colour of each segment stroke
    segmentStrokeColor: "#fff",
    //Number - The width of each segment stroke
    segmentStrokeWidth: 1,
    //Number - The percentage of the chart that we cut out of the middle
    percentageInnerCutout: 50, // This is 0 for Pie charts
    //Number - Amount of animation steps
    animationSteps: 100,
    //String - Animation easing effect
    animationEasing: "easeOutBounce",
    //Boolean - Whether we animate the rotation of the Doughnut
    animateRotate: true,
    //Boolean - Whether we animate scaling the Doughnut from the centre
    animateScale: false,
    //Boolean - whether to make the chart responsive to window resizing
    responsive: true,
    // Boolean - whether to maintain the starting aspect ratio or not when responsive, if set to false, will take up entire container
    maintainAspectRatio: false,
    //String - A legend template
    legendTemplate: "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<segments.length; i++){%><li><span style=\"background-color:<%=segments[i].fillColor%>\"></span><%if(segments[i].label){%><%=segments[i].label%><%}%></li><%}%></ul>",
    //String - A tooltip template
    tooltipTemplate: "<%=label%> - $<%=value %>"
  };
  //Create pie or douhnut chart
  // You can switch between pie and douhnut using the method below.
  subscribeChart.Doughnut(subscribeData, subscribeOptions);
  //-----------------
  //- END PIE CHART -
  //-----------------

   //-----------------------
  //- MONTHLY SALES CHART -
  //-----------------------


//-------------
  //- PIE CHART -
  //-------------
  // Get context with jQuery - using jQuery's .get() method.
  var subscribe_canvas = $("#vod_payments").get(0).getContext("2d");
  var subscribeChart = new Chart(subscribe_canvas);
  var subscribeData = [
    {
      value: {{$vod_user_amt}},
      color: "#00a65a",
      highlight: "#00a65a",
      label: "Admin Commission"
    },
    {
      value: {{$vod_user_amt}},
      color: "#f39c12",
      highlight: "#f39c12",
      label: "User Commission"
    }
  ];
  var subscribeOptions = {
    //Boolean - Whether we should show a stroke on each segment
    segmentShowStroke: true,
    //String - The colour of each segment stroke
    segmentStrokeColor: "#fff",
    //Number - The width of each segment stroke
    segmentStrokeWidth: 1,
    //Number - The percentage of the chart that we cut out of the middle
    percentageInnerCutout: 50, // This is 0 for Pie charts
    //Number - Amount of animation steps
    animationSteps: 100,
    //String - Animation easing effect
    animationEasing: "easeOutBounce",
    //Boolean - Whether we animate the rotation of the Doughnut
    animateRotate: true,
    //Boolean - Whether we animate scaling the Doughnut from the centre
    animateScale: false,
    //Boolean - whether to make the chart responsive to window resizing
    responsive: true,
    // Boolean - whether to maintain the starting aspect ratio or not when responsive, if set to false, will take up entire container
    maintainAspectRatio: false,
    //String - A legend template
    legendTemplate: "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<segments.length; i++){%><li><span style=\"background-color:<%=segments[i].fillColor%>\"></span><%if(segments[i].label){%><%=segments[i].label%><%}%></li><%}%></ul>",
    //String - A tooltip template
    tooltipTemplate: "<%=label%> - $<%=value %>"
  };
  //Create pie or douhnut chart
  // You can switch between pie and douhnut using the method below.
  subscribeChart.Doughnut(subscribeData, subscribeOptions);
  //-----------------
  //- END PIE CHART -
  //-----------------

   //-----------------------
  //- MONTHLY SALES CHART -
  //-----------------------
</script>

@endsection