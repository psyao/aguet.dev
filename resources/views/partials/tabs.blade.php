{{-- Section tabs (scrollspy-highlighted in JS). The ~/ prefix is decorative (CSS). --}}
<nav class="tabs" aria-label="{{ $locale === 'fr' ? 'Sections du site' : 'Site sections' }}">
    <div class="tabs-inner">
        <a href="#about">{{ __('site.nav.about') }}</a>
        <a href="#skills">{{ __('site.nav.skills') }}</a>
        <a href="#projects">{{ __('site.nav.projects') }}</a>
        <a href="#contact">{{ __('site.nav.contact') }}</a>
    </div>
</nav>
