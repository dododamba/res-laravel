<?php

namespace App\Http\Middleware;

use App\Helpers\ThemeHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThemeLayoutMiddleware
{
    public function __construct(
        protected ThemeHelper $theme
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // 1. Initialisation des attributs structurels de Metronic (Dark Sidebar Layout)
        $this->theme->addHtmlAttribute('body', 'data-kt-app-layout', 'dark-sidebar');
        $this->theme->addHtmlAttribute('body', 'data-kt-app-header-fixed', 'true');
        $this->theme->addHtmlAttribute('body', 'data-kt-app-sidebar-enabled', 'true');
        $this->theme->addHtmlAttribute('body', 'data-kt-app-sidebar-fixed', 'true');
        $this->theme->addHtmlAttribute('body', 'data-kt-app-sidebar-hoverable', 'true');
        $this->theme->addHtmlAttribute('body', 'data-kt-app-sidebar-push-header', 'true');
        $this->theme->addHtmlAttribute('body', 'data-kt-app-sidebar-push-toolbar', 'true');
        $this->theme->addHtmlAttribute('body', 'data-kt-app-sidebar-push-footer', 'true');
        $this->theme->addHtmlAttribute('body', 'data-kt-app-toolbar-enabled', 'true');

        $this->theme->addHtmlClass('body', 'app-default');

        // 2. Inclusion automatique des briques d'assets globales
        $this->theme->addVendors(['datatables', 'fullcalendar']);
        $this->theme->addJavascriptFile('assets/js/widgets.bundle.js');
        $this->theme->addJavascriptFile('assets/js/custom/apps/chat/chat.js');

        return $next($request);
    }
}
