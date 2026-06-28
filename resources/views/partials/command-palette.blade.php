{{-- ⌘K command palette. State lives in the Alpine $store.cmdk (resources/js/app.js). --}}
<div class="cmdk" id="cmdk" x-cloak
     x-show="$store.cmdk.isOpen"
     :class="{ show: $store.cmdk.isOpen }"
     @keydown.escape.window="$store.cmdk.close()">
    <div class="cmdk-backdrop" @click="$store.cmdk.close()"></div>
    <div class="cmdk-panel" role="dialog" aria-modal="true"
         aria-label="{{ $locale === 'fr' ? 'Palette de commandes' : 'Command palette' }}">
        <div class="cmdk-input">
            <span class="prompt" aria-hidden="true">›</span>
            <input id="cmdk-input" type="text" autocomplete="off" autocapitalize="off" spellcheck="false"
                   placeholder="{{ __('site.cmd.placeholder') }}"
                   x-model="$store.cmdk.query"
                   @keydown.down.prevent="$store.cmdk.move(1)"
                   @keydown.up.prevent="$store.cmdk.move(-1)"
                   @keydown.enter.prevent="$store.cmdk.enter()">
            <button type="button" class="cmdk-x" @click="$store.cmdk.close()"
                    aria-label="{{ $locale === 'fr' ? 'Fermer' : 'Close' }}">✕</button>
        </div>
        <div class="cmdk-list" id="cmdk-list">
            <template x-for="grp in $store.cmdk.groups" :key="grp.g">
                <div>
                    <div class="cmdk-grp" x-text="grp.g"></div>
                    <template x-for="item in grp.items" :key="item._i">
                        <button class="cmdk-item" type="button"
                                :class="{ on: item._i === $store.cmdk.active }"
                                @click="$store.cmdk.run(item)"
                                @mousemove="$store.cmdk.active = item._i">
                            <span class="ci-prompt" aria-hidden="true">›</span>
                            <span class="ci-label" x-text="item.label"></span>
                            <span class="ci-hint" x-text="item.hint"></span>
                        </button>
                    </template>
                </div>
            </template>
            <div class="cmdk-empty" x-show="$store.cmdk.filtered.length === 0 && !$store.cmdk.commandMode">— {{ __('site.cmd.empty') }} —</div>
        </div>
        <div class="cmdk-foot">
            <span><b>↑↓</b> {{ __('site.cmd.hint_nav') }}</span>
            <span><b>↵</b> {{ __('site.cmd.hint_open') }}</span>
            <span><b>esc</b> {{ __('site.cmd.hint_close') }}</span>
        </div>
    </div>
</div>
