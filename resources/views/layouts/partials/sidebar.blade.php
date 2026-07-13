<!--begin::Sidebar-->
<div id="kt_app_sidebar" class="app-sidebar flex-column" data-kt-wrapper-enabled="true">
    
    <!--begin::Logo-->
    <div class="app-sidebar-logo px-6 d-flex align-items-center bg-white" id="kt_app_sidebar_logo">
        <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-white text-decoration-none">
            <img src="{{ asset('logo.png') }}" class="h-30px h-lg-35px app-sidebar-logo-default" alt="Logo" />
        </a>
    </div>
    <!--end::Logo-->

    <!--begin::sidebar menu-->
    <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
        <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper hover-scroll-overlay-y my-5" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-height="auto" data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer" data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px">
            
            <!--begin::Menu-->
            <div class="menu menu-column menu-rounded menu-sub-indention px-3" id="#kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="false">
                
                <!-- Tableau de bord -->
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <span class="menu-icon">
                            {!! $theme->getSvgIcon('duotune/general/gen025.svg', 'svg-icon-2') !!}
                        </span>
                        <span class="menu-title">Tableau de bord</span>
                    </a>
                </div>

                <!-- Section : Enquêtes Terrain -->
                <div class="menu-item pt-5">
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-8">Enquêtes de Saisies</span>
                    </div>
                </div>

                <!-- Recensement Ménages -->
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('recensement.*') ? 'active' : '' }}" href="{{ route('recensement.index') }}">
                        <span class="menu-icon">
                            {!! $theme->getSvgIcon('duotune/communication/com013.svg', 'svg-icon-2') !!}
                        </span>
                        <span class="menu-title">Recensement Ménages</span>
                    </a>
                </div>

                <!-- Habitations (Maisons) -->
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('maison.*') ? 'active' : '' }}" href="{{ route('maison.index') }}">
                        <span class="menu-icon">
                            {!! $theme->getSvgIcon('duotune/general/gen001.svg', 'svg-icon-2') !!}
                        </span>
                        <span class="menu-title">Fiches Habitations</span>
                    </a>
                </div>

                <!-- Opérateurs Économiques -->
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('operateur.*') ? 'active' : '' }}" href="{{ route('operateur.index') }}">
                        <span class="menu-icon">
                            {!! $theme->getSvgIcon('duotune/finance/fin006.svg', 'svg-icon-2') !!}
                        </span>
                        <span class="menu-title">Opérateurs Économiques</span>
                    </a>
                </div>

                @can('USER_MANAGE')
                <!-- Section : Administration & RH -->
                <div class="menu-item pt-5">
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-8">Administration</span>
                    </div>
                </div>

                <!-- Agents Territoriaux -->
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('agent.*') ? 'active' : '' }}" href="{{ route('agent.index') }}">
                        <span class="menu-icon">
                            {!! $theme->getSvgIcon('duotune/communication/com006.svg', 'svg-icon-2') !!}
                        </span>
                        <span class="menu-title">Agents Territoriaux</span>
                    </a>
                </div>

                <!-- Utilisateurs Système -->
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('user.*') ? 'active' : '' }}" href="{{ route('user.index') }}">
                        <span class="menu-icon">
                            {!! $theme->getSvgIcon('duotune/communication/com005.svg', 'svg-icon-2') !!}
                        </span>
                        <span class="menu-title">Utilisateurs & RBAC</span>
                    </a>
                </div>
                @endcan

                <!-- Section : Configurations Paramétriques -->
                @can('PARAM_VIEW')
                <div class="menu-item pt-5">
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-8">Paramétrages Métiers</span>
                    </div>
                </div>

                <!-- Menu Déroulant : Hiérarchie Géographique -->
                <div class="menu-item menu-accordion {{ request()->routeIs('quartier.*') || request()->routeIs('carre.*') || request()->is('admin/secteur*') || request()->is('admin/avenue*') ? 'here show' : '' }}" data-kt-menu-trigger="click">
                    <span class="menu-link">
                        <span class="menu-icon">
                            {!! $theme->getSvgIcon('duotune/maps/map012.svg', 'svg-icon-2') !!}
                        </span>
                        <span class="menu-title">Découpage Territorial</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <div class="menu-sub menu-sub-accordion">
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('quartier.*') ? 'active' : '' }}" href="{{ route('quartier.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Quartiers</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('carre.*') ? 'active' : '' }}" href="{{ route('carre.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Carrés (Blocs)</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->is('admin/secteur*') ? 'active' : '' }}" href="{{ url('admin/secteur') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Secteurs</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->is('admin/avenue*') ? 'active' : '' }}" href="{{ url('admin/avenue') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Avenues (Voies)</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Menu Déroulant : Référentiels Métiers -->
                <div class="menu-item menu-accordion {{ request()->is('admin/besoin-prioritaire*') || request()->is('admin/source-eau*') || request()->is('admin/source-energie*') || request()->is('admin/assainissement*') || request()->is('admin/fonction*') ? 'here show' : '' }}" data-kt-menu-trigger="click">
                    <span class="menu-link">
                        <span class="menu-icon">
                            {!! $theme->getSvgIcon('duotune/coding/cod001.svg', 'svg-icon-2') !!}
                        </span>
                        <span class="menu-title">Nomenclatures d'Enquêtes</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <div class="menu-sub menu-sub-accordion">
                        <div class="menu-item">
                            <a class="menu-link {{ request()->is('admin/besoin-prioritaire*') ? 'active' : '' }}" href="{{ url('admin/besoin-prioritaire') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Besoins Prioritaires</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->is('admin/source-eau*') ? 'active' : '' }}" href="{{ url('admin/source-eau') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Sources d'Eau</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->is('admin/source-energie*') ? 'active' : '' }}" href="{{ url('admin/source-energie') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Sources d'Énergie</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->is('admin/assainissement*') ? 'active' : '' }}" href="{{ url('admin/assainissement') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Modes d'Assainissement</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->is('admin/fonction*') ? 'active' : '' }}" href="{{ url('admin/fonction') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Fonctions administratives</span>
                            </a>
                        </div>
                    </div>
                </div>
                @endcan

            </div>
            <!--end::Menu-->

        </div>
    </div>
    <!--end::sidebar menu-->

</div>
<!--end::Sidebar-->
