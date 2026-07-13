<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditLogMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') || !$request->route()) {
            return $response;
        }

        $routeName = $request->route()->getName();

        if (preg_match('/^(recensement|maison|operateur|user)\.(store|update|destroy|transition)/', $routeName, $matches)) {
            $entity = $matches[1];
            $action = $matches[2];

            $userAgent = $request->userAgent() ?? '';
            $osAndBrowser = $this->parseUserAgent($userAgent);

            AuditLog::create([
                'user_identifier' => auth()->check() ? auth()->user()->email : 'anonymous',
                'action' => strtoupper($entity . '_' . $action),
                'object_class' => 'App\\Models\\' . ucfirst($entity),
                'object_id' => $request->route($entity) ?? null,
                'ip_address' => $request->ip(),
                'user_agent' => $userAgent,
                'os' => $osAndBrowser['os'],
                'browser' => $osAndBrowser['browser'],
                'result' => $response->isSuccessful() ? 'success' : 'failure',
                'data_before' => $request->isMethod('PUT') || $request->isMethod('PATCH') ? json_encode($request->route()->parameter($entity)?->getOriginal()) : null,
                'data_after' => $response->isSuccessful() ? json_encode($request->all()) : null
            ]);
        }

        return $response;
    }

    private function parseUserAgent(string $userAgent): array
    {
        $os = 'Unknown OS';
        $browser = 'Unknown Browser';

        $osArray = [
            '/windows nt 10/i'      => 'Windows 10/11',
            '/macintosh|mac os x/i' => 'Mac OS X',
            '/linux/i'              => 'Linux',
            '/android/i'            => 'Android',
            '/iphone/i'             => 'iPhone OS',
        ];

        foreach ($osArray as $regex => $value) {
            if (preg_match($regex, $userAgent)) {
                $os = $value;
                break;
            }
        }

        $browserArray = [
            '/firefox/i'   => 'Firefox',
            '/safari/i'    => 'Safari',
            '/chrome/i'    => 'Chrome',
            '/edge/i'      => 'Edge',
        ];

        foreach ($browserArray as $regex => $value) {
            if (preg_match($regex, $userAgent)) {
                $browser = $value;
                break;
            }
        }

        return ['os' => $os, 'browser' => $browser];
    }
}
