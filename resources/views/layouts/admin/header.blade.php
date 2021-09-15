  <header class="main-header">
    <!-- Logo -->
    <a href="{{route('admin.dashboard')}}" class="logo" style="background-color: #653bc8">
      <!-- mini logo for sidebar mini 50x50 pixels -->
      <span class="logo-mini"><b></b></span>
      <!-- logo for regular state and mobile devices -->
      <span class="logo-lg"><img alt="{{Setting::get('site_name')}}" src="{{Setting::get('site_icon')}}" class="nav-logo"><b>{{Setting::get('site_name')}}</b></span>
    </a>
    <!-- Header Navbar: style can be found in header.less -->
    <nav class="navbar navbar-static-top">
      <!-- Sidebar toggle button-->
      <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button" >
        <span class="sr-only">Toggle navigation</span>
      </a>

      <div class="navbar-custom-menu">

        <ul class="nav navbar-nav">

            <li class="dropdown notifications-menu">

                <a href="{{Setting::get('ANGULAR_URL')}}" class="btn bg-dark-blue btn-flat text-uppercase" target="_blank" style="background: #ff3d63"> 

                    <i class="fa fa-globe"></i>&nbsp;&nbsp;{{tr('visit_website')}}

                </a>

            </li>

            <!-- <li class="dropdown notifications-menu">

            <a href="javascript:void(0);"  class="btn btn-warning" id="start-tour" target="_blank"> 
            Take an Tour
            </a>

            </li> -->

            <!-- User Account: style can be found in dropdown.less -->
            <li class="dropdown user user-menu">

                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    <img src="{{Auth::guard('admin')->user()->picture ?Auth::guard('admin')->user()->picture : asset('placeholder.png')}}" class="user-image" alt="User Image">
                    <span class="hidden-xs">{{Auth::guard('admin')->user()->name}}</span>
                </a>

                <ul class="dropdown-menu">
                    <!-- User image -->
                    <li class="user-header" style="background-color: #653bc8 !important; ">
                        <img src="{{Auth::guard('admin')->user()->picture ? Auth::guard('admin')->user()->picture : asset('placeholder.png')}}" class="img-circle" alt="User Image">

                        <p>
                            {{Auth::guard('admin')->user()->name}} - {{tr('admin')}}
                            <small>{{Auth::guard('admin')->user()->email}}</small>
                        </p>
                    </li>
                    
                    <!-- Menu Footer-->
                    <li class="user-footer">
                        <div class="pull-left">
                            <a href="{{route('admin.profile')}}" class="btn btn-primary btn-flat">{{tr('profile')}}</a>
                        </div>
                        <div class="pull-right">
                            <a href="{{route('admin.logout')}}" class="btn btn-primary btn-flat" onclick="return confirm(&quot;{{ tr('logout_confirmation') }}&quot;)" >{{tr('logout')}}</a>
                        </div>
                    </li>
                </ul>
            </li>
        </ul>
      </div>
    </nav>
  </header>


    