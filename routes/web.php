<?php

use App\Http\Controllers\HomeController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

/*
| The one-page site. The default locale (FR) is served at "/"; every other
| configured locale gets its own prefix (e.g. "/en"). SetLocale reads the
| locale from the URL.
*/
Route::middleware(SetLocale::class)->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    foreach (array_diff(config('aguet.locales', []), [config('aguet.default_locale')]) as $locale) {
        Route::get('/'.$locale, [HomeController::class, 'index'])->name('home.'.$locale);
    }
});

// TEMPORARY: diagnose what REMOTE_ADDR vs X-Forwarded-For look like behind
// Infomaniak's proxy, to decide how to configure trustProxies. Remove after use.
Route::get('/whoami-debug', function (Illuminate\Http\Request $r) {
    return response()->json([
        'REMOTE_ADDR' => $r->server('REMOTE_ADDR'),
        'X-Forwarded-For' => $r->header('X-Forwarded-For'),
        'X-Real-IP' => $r->header('X-Real-IP'),
        'laravel_ip' => $r->ip(),
    ]);
});
