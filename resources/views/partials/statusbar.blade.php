{{-- tmux-style status bar: mode, branch, live Europe/Zurich clock, locale, year. --}}
<footer class="statusbar">
    <span class="seg mode">NORMAL</span>
    <span class="seg hide">● <b>main</b></span>
    <span class="seg hide">{{ __('site.footer.note') }}</span>
    <span class="seg grow"></span>
    <span class="seg r hide">utf-8</span>
    <span class="seg r"><b>{{ strtoupper($locale) }}</b></span>
    <span class="seg r" x-data="clock" x-text="time">--:--</span>
    <span class="seg r">© {{ date('Y') }}</span>
</footer>
