<?php

/*
| The one-page Site is served by Velox: its Home Page at "/" and its English
| Translation at "/en" (see App\ContentTypes\Home). Add the Site's own pages
| here; they win over Velox's catch-all route.
*/

use App\Http\Controllers\PrivacyController;
use Illuminate\Support\Facades\Route;

Route::get('/privacy', PrivacyController::class)->defaults('locale', 'fr')->name('privacy');
Route::get('/en/privacy', PrivacyController::class)->defaults('locale', 'en')->name('privacy.en');
