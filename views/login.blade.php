<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Login</title>
    <!-- See: https://wrapbootstrap.com/theme/authenty-login-signup-forms-WB03N7C1H -->

    <!-- Stylesheets -->
    <link href="{{ asset('assets/vendor/ctrl/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/ctrl/login/css/animation.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/ctrl/login/css/preview.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/ctrl/login/css/authenty.css') }}" rel="stylesheet">
    <style>
    .authenty.signin-main .wrap {
      padding-top: 40px;
    }
    .authenty.signin-main input[type="email"] {
      background-color: #404040;
      padding-left: 20px;
      border-radius: 0;
      border: none;
      margin-bottom: 10px;
      color: #ccc;
      height: 43px;
  }
    </style>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.1/css/font-awesome.min.css">
    
    <!-- Google Fonts -->
    <link href='http://fonts.googleapis.com/css?family=Roboto+Slab:400,700,300' rel='stylesheet' type='text/css'>
    <link href='http://fonts.googleapis.com/css?family=Open+Sans:300,400,700' rel='stylesheet' type='text/css'>

    
    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    
    <section id="authenty_preview">
      <section id="signin_main" class="authenty signin-main">
        <div class="section-content">
          <div class="wrap">
            <div class="container">   
              <div class="form-wrap">
                <div class="row">
                 @if ($logo)
                  <div class="title" data-animation="fadeInDown" data-animation-delay=".8s">
                      <img src="{{ $logo }}" style="height: 50px">
                    <!--<h1>CMS</h1>-->
                    <!--<h5>Please log in below.</h5>-->
                  </div>
                  @endif

                  {{-- This is only used for logins where the Ajax post fails, which is hopefully never --}}
                   @include('ctrl::form_errors')


                  <form class="ajax" role="form" method="post" action="{{ route('ctrl::post_login') }}">
                    {!! csrf_field() !!}
                    <input type="hidden" value="remember-me" name="remember">
                    <div id="form_1" data-animation="bounceIn">
                      <div class="form-header">
                        <i class="fa fa-user"></i>
                      </div>
                      <div class="form-main">
                        <div class="form-group">
                          <input type="email" id="un_1" class="form-control" placeholder="Email address" required="required"  name="email" value="{{ old('email') }}">
                          <input type="password" id="pw_1" class="form-control" placeholder="Password" required="required" name="password">
                        </div>
                        <button id="signIn_1" type="submit" class="btn btn-block signin">Sign In</button>
                      </div>
                      <div class="form-footer">
                        <div class="row">
                          <div class="col-xs-12 text-center">
                            <i class="fa fa-unlock-alt"></i>
                            <a href="#password_recovery" id="forgot">Forgotten your password?</a>
                          </div>
                          <!--
                          <div class="col-xs-5">
                            <i class="fa fa-check"></i>
                            <a href="#signup_window" id="signup_from_1">Sign Up</a>
                          </div>
                          -->
                        </div>
                      </div>
                    </div>
                  </form>   

                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    
    
      
    
      <section id="password_recovery" class="authenty password-recovery">
        <div class="section-content">
          <div class="wrap">
            <div class="container">
              <div class="form-wrap">
                <div class="row">
                  <div class="col-xs-12 col-sm-3 brand" data-animation="fadeInUp">
                    <!--
                    <h2>Authenty</h2>
                    <p>Authentication made beautiful</p>
                    -->
                    <img src="http://www.argos-support.co.uk/assets/img/design/argos-logo.png" alt="ARGOS">
                  </div>
                  <div class="col-sm-1 hidden-xs">
                    <div class="horizontal-divider"></div>
                  </div>
                  <div class="col-xs-12 col-sm-8 main" data-animation="fadeInLeft" data-animation-delay=".5s">
                    <h2>Forgotten your password?</h2>
                    <p>Enter your email address below, and we'll email you a reset link.</p>
                    <form>
                      <div class="form-group">
                        <input type="email" class="form-control" placeholder="Email address" required="required">
                      </div>
                      <div class="row">
                        <div class="col-xs-12 col-sm-4 col-sm-offset-8">
                          <button type="submit" class="btn btn-block reset">Reset Password</button>
                        </div>
                      </div>
                    </form> 
                  </div>
                </div>
                  
              </div>
            </div>
          </div>
        </div>
      </section>
      
    </section>  
    
    
    <!-- js library -->
    <script src="{{ asset('assets/vendor/ctrl/vendor/jquery/jquery-1.11.3.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/ctrl/vendor/jquery/jquery-ui-1.11.4.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/ctrl/vendor/bootstrap/js/bootstrap.min.js') }}"></script>

    <script src="{{ asset('assets/vendor/ctrl/login/js/waypoints.min.js') }}"></script>    
    <!-- Not needed, replaced by the Ajax code below
    <script src="{{ asset('assets/vendor/ctrl/login/js/authenty.js') }}"></script>
    -->
    <script src="{{ asset('assets/vendor/ctrl/login/js/scrollTo.min.js') }}"></script>    
    <!-- Not needed?
    <script src="js/preview/jquery.malihu.PageScroll2id.js"></script>
    <script src="js/preview/jquery.address-1.6.min.js"></script>
    <script src="js/preview/xinit.js"></script>
    -->
    
    <script src="{{ asset('assets/vendor/ctrl/vendor/jquery.form/jquery.form.min.js') }}"></script>
    
    <script>
      // Custom error handler based on /ctrl/js/forms.js
       // wait for the DOM to be loaded 
      $(document).ready(function() { 
          
        var options = { 
              dataType: 'json',
              success: function(responseText, statusText, xhr, $form)  { 
                redirect = responseText.redirect;  
                window.location.replace(redirect);                
              },
              error: function(jqXHR, textStatus, errorThrown ) { 
                // Don't even bother displaying the error message here; it's just "Login failed"
                if (jqXHR.status == 500) {
                  // Major error; it's possible to update Handler.php to provide a nicer error in error.exception
                  response = $.parseJSON(jqXHR.responseText);
                  if (response.error.exception) {
                    console.log(errorThrown+': '+response.error.exception);
                  }
                  else {
                    console.log('Error');
                  }
                }
                else {
                  $('#form_1 .fa-user').removeClass('success').addClass('fail');
                  $('#form_1').addClass('fail');
                }                
            }
          }; 
       
          $('form.ajax').ajaxForm(options);
          
      }); 



      (function($) {
        
        // get full window size
        $(window).on('load resize', function(){
            var w = $(window).width();
            var h = $(window).height();

            $('section').height(h);
        });   

        // scrollTo plugin
        $('#forgot').scrollTo({ easing: 'easeInOutQuint', speed: 1500 });
      
        // set focus on input
        var firstInput = $('section').find('input[type=text], input[type=email]').filter(':visible:first');
        
        if (firstInput != null) {
                firstInput.focus();
            }
        
        $('section').waypoint(function (direction) {
          var target = $(this).find('input[type=text], input[type=email]').filter(':visible:first');
          target.focus();
        }, {
            offset: 300
        }).waypoint(function (direction) {
          var target = $(this).find('input[type=text], input[type=email]').filter(':visible:first');
          target.focus();
        }, {
            offset: -400
        });
        
        
        // animation handler
        $('[data-animation-delay]').each(function () {
            var animationDelay = $(this).data("animation-delay");
            $(this).css({
                "-webkit-animation-delay": animationDelay,
                "-moz-animation-delay": animationDelay,
                "-o-animation-delay": animationDelay,
                "-ms-animation-delay": animationDelay,
                "animation-delay": animationDelay
            });
        });
        
        $('[data-animation]').waypoint(function (direction) {
            if (direction == "down") {
                $(this).addClass("animated " + $(this).data("animation"));
            }
        }, {
            offset: '90%'
        }).waypoint(function (direction) {
            if (direction == "up") {
                $(this).removeClass("animated " + $(this).data("animation"));
            }
        }, {
            offset: '100%'
        });
      
      })(jQuery);
    </script>
  </body>
</html>



<?php /*
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
  
    <title>Login</title>

    <!-- Bootstrap core CSS -->
    <link href="{{ asset('assets/vendor/ctrl/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">

    <!-- Custom styles for this template -->
    <style>
      body {
        padding-top: 40px;
        padding-bottom: 40px;
        background-color: #eee;
      }

      .form-signin {
        max-width: 330px;
        padding: 15px;
        margin: 0 auto;
      }
      .form-signin .form-signin-heading,
      .form-signin .checkbox {
        margin-bottom: 10px;
      }
      .form-signin .checkbox {
        font-weight: normal;
      }
      .form-signin .form-control {
        position: relative;
        height: auto;
        -webkit-box-sizing: border-box;
           -moz-box-sizing: border-box;
                box-sizing: border-box;
        padding: 10px;
        font-size: 16px;
      }
      .form-signin .form-control:focus {
        z-index: 2;
      }
      .form-signin input[type="email"] {
        margin-bottom: -1px;
        border-bottom-right-radius: 0;
        border-bottom-left-radius: 0;
      }
      .form-signin input[type="password"] {
        margin-bottom: 10px;
        border-top-left-radius: 0;
        border-top-right-radius: 0;
      }

    </style>

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>

  <body>

    <div class="container">

      <form class="form-signin ajax" role="form" method="post" action="{{ route('ctrl::post_login') }}">
        {!! csrf_field() !!}
        <h2 class="form-signin-heading">Please sign in</h2>

        @include('ctrl::form_errors')

        <input type="email" class="form-control" placeholder="Email address" required autofocus name="email" value="{{ old('email') }}">
        <input type="password" class="form-control" placeholder="Password" required name="password">
        <label class="checkbox">
          <input type="checkbox" value="remember-me" name="remember" @if (old('remember')) checked="checked" @endif> Remember me
        </label>
        <button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
      </form>

    </div> <!-- /container -->


    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="{{ asset('assets/vendor/ctrl/vendor/jquery/jquery-1.11.3.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/ctrl/vendor/bootstrap/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/ctrl/vendor/jquery.form/jquery.form.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/ctrl/js/forms.js') }}"></script>


  </body>
</html>
*/ ?>
