<?php

use App\Http\Controllers\CronController;
use App\Http\Middleware\SetSecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // Deliberately outside the `web` middleware group registered
            // above (session/cookie/CSRF-adjacent middleware) — this is a
            // machine-to-machine trigger (Infomaniak's pseudo-cron), not a
            // page. `then:` routes get no implicit group middleware.
            Route::get('/cron/{token}', CronController::class)
                ->where('token', '[a-f0-9]{48}');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: SetSecurityHeaders::class);

        // No trustProxies: Infomaniak passes the real client IP in REMOTE_ADDR
        // and never sets X-Forwarded-For. Trusting XFF would let any visitor
        // spoof their IP (verified 2026-07-02) and bypass the contact-form
        // per-IP rate limit. The empty body keeps Laravel's default middleware
        // groups (web/api) registered.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Agents asking for Markdown get a Markdown 404 pointing them at llms.txt;
        // everyone else falls through to the themed errors/404 page.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->wantsMarkdown()) {
                return response()->markdown(view('markdown.not-found')->render(), 404)->setVary('Accept', false);
            }
        });
    })->create();
