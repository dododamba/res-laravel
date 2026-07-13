<!--begin::Header-->
<div id="kt_app_header" class="app-header" data-kt-sticky="true" data-kt-sticky-activate="{default: true, lg: true}" data-kt-sticky-name="app-header-minimize" data-kt-sticky-offset="{default: '200px', lg: '200px'}">
    <!--begin::Header container-->
    <div class="app-container container-fluid d-flex align-items-stretch justify-content-between" id="kt_app_header_container">
        
        <!--begin::Sidebar toggle-->
        <div class="d-flex align-items-center d-lg-none ms-n3 me-1" title="Afficher la barre latérale">
            <div class="btn btn-icon btn-active-color-primary w-35px h-35px" id="kt_app_sidebar_mobile_toggle">
                {!! $theme->getSvgIcon('duotune/abstract/abs015.svg', 'svg-icon-2x') !!}
            </div>
        </div>
        <!--end::Sidebar toggle-->

        <!--begin::Breadcrumbs / Titre de page-->
        <div class="d-flex align-items-center flex-row-fluid" id="kt_app_header_wrapper">
            <div class="app-header-menu app-header-mobile-drawer align-items-stretch">
                <!-- Titre dynamique -->
                <h1 class="text-gray-900 fw-bold d-flex flex-column justify-content-center my-0 fs-5">
                    @yield('title', 'Administration')
                    <span class="text-muted fs-8 fw-semibold mt-1">Recensement Territorial Communal</span>
                </h1>
            </div>
        </div>
        <!--end::Breadcrumbs-->

        <!--begin::Navbar - Barre d'outils utilisateur-->
        <div class="app-navbar flex-shrink-0">
            
            <!--begin::User Menu-->
            <div class="app-navbar-item ms-1 ms-md-4" id="kt_header_user_menu_toggle">
                <!--begin::Menu wrapper-->
                <div class="cursor-pointer symbol symbol-35px" data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
                    <img src="{{ auth()->user()?->avatar ? asset(auth()->user()->avatar) : asset('assets/media/avatars/blank.png') }}" class="rounded-3" alt="user" />
                </div>
                
                <!--begin::User account menu-->
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px" data-kt-menu="true">
                    
                    <!--begin::Menu item (User Info)-->
                    <div class="menu-item px-3">
                        <div class="menu-content d-flex align-items-center px-3">
                            <!--Avatar-->
                            <div class="symbol symbol-50px me-5">
                                <img alt="Logo" src="{{ auth()->user()?->avatar ? asset(auth()->user()->avatar) : asset('assets/media/avatars/blank.png') }}" />
                            </div>
                            <!--Identité-->
                            <div class="d-flex flex-column">
                                <div class="fw-bold d-flex align-items-center fs-5">
                                    {{ auth()->user()?->firstname }} {{ auth()->user()?->lastname }}
                                </div>
                                <a href="#" class="fw-semibold text-muted text-hover-primary fs-7">{{ auth()->user()?->email }}</a>
                            </div>
                        </div>
                    </div>
                    <!--end::Menu item-->

                    <!--begin::Menu separator-->
                    <div class="separator my-2"></div>

                    <!--Profil-->
                    <div class="menu-item px-5">
                        <a href="{{ route('profile.show') }}" class="menu-link px-5">Mon Profil Agent</a>
                    </div>

                    <!--Activités-->
                    <div class="menu-item px-5">
                        @can('AUDIT_VIEW')
                            <a href="{{ route('audit.index') }}" class="menu-link px-5">Journal d'activités</a>
                        @else
                            <a href="#" class="menu-link px-5 text-muted opacity-50" title="Accès réservé aux administrateurs" onclick="return false;">Journal d'activités</a>
                        @endcan
                    </div>

                    <!--Paramètres-->
                    <div class="menu-item px-5">
                        <a href="{{ route('profile.edit') }}" class="menu-link px-5">Paramètres du compte</a>
                    </div>

                    <!--begin::Menu separator-->
                    <div class="separator my-2"></div>

                    <!--Déconnexion-->
                    <div class="menu-item px-5">
                        <form method="POST" action="{{ route('logout') }}" id="logout-form" class="d-none">
                            @csrf
                        </form>
                        <a href="#" class="menu-link px-5 text-danger" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            Déconnexion
                        </a>
                    </div>

                </div>
                <!--end::User account menu-->
                <!--end::Menu wrapper-->
            </div>
            <!--end::User Menu-->

        </div>
        <!--end::Navbar-->

    </div>
    <!--end::Header container-->
</div>
<!--end::Header-->
