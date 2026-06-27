{{-- tmux-style status bar: mode, branch, live Europe/Zurich clock, locale, year. --}}
<footer class="statusbar">
    <span class="seg mode">NORMAL</span>
    <span class="seg hide">● <b>main</b></span>
    <span class="seg hide">{{ __('site.footer.note') }}</span>
    <span class="seg grow"></span>
    <span class="seg r hide">utf-8</span>
    @php($other = collect(config('aguet.locales'))->first(fn ($l) => $l !== $locale))
    <a class="seg r" href="{{ $other === config('aguet.default_locale') ? route('home') : route('home.'.$other) }}" title="{{ __('site.footer.switch') }}" aria-label="{{ __('site.footer.switch') }}"><b>{{ strtoupper($locale) }}</b></a>
    <span class="seg r" @unless($shot ?? false) x-data="clock" x-text="time" @endunless>{{ ($shot ?? false) ? '00:00' : '--:--' }}</span>
    <span class="seg r">© {{ ($shot ?? false) ? '2026' : date('Y') }}</span>
</footer>
