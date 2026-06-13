{{-- Contact modal shell. State lives in the Alpine $store.contact
     (resources/js/app.js); the form itself is the <livewire:contact-form>. --}}
<div class="contact-modal" id="contact-modal" x-cloak
     x-show="$store.contact.isOpen"
     :class="{ show: $store.contact.isOpen }"
     @keydown.escape.window="$store.contact.close()">
    <div class="contact-modal-backdrop" @click="$store.contact.close()"></div>
    <div class="contact-modal-panel" id="contact-modal-panel"
         role="dialog" aria-modal="true" aria-labelledby="contact-modal-title"
         @keydown.tab="$store.contact.trapFocus($event)">
        <div class="cm-head">
            <p class="cmd"><span class="prompt" aria-hidden="true">steve@aguet ~ %</span> <span class="arg">./contact.sh</span></p>
            <h2 id="contact-modal-title" class="cm-title">{{ __('site.contact.form.title') }}</h2>
            <p class="cm-intro">{{ __('site.contact.form.intro') }}</p>
            <button type="button" class="cm-close" @click="$store.contact.close()"
                    aria-label="{{ __('site.contact.form.close') }}">✕</button>
        </div>
        <div class="cm-body">
            <livewire:contact-form />
        </div>
    </div>
</div>
