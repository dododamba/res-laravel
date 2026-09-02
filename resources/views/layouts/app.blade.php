<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" {!! $theme->printHtmlAttributes('html') !!}>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Recensement Territorial') - Administration</title>

    <!-- Metronic Bootstrap 5 Stylesheets -->
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    
    <!-- Injection des CSS additionnels chargés dynamiquement par le ThemeHelper -->
    @foreach($theme->getCssFiles() as $css)
        <link href="{{ asset($css) }}" rel="stylesheet" type="text/css" />
    @endforeach

    <!-- CSS Responsive Personnalisé -->
    <link href="{{ asset('css/backoffice-responsive.css') }}" rel="stylesheet" type="text/css" />

    @stack('styles')
</head>
<body id="kt_app_body" {!! $theme->printHtmlClasses('body') !!} {!! $theme->printHtmlAttributes('body') !!}>

    <!-- Overlay Mobile Drawer Sidebar -->
    <div id="kt_sidebar_overlay" class="drawer-overlay"></div>

    <!--begin::App-->
    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <!--begin::Page-->
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
            
            <!-- Inclusion de l'en-tête (Header) -->
            @include('layouts.partials.header')

            <!--begin::Wrapper-->
            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
                
                <!-- Inclusion de la barre latérale (Sidebar) -->
                @include('layouts.partials.sidebar')

                <!--begin::Main-->
                <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                    
                    <!--begin::Content wrapper-->
                    <div class="d-flex flex-column flex-column-fluid">
                        
                        <!--begin::Content-->
                        <div id="kt_app_content" class="app-content flex-column-fluid mt-5">
                            <div id="kt_app_content_container" class="app-container container-fluid">
                                
                                <!-- Alertes Flash Session Blade -->
                                @if(session('success'))
                                    <div class="alert alert-dismissible bg-light-success d-flex align-items-center p-5 mb-5 border border-success border-dashed rounded">
                                        <i class="fas fa-check-circle fs-2hx text-success me-4"></i>
                                        <div class="d-flex flex-column">
                                            <h4 class="fw-bold text-gray-900 mb-1 fs-6">Succès</h4>
                                            <span class="text-gray-700 fw-semibold">{!! session('success') !!}</span>
                                        </div>
                                        <button type="button" class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto" data-bs-dismiss="alert">
                                            <i class="fas fa-times text-success fs-3"></i>
                                        </button>
                                    </div>
                                @endif

                                @if(session('error'))
                                    <div class="alert alert-dismissible bg-light-danger d-flex align-items-center p-5 mb-5 border border-danger border-dashed rounded">
                                        <i class="fas fa-exclamation-triangle fs-2hx text-danger me-4"></i>
                                        <div class="d-flex flex-column">
                                            <h4 class="fw-bold text-gray-900 mb-1 fs-6">Erreur</h4>
                                            <span class="text-gray-700 fw-semibold">{!! session('error') !!}</span>
                                        </div>
                                        <button type="button" class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto" data-bs-dismiss="alert">
                                            <i class="fas fa-times text-danger fs-3"></i>
                                        </button>
                                    </div>
                                @endif

                                <!-- Injection du contenu principal de la page -->
                                @yield('content')

                            </div>
                        </div>
                        <!--end::Content-->

                    </div>
                    <!--end::Content wrapper-->

                    @include('layouts.partials.footer')

                </div>
                <!--end:::Main-->

            </div>
            <!--end::Wrapper-->

        </div>
        <!--end::Page-->
    </div>
    <!--end::App-->

    <!-- Scripts de base Metronic -->
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    
    <!-- Injection des JS additionnels chargés dynamiquement par le ThemeHelper -->
    @foreach($theme->getJavascriptFiles() as $js)
        <script src="{{ asset($js) }}"></script>
    @endforeach

    @stack('scripts')

    <!-- Script Drawer Sidebar Mobile -->
    <script>
    (function () {
        var toggleBtn  = document.getElementById('kt_app_sidebar_mobile_toggle');
        var sidebar    = document.getElementById('kt_app_sidebar');
        var overlay    = document.getElementById('kt_sidebar_overlay');
        var BREAKPOINT = 992; // lg

        function isMobile() {
            return window.innerWidth < BREAKPOINT;
        }

        function openDrawer() {
            if (!sidebar || !overlay) return;
            sidebar.classList.add('drawer-open');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeDrawer() {
            if (!sidebar || !overlay) return;
            sidebar.classList.remove('drawer-open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        function toggleDrawer() {
            if (!isMobile()) return;
            sidebar.classList.contains('drawer-open') ? closeDrawer() : openDrawer();
        }

        // Fermer si on passe en desktop
        window.addEventListener('resize', function () {
            if (!isMobile()) {
                closeDrawer();
            }
        });

        if (toggleBtn) {
            toggleBtn.addEventListener('click', toggleDrawer);
        }

        if (overlay) {
            overlay.addEventListener('click', closeDrawer);
        }

        // Fermer le drawer en cliquant sur un lien du menu
        if (sidebar) {
            sidebar.querySelectorAll('.menu-link').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (isMobile()) closeDrawer();
                });
            });
        }
    })();
    </script>
</body>
</html>
