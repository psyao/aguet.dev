{{-- tmux-style status bar: vim mode pill (palette trigger), branch + commit
     popover, live Europe/Zurich clock, locale, year. --}}
@php
    $bi = config('build_info', []);
    $sha = (string) ($bi['sha'] ?? '');
    $validSha = preg_match('/\A[0-9a-f]{40}\z/i', $sha) === 1 ? strtolower($sha) : null;
    $decoded = ! empty($bi['message_b64']) ? base64_decode($bi['message_b64'], true) : null;
    $message = ($decoded !== false && $decoded !== '') ? $decoded : null;
    $repo = rtrim((string) config('aguet.repo_url', ''), '/');
    $commitUrl = ($validSha && $repo !== '') ? $repo.'/commit/'.$validSha : null;
    $shortSha = $validSha ? substr($validSha, 0, 7) : null;

    $relDate = null;
    if (! empty($bi['date'])) {
        try {
            $relDate = \Illuminate\Support\Carbon::parse($bi['date'])
                ->locale(app()->getLocale())
                ->diffForHumans();
        } catch (\Throwable $e) {
            $relDate = null; // malformed date → just omit it
        }
    }
@endphp
<footer class="statusbar" @unless($shot ?? false) x-data="statusbar" @endunless>
    <button type="button" class="seg mode"
            @unless($shot ?? false) @click="$store.cmdk.toggle()" @endunless><span @unless($shot ?? false) x-text="mode" @endunless>NORMAL</span><span class="visually-hidden"> — {{ __('site.footer.palette') }}</span></button>

    @unless($shot ?? false)
    <span class="seg cmd-echo" x-show="$store.vim.msg" x-cloak x-text="$store.vim.msg"></span>
    @endunless

    <span class="seg hide branch"
          @unless($shot ?? false) @click.outside="popover = false" @keydown.escape="popover = false" @endunless>
        <button type="button" class="branch-trigger"
                @unless($shot ?? false) @click="popover = ! popover" :aria-expanded="popover" @endunless
                aria-label="main — {{ __('site.footer.commit') }}"><span aria-hidden="true">●</span> <b>main</b></button>
        @unless($shot ?? false)
        <div class="sb-pop" x-show="popover" x-cloak x-transition.opacity
             role="dialog" aria-label="{{ __('site.footer.commit') }}">
            @if ($commitUrl)
                <a class="sb-pop-row sb-pop-sha" href="{{ $commitUrl }}"
                   target="_blank" rel="noopener noreferrer">● main {{ $shortSha }}</a>
            @endif
            @if ($message)
                <p class="sb-pop-row sb-pop-msg">{{ $message }}</p>
            @endif
            @if ($relDate)
                <p class="sb-pop-row sb-pop-date">{{ $relDate }}</p>
            @endif
            @if ($repo !== '')
                <a class="sb-pop-row sb-pop-repo" href="{{ $repo }}"
                   target="_blank" rel="noopener noreferrer">{{ __('site.footer.repo') }}</a>
            @endif
        </div>
        @endunless
    </span>

    <span class="seg hide">{{ __('site.footer.note') }}</span>
    <span class="seg grow"></span>
    <span class="seg r hide">utf-8</span>
    @php($other = collect(config('aguet.locales'))->first(fn ($l) => $l !== $locale))
    <a class="seg r" href="{{ $other === config('aguet.default_locale') ? route('home') : route('home.'.$other) }}" title="{{ __('site.footer.switch') }}" aria-label="{{ strtoupper($locale) }} — {{ __('site.footer.switch') }}"><b>{{ strtoupper($locale) }}</b></a>
    <span class="seg r hide" @unless($shot ?? false) x-data="clock" x-text="time" @endunless>{{ ($shot ?? false) ? '00:00' : '--:--' }}</span>
    <span class="seg r">© {{ ($shot ?? false) ? '2026' : date('Y') }}</span>
</footer>
