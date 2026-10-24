{{-- The privacy policy: one terminal-styled section, its body rendered from resources/views/markdown/privacy/{locale}. --}}
@php
    $urls = ['fr' => route('privacy'), 'en' => route('privacy.en')];
    $home = $locale === 'en' ? url('/en') : url('/');
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $locale === 'en' ? 'Privacy policy' : 'Politique de confidentialité' }} — aguet.dev</title>
    <link rel="canonical" href="{{ $urls[$locale] }}">
    @foreach ($urls as $code => $url)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ $url }}">
    @endforeach
    @vite(['resources/css/app.css'])
</head>
<body data-density="comfortable" data-fx="subtle">
    <script>try{var t=localStorage.getItem('theme');if(t)document.body.dataset.theme=t;}catch(e){}</script>
    <main id="content">
        <section class="about section">
            <div class="wrap">
                <p class="cmd"><a class="prompt" href="{{ $home }}">steve@aguet ~ %</a> <span class="arg">cat privacy.md</span></p>
                <div class="body legal">{!! $body !!}</div>
            </div>
        </section>
    </main>
</body>
</html>
