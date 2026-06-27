{{-- Contact: a contact.json block with click-to-copy on each coordinate. --}}
@php
    $email = $content->contact_email;
    // Base64 so the address never appears as plaintext "user@domain" in the HTML
    // source; Alpine rebuilds it at runtime (see the `email` component in app.js).
    $emailEnc = base64_encode((string) $email);
    $linkedin = $content->contact_linkedin;
    $linkedinLabel = $content->contact_linkedin_label;
    $github = $content->contact_github;
    $githubLabel = $content->contact_github_label;
@endphp
<section id="contact" class="contact section">
    <div class="wrap">
        <h2 class="visually-hidden">{{ __('site.sections.contact') }}</h2>
        <p class="cmd"><span class="prompt">steve@aguet ~ %</span> <span class="arg">cat contact.json</span></p>
        <p class="lead">{!! \App\Support\Content::md($content->contact_lead) !!}</p>

        <div class="json">
            <div><span class="s">{</span></div>

            {{-- Email is assembled at runtime from a base64 blob; the row stays
                 hidden without JS (x-cloak) so no plaintext address is exposed. --}}
            <div class="jrow" x-data="email('{{ $emailEnc }}')" x-cloak>&nbsp;&nbsp;<span class="k">"email"</span><span class="s">:</span> <a :href="mailto" x-text="display"></a><span class="s">,</span><button class="cp" type="button" :class="{ ok: copied }" @click="copyValue()" x-text="label" aria-label="{{ $locale === 'fr' ? 'Copier l’email' : 'Copy email' }}">{{ __('site.contact.copy') }}</button></div>

            <div class="jrow" x-data="copy('{{ $linkedin }}')">&nbsp;&nbsp;<span class="k">"linkedin"</span><span class="s">:</span> <a href="{{ $linkedin }}" target="_blank" rel="noopener">"{{ $linkedinLabel }}"</a><span class="s">,</span><button class="cp" type="button" :class="{ ok: copied }" @click="copyValue()" x-text="label" aria-label="{{ $locale === 'fr' ? 'Copier le lien LinkedIn' : 'Copy LinkedIn link' }}">{{ __('site.contact.copy') }}</button></div>

            <div class="jrow" x-data="copy('{{ $github }}')">&nbsp;&nbsp;<span class="k">"github"</span><span class="s">:</span> <a href="{{ $github }}" target="_blank" rel="noopener">"{{ $githubLabel }}"</a><span class="s">,</span><button class="cp" type="button" :class="{ ok: copied }" @click="copyValue()" x-text="label" aria-label="{{ $locale === 'fr' ? 'Copier le lien GitHub' : 'Copy GitHub link' }}">{{ __('site.contact.copy') }}</button></div>

            <div>&nbsp;&nbsp;<span class="k">"location"</span><span class="s">:</span> <span class="accent">"{{ $content->hero_location }}"</span></div>

            <div><span class="s">}</span></div>
        </div>

        {{-- Progressive enhancement: with JS, Alpine opens the contact modal.
             Without JS it falls back to LinkedIn (a real channel) rather than a
             mailto: — that keeps the email address out of the HTML source. --}}
        <a class="tui-btn primary cta" href="{{ $linkedin }}"
           @click.prevent="$store.contact.open()"
           x-bind:aria-haspopup="'dialog'"><span>{{ __('site.contact.cta') }}</span> <span class="arr">→</span></a>
    </div>
</section>
