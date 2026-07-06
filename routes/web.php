<?php

use App\Http\Controllers\HomeController;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetSecurityHeaders;
use Illuminate\Support\Facades\Route;

/*
| The one-page site. The default locale (FR) is served at "/"; every other
| configured locale gets its own prefix (e.g. "/en"). SetLocale reads the
| locale from the URL. SetSecurityHeaders is scoped here rather than global
| so it never applies to the Filament admin panel, which has its own,
| unaudited set of asset origins.
*/
Route::middleware([SetLocale::class, SetSecurityHeaders::class])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    foreach (array_diff(config('aguet.locales', []), [config('aguet.default_locale')]) as $locale) {
        Route::get('/'.$locale, [HomeController::class, 'index'])->name('home.'.$locale);
    }
});
