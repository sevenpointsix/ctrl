<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>CTRL</title>

    <!-- Bootstrap core CSS -->
    <link href="{{ asset('assets/vendor/ctrl/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.1/css/font-awesome.min.css">

    @yield('css')
    @stack('css') {{-- Individual form fields push to the stack --}}

    <!-- Fix header and footer -->
    <style>
    html {
      position: relative;
      min-height: 100%;
    }
    body {          
      margin-bottom: 70px;
      padding-top: 50px;
    }
    .footer {
      position: absolute;
      bottom: 0;
      width: 100%;
      /* Set the fixed height of the footer here */
      height: 50px;
      background-color: #f5f5f5;
    }
    .footer .text-muted {
      margin: 15px 0;
    }
	   </style>

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>

  <body>

    <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="{{ route('ctrl::dashboard') }}"><i class="fa fa-home fa-2x"></i></a>
        </div>
        <div class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            {{-- Don't think we need a "home" link
            <li class="active"><a href="{{ route('ctrl::dashboard') }}">Dashboard</a></li>
            --}}
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </div>

    <div class="container">

      @yield('content')

    </div><!-- /.container -->


    <footer class="footer">
      <div class="container">
        <p class="text-muted"><a href="{{ route('ctrl::logout') }}'"><i class="fa fa-power-off"></i> Logout</a></p>
      </div>
    </footer>


    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="{{ asset('assets/vendor/ctrl/vendor/jquery/jquery-1.11.3.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/ctrl/vendor/bootstrap/js/bootstrap.min.js') }}"></script>
    
    @yield('js')
    @stack('js') {{-- Individual form fields push to the stack --}}

  </body>
</html>
