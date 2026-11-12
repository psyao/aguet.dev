{{-- Contact modal shell, opened by the `contact` object in resources/js/app.js.
     Its form comes back as a Kami Form (psyao/kami#560). --}}
<div class="contact-modal" id="contact-modal" hidden>
    <div class="contact-modal-backdrop" data-contact-close></div>
    <div class="contact-modal-panel" id="contact-modal-panel"
         role="dialog" aria-modal="true" aria-labelledby="contact-modal-title">
        <div class="cm-head">
            <p class="cmd"><span class="prompt" aria-hidden="true">steve@aguet ~ %</span> <span class="arg">./contact.sh</span></p>
            <h2 id="contact-modal-title" class="cm-title">{{ __('site.contact.form.title') }}</h2>
            <p class="cm-intro">{{ __('site.contact.form.intro') }}</p>
            <button type="button" class="cm-close" data-contact-close
                    aria-label="{{ __('site.contact.form.close') }}">✕</button>
        </div>
        <div class="cm-body"></div>
    </div>
</div>
