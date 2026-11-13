<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Psyao\Kami\Facades\Kami;
use Psyao\Kami\Support\SecurityHeaders;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Kami's front CSP and security headers on aguet's own routes too (Kami's pages get them in its `kami` group).
        $middleware->web(append: [SecurityHeaders::class]);

        // No trustProxies: Infomaniak passes the real client IP in REMOTE_ADDR
        // and never sets X-Forwarded-For. Trusting XFF would let any visitor
        // spoof their IP (verified 2026-07-02) and bypass the contact Form's
        // per-IP throttle. The empty body keeps Laravel's default middleware
        // groups (web/api) registered.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // First: error pages render in the URL's Locale, `/en/…` in English.
        Kami::exceptions($exceptions);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
