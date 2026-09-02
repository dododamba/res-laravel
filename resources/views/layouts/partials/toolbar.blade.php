<!--begin::Toolbar-->
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-4">
    <!--begin::Toolbar container-->
    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack flex-wrap gap-3">
        
        <!--begin::Page title-->
        <div class="page-title d-flex flex-column justify-content-center me-3">
            <!--begin::Title-->
            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                @yield('page_title', View::yieldContent('title', 'Administration'))
            </h1>
            <!--end::Title-->

            <!--begin::Subtitle-->
            @hasSection('page_subtitle')
                <span class="text-muted fs-7 fw-semibold mt-1">
                    @yield('page_subtitle')
                </span>
            @endif
            <!--end::Subtitle-->
        </div>
        <!--end::Page title-->

        <!--begin::Actions-->
        @hasSection('actions')
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                @yield('actions')
            </div>
        @endif
        <!--end::Actions-->

    </div>
    <!--end::Toolbar container-->
</div>
<!--end::Toolbar-->
