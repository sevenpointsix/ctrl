<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>CTRL</title>

    <!-- Bootstrap core CSS -->
    <link href="{{ asset('assets/vendor/ctrl/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Font Awesome -->
    {{-- Use the CDN... see https://cdn.fontawesome.com/embed-codes, login as chris@sevenpointsix.io
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.1/css/font-awesome.min.css">
    --}}
    {{-- <script src="https://use.fontawesome.com/ededc99dd3.js"></script> --}}{{-- JS is very slow, CSS is quicker --}}
    @if (app()->environment('local'))
      <link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/font-awesome/css/font-awesome.min.css') }}">
    @else
      <link rel="stylesheet" href="https://use.fontawesome.com/ededc99dd3.css">
    @endif
    <!-- Animate.css, used by notify -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/vendor/animate.css/animate.min.css') }}">

    @yield('css')
    @stack('css') {{-- Individual form fields push to the stack --}}

    <link rel="stylesheet" href="{{ asset('assets/vendor/ctrl/css/main.css') }}">

    @if (view()->exists('ctrl_custom::custom_css'))
      @include('ctrl_custom::custom_css')
    @endif

    <!-- Fix header and footer -->
    <style>

	   </style>

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

    {{-- ROLLBAR --}}
    @if (env('ROLLBAR_CLIENT_TOKEN'))
      <script>
        var _rollbarConfig = {
            accessToken: "{{ env('ROLLBAR_CLIENT_TOKEN') }}",
            captureUncaught: true,
            captureUnhandledRejections: true,
            payload: {
                environment: "{{ app()->environment() }}"
            }
        };
        // Rollbar Snippet
        !function(r){function e(t){if(o[t])return o[t].exports;var n=o[t]={exports:{},id:t,loaded:!1};return r[t].call(n.exports,n,n.exports,e),n.loaded=!0,n.exports}var o={};return e.m=r,e.c=o,e.p="",e(0)}([function(r,e,o){"use strict";var t=o(1).Rollbar,n=o(2);_rollbarConfig.rollbarJsUrl=_rollbarConfig.rollbarJsUrl||"https://d37gvrvc0wt4s1.cloudfront.net/js/v1.9/rollbar.min.js";var a=t.init(window,_rollbarConfig),i=n(a,_rollbarConfig);a.loadFull(window,document,!_rollbarConfig.async,_rollbarConfig,i)},function(r,e){"use strict";function o(r){return function(){try{return r.apply(this,arguments)}catch(e){try{console.error("[Rollbar]: Internal error",e)}catch(o){}}}}function t(r,e,o){window._rollbarWrappedError&&(o[4]||(o[4]=window._rollbarWrappedError),o[5]||(o[5]=window._rollbarWrappedError._rollbarContext),window._rollbarWrappedError=null),r.uncaughtError.apply(r,o),e&&e.apply(window,o)}function n(r){var e=function(){var e=Array.prototype.slice.call(arguments,0);t(r,r._rollbarOldOnError,e)};return e.belongsToShim=!0,e}function a(r){this.shimId=++c,this.notifier=null,this.parentShim=r,this._rollbarOldOnError=null}function i(r){var e=a;return o(function(){if(this.notifier)return this.notifier[r].apply(this.notifier,arguments);var o=this,t="scope"===r;t&&(o=new e(this));var n=Array.prototype.slice.call(arguments,0),a={shim:o,method:r,args:n,ts:new Date};return window._rollbarShimQueue.push(a),t?o:void 0})}function l(r,e){if(e.hasOwnProperty&&e.hasOwnProperty("addEventListener")){var o=e.addEventListener;e.addEventListener=function(e,t,n){o.call(this,e,r.wrap(t),n)};var t=e.removeEventListener;e.removeEventListener=function(r,e,o){t.call(this,r,e&&e._wrapped?e._wrapped:e,o)}}}var c=0;a.init=function(r,e){var t=e.globalAlias||"Rollbar";if("object"==typeof r[t])return r[t];r._rollbarShimQueue=[],r._rollbarWrappedError=null,e=e||{};var i=new a;return o(function(){if(i.configure(e),e.captureUncaught){i._rollbarOldOnError=r.onerror,r.onerror=n(i);var o,a,c="EventTarget,Window,Node,ApplicationCache,AudioTrackList,ChannelMergerNode,CryptoOperation,EventSource,FileReader,HTMLUnknownElement,IDBDatabase,IDBRequest,IDBTransaction,KeyOperation,MediaController,MessagePort,ModalWindow,Notification,SVGElementInstance,Screen,TextTrack,TextTrackCue,TextTrackList,WebSocket,WebSocketWorker,Worker,XMLHttpRequest,XMLHttpRequestEventTarget,XMLHttpRequestUpload".split(",");for(o=0;o<c.length;++o)a=c[o],r[a]&&r[a].prototype&&l(i,r[a].prototype)}return e.captureUnhandledRejections&&(i._unhandledRejectionHandler=function(r){var e=r.reason,o=r.promise,t=r.detail;!e&&t&&(e=t.reason,o=t.promise),i.unhandledRejection(e,o)},r.addEventListener("unhandledrejection",i._unhandledRejectionHandler)),r[t]=i,i})()},a.prototype.loadFull=function(r,e,t,n,a){var i=function(){var e;if(void 0===r._rollbarPayloadQueue){var o,t,n,i;for(e=new Error("rollbar.js did not load");o=r._rollbarShimQueue.shift();)for(n=o.args,i=0;i<n.length;++i)if(t=n[i],"function"==typeof t){t(e);break}}"function"==typeof a&&a(e)},l=!1,c=e.createElement("script"),p=e.getElementsByTagName("script")[0],d=p.parentNode;c.crossOrigin="",c.src=n.rollbarJsUrl,c.async=!t,c.onload=c.onreadystatechange=o(function(){if(!(l||this.readyState&&"loaded"!==this.readyState&&"complete"!==this.readyState)){c.onload=c.onreadystatechange=null;try{d.removeChild(c)}catch(r){}l=!0,i()}}),d.insertBefore(c,p)},a.prototype.wrap=function(r,e){try{var o;if(o="function"==typeof e?e:function(){return e||{}},"function"!=typeof r)return r;if(r._isWrap)return r;if(!r._wrapped){r._wrapped=function(){try{return r.apply(this,arguments)}catch(e){throw"string"==typeof e&&(e=new String(e)),e._rollbarContext=o()||{},e._rollbarContext._wrappedSource=r.toString(),window._rollbarWrappedError=e,e}},r._wrapped._isWrap=!0;for(var t in r)r.hasOwnProperty(t)&&(r._wrapped[t]=r[t])}return r._wrapped}catch(n){return r}};for(var p="log,debug,info,warn,warning,error,critical,global,configure,scope,uncaughtError,unhandledRejection".split(","),d=0;d<p.length;++d)a.prototype[p[d]]=i(p[d]);r.exports={Rollbar:a,_rollbarWindowOnError:t}},function(r,e){"use strict";r.exports=function(r,e){return function(o){if(!o&&!window._rollbarInitialized){var t=window.RollbarNotifier,n=e||{},a=n.globalAlias||"Rollbar",i=window.Rollbar.init(n,r);i._processShimQueue(window._rollbarShimQueue||[]),window[a]=i,window._rollbarInitialized=!0,t.processPayloads()}}}}]);
        // End Rollbar Snippet
        </script>
      @endif

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
          <a class="navbar-brand" href="{{ route('ctrl::dashboard') }}"><i class="fa fa-lg fa-home"></i></a>
        </div>
        <div class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            {{-- Don't think we need a "home" link
            <li class="active"><a href="{{ route('ctrl::dashboard') }}">Dashboard</a></li>
            --}}
            @foreach ($menu_links as $menu_title=>$links)

              @if (count($links) == 1)
              <li><a href="{{ route('ctrl::list_objects',$links[0]['id']) }}">{!! $links[0]['icon'] !!}{{ $menu_title }}</a></li>
              @else

                <li class="dropdown">
                  <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">{{ $menu_title }} <span class="caret"></span></a>
                  <ul class="dropdown-menu">
                  @foreach ($links as $link)
                  <li><a href="{{ route('ctrl::list_objects',$link['id']) }}">{!! $link['icon'] !!}{{ $link['title'] }}</a></li>
                  @endforeach
                  </ul>
                </li>
              @endif
            @endforeach
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </div>

    <div class="container">

      @yield('content')

    </div><!-- /.container -->


    <footer class="footer">
      <div class="container">
        <p class="text-muted"><a href="{{ route('ctrl::logout') }}"><i class="fa fa-power-off"></i> Logout</a></p>
      </div>
    </footer>


    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="{{ asset('assets/vendor/ctrl/vendor/jquery/jquery-1.11.3.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/ctrl/vendor/bootstrap/js/bootstrap.min.js') }}"></script>

    <script src="{{ asset('assets/vendor/ctrl/vendor/handlebars/handlebars-v4.0.5.js') }}"></script> <!-- Required by various pages -->

    <script src="{{ asset('assets/vendor/ctrl/vendor/bootstrap-notify/bootstrap-notify.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/ctrl/vendor/bootbox/bootbox.min.js') }}"></script>

    <script src="{{ asset('assets/vendor/ctrl/js/main.js') }}"></script>

    @yield('js')

    @if (view()->exists('ctrl_custom::custom_js'))
      @include('ctrl_custom::custom_js')
    @endif

    @include('ctrl::notify')

    @stack('js') {{-- Individual form fields push to the stack --}}

  </body>
</html>
