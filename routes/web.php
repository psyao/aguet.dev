<?php

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Psyao\Kami\Content\SiteSettings;
use Psyao\Kami\Facades\Kami;

/*
| The one-page Site is served by Kami: its Home Page at "/" and its English
| Translation at "/en" (see App\ContentTypes\Home). The routes below keep
| Velox-era addresses alive; Kami's fallback route comes last, so they win.
*/

// "/EN", "/En", "/eN": one 301 to the English home, as Paths are matched byte for byte.
Route::permanentRedirect('/{locale}', '/en')->where('locale', '(?!en)[Ee][Nn]');

// Velox's social image, still in shared links' cached previews: the current `kami-social`
// Conversion of the Site settings' Social image, read at each request as its URL changes.
Route::get('/storage/media/{ulid}/aguet-dev-1200.webp', function (): RedirectResponse {
    $image = SiteSettings::entry()?->default_social_image;

    abort_if($image === null, 404);

    return redirect($image->url('kami-social'), 301);
})->where('ulid', '[0-9A-Za-z]{26}');

Kami::routes();
