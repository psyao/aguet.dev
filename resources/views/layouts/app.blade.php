@php
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
        ],
        'projects' => $projects->map(fn ($p) => [
            'label' => $p->name,
            'host' => $p->host(),
            'url' => $p->url,
        ])->all(),
        'contact' => [
            'email' => config('aguet.contact.email'),
            'linkedin' => config('aguet.contact.linkedin'),
            'linkedinLabel' => config('aguet.contact.linkedin_label'),
            'github' => config('aguet.contact.github'),
            'githubLabel' => config('aguet.contact.github_label'),
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Boot reveal: hide the hero only while typing plays; a failsafe clears it. --}}
    <script>(function(){try{var m=window.matchMedia&&matchMedia("(prefers-reduced-motion: reduce)").matches;if(!m){document.documentElement.classList.add("boot");setTimeout(function(){document.documentElement.classList.remove("boot");},2800);}}catch(e){}})();</script>

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
</head>
<body data-density="comfortable" data-fx="subtle" x-data="terminal">

    <a class="skip" href="#content">{{ __('site.skip') }}</a>

    @include('partials.chrome')
    @include('partials.tabs')

    <main id="content">
        @yield('content')
    </main>

    @include('partials.statusbar')
    @include('partials.command-palette')

    <script>window.__AGUET = @json($jsConfig);</script>
    @livewireScripts
</body>
</html>
