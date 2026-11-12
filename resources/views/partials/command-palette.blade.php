{{-- ⌘K command palette. Opened, filtered and listed by the `cmdk` object in resources/js/app.js. --}}
<div class="cmdk" id="cmdk" hidden>
    <div class="cmdk-backdrop" data-cmdk-close></div>
    <div class="cmdk-panel" id="cmdk-panel" role="dialog" aria-modal="true"
         aria-label="{{ __('site.cmd.palette') }}">
        <div class="cmdk-input" role="search">
            <span class="prompt" aria-hidden="true">›</span>
            <input id="cmdk-input" type="text" autocomplete="off" autocapitalize="off" spellcheck="false"
                   aria-label="{{ __('site.cmd.placeholder') }}"
                   placeholder="{{ __('site.cmd.placeholder') }}">
            <button type="button" class="cmdk-x" data-cmdk-close
                    aria-label="{{ __('site.cmd.close') }}">✕</button>
        </div>
        <div class="cmdk-list" id="cmdk-list" role="region" aria-label="{{ __('site.cmd.results') }}">
            <div id="cmdk-groups"></div>
            <div class="cmdk-empty" hidden>— {{ __('site.cmd.empty') }} —</div>
        </div>
        <div class="cmdk-foot">
            <span><b>↑↓</b> {{ __('site.cmd.hint_nav') }}</span>
            <span><b>↵</b> {{ __('site.cmd.hint_open') }}</span>
            <span><b>esc</b> {{ __('site.cmd.hint_close') }}</span>
        </div>
    </div>
</div>
