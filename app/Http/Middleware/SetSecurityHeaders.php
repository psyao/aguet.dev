<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetSecurityHeaders
{
    /**
     * Content-Security-Policy for the public site. `unsafe-inline` on
     * script/style is required by Alpine (inline x-data expressions, inline
     * style bindings) and the small inline bootstrap scripts in the layout
     * head — none of them use nonces. Cloudflare Web Analytics needs its own
     * script and connect origins (the beacon script and its report endpoint
     * live on different subdomains).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $csp = implode('; ', [
            "default-src 'self'",
            // 'unsafe-eval' is required by Alpine: every x-data/x-on expression
            // is evaluated via `new Function(...)`, confirmed by browser console
            // CSP violations without it (verified 2026-07-06).
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://static.cloudflareinsights.com",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self'",
            "connect-src 'self' https://cloudflareinsights.com",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
