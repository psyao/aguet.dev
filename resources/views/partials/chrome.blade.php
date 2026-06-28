{{-- Window chrome: traffic-light dots, terminal title, ⌘K, FR/EN switch (server-side links). --}}
<div class="chrome">
    <div class="chrome-bar">
        <span class="dots" aria-hidden="true"><i></i><i></i><i></i></span>
        <span class="chrome-title"><b>steve@aguet</b> — ~/aguet.dev — zsh</span>
        <span class="chrome-right">
            <button class="kbtn" type="button" @click="$store.cmdk.open()"
                    aria-label="{{ __('site.chrome.commands') }}">
                <span class="klabel">{{ __('site.chrome.commands') }}</span> <kbd class="kmod" aria-hidden="true">⌘K</kbd>
            </button>
            <span class="lang" role="group" aria-label="{{ $locale === 'fr' ? 'Langue' : 'Language' }}">
                @foreach ($homeUrls as $l => $u)
                    <a href="{{ $u }}" hreflang="{{ $l }}" lang="{{ $l }}"
                       @class(['is-active' => $l === $locale])
                       @if ($l === $locale) aria-current="true" @endif>{{ $l }}</a>
                @endforeach
            </span>
        </span>
    </div>
</div>
