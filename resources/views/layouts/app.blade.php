@php
    // Visually-neutral screenshot mode for the Pest visual-regression harness:
    // only active under APP_ENV=testing, triggered by ?screenshot=1. Settles the
    // page (no boot intro, no animations, frozen clock/year, real font) so captures
    // are deterministic. Inert in production.
    $shot = app()->environment('testing') && request()->boolean('screenshot');
    $ogLocales = ['fr' => 'fr_CH', 'en' => 'en_GB'];
    $defaultLocale = config('aguet.default_locale', 'fr');
    $jsConfig = [
        'locale' => $locale,
        'altUrl' => $altUrl,
        'i18n' => [
            'cmd.placeholder' => __('site.cmd.placeholder'),
            'cmd.nav' => __('site.cmd.nav'),
            'cmd.actions' => __('site.cmd.actions'),
            'cmd.lang' => __('site.cmd.lang'),
            'cmd.email' => __('site.cmd.email'),
            'cmd.linkedin' => __('site.cmd.linkedin'),
            'cmd.github' => __('site.cmd.github'),
            'cmd.empty' => __('site.cmd.empty'),
            'nav.about' => __('site.nav.about'),
            'nav.skills' => __('site.nav.skills'),
            'nav.projects' => __('site.nav.projects'),
            'nav.contact' => __('site.nav.contact'),
            'contact.copy' => __('site.contact.copy'),
            'contact.copied' => __('site.contact.copied'),
            'cmd.wq' => __('site.cmd.wq'),
            'help.motions' => __('site.help.motions'),
            'help.jumps' => __('site.help.jumps'),
            'help.excmd' => __('site.help.excmd'),
            'help.konami' => __('site.help.konami'),
        ],
        'projects' => $projects->map(fn ($p) => [
            'label' => $p->name,
            'host' => $p->host(),
            'url' => $p->url,
        ])->all(),
        'contact' => [
            // Base64 so the address isn't plaintext in the page source; app.js
            // decodes it for the ⌘K palette's "Email" action.
            'emailEnc' => base64_encode((string) $content->contact_email),
            'linkedin' => $content->contact_linkedin,
            'linkedinLabel' => $content->contact_linkedin_label,
            'github' => $content->contact_github,
            'githubLabel' => $content->contact_github_label,
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Boot reveal: hide the hero only while typing plays; a failsafe clears it. --}}
    @unless($shot)
    <script>(function(){try{var m=window.matchMedia&&matchMedia("(prefers-reduced-motion: reduce)").matches;if(!m){document.documentElement.classList.add("boot");setTimeout(function(){document.documentElement.classList.remove("boot");},2800);}}catch(e){}})();</script>
    @else
    {{-- Screenshot mode: settle the page deterministically (no animations, real font, fonts ready). --}}
    <style>
        *, *::before, *::after {
            animation: none !important;
            transition: none !important;
            /* GPU backdrop blur (chrome bar, command-palette overlay) rasterizes
               non-deterministically run-to-run; neutralize it so baselines are stable. */
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }
        .cur { background: transparent !important; }
        /* assertScreenshotMatches() force-injects "* { font-family: Arial !important }"
           for cross-machine portability. Beat it with a higher-specificity !important
           rule (html * = 0,0,1 > * = 0,0,0) so the baseline shows the real JetBrains
           Mono terminal. Deterministic because the font is self-hosted + env is pinned. */
        html, html * { font-family: var(--mono) !important; }
    </style>
    <script>document.addEventListener('DOMContentLoaded',function(){if(document.fonts&&document.fonts.ready){document.fonts.ready.then(function(){document.documentElement.classList.add('fonts-ready');});}else{document.documentElement.classList.add('fonts-ready');}});</script>
    @endunless

    <title>{{ __('site.meta.title') }}</title>
    <meta name="description" content="{{ __('site.meta.description') }}">

    <link rel="canonical" href="{{ url()->current() }}">
    @foreach ($homeUrls as $l => $u)
        <link rel="alternate" hreflang="{{ $l }}" href="{{ $u }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $homeUrls[$defaultLocale] ?? url('/') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="aguet.dev">
    <meta property="og:title" content="{{ __('site.meta.title') }}">
    <meta property="og:description" content="{{ __('site.meta.description') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="{{ $ogLocales[$locale] ?? 'fr_CH' }}">
    @foreach (array_diff_key($ogLocales, [$locale => true]) as $og)
        <meta property="og:locale:alternate" content="{{ $og }}">
    @endforeach

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Cloudflare Web Analytics (cookieless, no consent banner). Beacon-only. --}}
    @if ($cfToken = config('services.cloudflare_analytics.token'))
        <script defer src="https://static.cloudflareinsights.com/beacon.min.js"
                data-cf-beacon='@json(['token' => $cfToken])'></script>
    @endif
</head>
<body data-density="comfortable" data-fx="subtle" x-data="terminal">

    <!--
       ~ you read the source. of course you do. here, some toys: ~

         j / k         move between sections (gg = top, G = bottom)
         :             open the command line     (then :q  :wq  :help)
         ↑↑↓↓←→←→ B A   ...you know this one.

       built with laravel + a bundled alpine. nice of you to drop by.
    -->

    <a class="skip" href="#content">{{ __('site.skip') }}</a>

    @include('partials.chrome')
    @include('partials.tabs')

    <main id="content">
        @yield('content')
    </main>

    @include('partials.statusbar')
    @include('partials.command-palette')
    @include('partials.contact-modal')

    <script>window.__AGUET = @json($jsConfig);</script>
    @livewireScripts
</body>
</html>
