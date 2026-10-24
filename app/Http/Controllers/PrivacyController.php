<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PrivacyController extends Controller
{
    /**
     * The privacy policy in the route's Locale, written once as Markdown: served
     * as is to agents asking for Markdown, rendered to HTML for everyone else.
     */
    public function __invoke(Request $request, string $locale): Response
    {
        app()->setLocale($locale);

        $markdown = view("markdown.privacy.{$locale}")->render();

        $response = $request->wantsMarkdown()
            ? response()->markdown($markdown)
            : response()->view('privacy', ['body' => Str::markdown($markdown), 'locale' => $locale]);

        return $response->setVary('Accept', false);
    }
}
