@php
    $lng = str_replace('_', '-', app()->getLocale());
@endphp
<!doctype html>
<html lang="{{ $lng }}">
<head>
    <meta HTTP-EQUIV="Pragma" content="no-cache">
    <meta HTTP-EQUIV="Expires" content="{{ gmdate('D,d M Y H:i:s') }}">
    <meta HTTP-EQUIV="Last-Modified" content="{{ gmdate('D,d M Y H:i:s') }}">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="author" content="Oleg Didenko">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate, proxy-revalidate">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Clients Area">
    <meta name="theme-color" content="#3f51b5">
    <meta name="application-name" content="Site">
    <meta name="mobile-web-app-capable" content="yes">
    <!-- meta property="og:image" content="img/fb.png" -->
    <!--link rel="manifest" href="/manifest.json" -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400,700">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="/favicon.ico">
    <link href="/css/lib/bootstrap.min.css" media="screen" rel="stylesheet" type="text/css">
    <link href="/css/lib/font-awesome.min.css" media="screen" rel="stylesheet" type="text/css">
    <link href="{{ mix('css/const.css') }}" media="screen" rel="stylesheet" type="text/css">
    <link href="{{ mix('css/app.css') }}" media="screen" rel="stylesheet" type="text/css">
    <script>
        var _gaq = _gaq || [];
        _gaq.push(['_setAccount', 'UA-548XXXXX-3']);
        _gaq.push(['_setDomainName', 'none']);
        _gaq.push(['_setAllowLinker', true]);
        _gaq.push(['_setLocalRemoteServerMode']);
        _gaq.push(['_trackPageview']);

        (function() {
          var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
          ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
          var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
        })();
    </script>
    <script>
        var gLng = "{{ str_replace('_', '-', app()->getLocale()) }}";
    </script>
    <script src="{{ mix('js/lib/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ mix('js/lib/js-form-validator/js-form-validator.min.js') }}"></script>
    <script src="/wc/wc-busy-ui.min.js"></script>
    @stack('scripts')
    <script src="{{ mix('js/app.js') }}"></script>
    <title>Customer Area</title>
</head>
<body class="lng-uk">
    <header class="container">
        <div class="row">
            <div class="col-auto">
                <img src="{{ mix('images/logo-h.png') }}">
            </div>
            <h5 class="col-auto text-muted">{!! implode('<br>',explode(' ',__('cab.MY_ACC'))) !!}</h5>  
            <div class="col"></div>  
            <div class="col-auto cab-lng-switch">
                <a href="/lng/uk">UK</a>
                <a href="/lng/en">EN</a>
                <a href="/lng/ru">RU</a>
            </div>  
            <div class="col-auto">
                <a href="{!! route('login') !!}">
                    <i class="fa fa-sign-out text-muted" style="font-size:32px;margin-top:6px;" title="{{ __("cab.EXIT") }}"></i>
                </a>
            </div>  
        </div>
    </header>
    <nav class="container navbar navbar-expand-sm navbar-light bg-light">
        <div class="container-fluid">
          <div class="row">  
              <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                  <span class="navbar-toggler-icon"></span>
              </button>
              <div class="col-auto collapse navbar-collapse" id="navbarSupportedContent">
                  <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                      <li class="nav-item">
                          <a class="nav-link" aria-current="page" href="{!! route('card') !!}">{{ mb_strtoupper(__("cab.ACC")) }}</a>
                      </li>
                      <li class="nav-item">
                          <a class="nav-link" href="{!! route('lines') !!}">{{ mb_strtoupper(__("cab.LINES")) }}</a>
                      </li>
                      <li class="nav-item">
                          <a class="nav-link" href="{!! route('cdr') !!}">{{mb_strtoupper( __("cab.CALLS")) }}</a>
                      </li>
                      <li class="nav-item">
                          <a class="nav-link" href="{!! route('pay') !!}">{{ mb_strtoupper(__("cab.INV_PAY")) }}</a>
                      </li>
                      <li class="nav-item">
                          <a class="nav-link" href="{!! route('help') !!}">{{ mb_strtoupper(__("cab.HELP")) }}</a>
                      </li>
                  </ul>
              </div>
          </div>
        </div>
    </nav>
  </div>
    <main class="container pb-3" data-page="@yield('pageType')">
      <h4 class="mb-3 p-2">@yield('pageTitle')</h4>
      @yield('content')
    </main>

    <footer class="container-fluid" style="border-top: 2px solid #d8d8d8">
      <aside class="container footer-bottom pt-md-3 pb-md-3">
          <address>Company © 2022</address>
      </aside>
    </footer>

    <!-- Modal -->
    <div class="modal fade" id="modal-win" tabindex="-1" aria-labelledby="modal-win" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-title">{{ __("cab.INV") }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <iframe src="" width="100%" height="500" frameborder="0"></iframe>
                </div>
            </div>
        </div>
    </div>

    @yield('templates')

    <script type="module" src="{{ mix('js/esm/app.esm.js') }}"></script>
    <wc-busy-ui style="display:none;"></wc-busy-ui>
</body>
</html>
