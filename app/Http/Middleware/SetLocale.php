<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Resolve the locale from the URL: the default locale lives at "/",
     * the others under their own prefix (e.g. "/en"). Shares the locale,
     * the alternate locale, and the per-locale home URLs with all views
     * (used by the language switch and the hreflang tags).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locales = config('aguet.locales', ['fr']);
        $default = config('aguet.default_locale', 'fr');

        $segment = $request->segment(1);
        $locale = in_array($segment, $locales, true) ? $segment : $default;

        app()->setLocale($locale);

        $homeUrls = [];
        foreach ($locales as $loc) {
            $homeUrls[$loc] = $loc === $default ? url('/') : url('/'.$loc);
        }

        $alt = collect($locales)->first(fn ($loc) => $loc !== $locale) ?? $default;

        view()->share('locale', $locale);
        view()->share('altLocale', $alt);
        view()->share('altUrl', $homeUrls[$alt] ?? url('/'));
        view()->share('homeUrls', $homeUrls);

        return $next($request);
    }
}
