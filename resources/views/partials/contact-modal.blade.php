{{-- Contact modal, opened by the `contact` object in resources/js/app.js. Its
     form is the Kami Form App\Forms\ContactForm, which resources/js/contact-form.js
     submits without a reload: the modal stays open over the errors, or over the
     confirmation in the status element. Without JavaScript it posts a full page,
     and the modal opens itself on load (data-contact-autoopen) over the
     confirmation, after `?sent=contact_form`, or over the errors of a refused
     message. The script builds the same error markup as this view. --}}
@php
    $sent = \Psyao\Kami\Facades\Kami::form('contact_form')->sent();
    $refused = isset($errors) && $errors->any();
@endphp
<div class="contact-modal" id="contact-modal" hidden @if ($sent || $refused) data-contact-autoopen @endif>
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
        <div class="cm-body">
            @unless ($sent)
                <x-kami::form form="contact_form" class="cf" aria-label="{{ __('site.contact.form.title') }}"
                              data-error="{{ __('site.contact.form.error') }}"
                              data-errors-heading="{{ __('kami::forms.errors.heading') }}">
                    <x-kami::form.errors level="3" class="cf-alert" data-contact-focus />

                    {{-- The counter counts UTF-16 units, as maxlength and app.js do.
                         Each field is a bordered box with its label cut into the top
                         border (Laravel Prompts style). The <label> stays a real,
                         associated label for screen readers. Each id is the field's
                         name, which the errors summary links to. --}}
                    <div class="cf-field @error('subject') is-invalid @enderror">
                        <label class="cf-legend" for="subject">{{ __('site.contact.form.subject_label') }}</label>
                        <input id="subject" name="subject" type="text" class="cf-input" value="{{ old('subject') }}"
                               required maxlength="{{ \App\Forms\ContactForm::MAX_SUBJECT }}" autocomplete="off"
                               placeholder="{{ __('site.contact.form.subject_placeholder') }}"
                               @error('subject') aria-invalid="true" aria-describedby="subject-error" @enderror>
                    </div>
                    @error('subject')
                        <p class="cf-error" id="subject-error">{{ $message }}</p>
                    @enderror

                    <div class="cf-field @error('email') is-invalid @enderror">
                        <label class="cf-legend" for="email">{{ __('site.contact.form.email_label') }}</label>
                        <input id="email" name="email" type="email" class="cf-input" value="{{ old('email') }}"
                               required maxlength="{{ \App\Forms\ContactForm::MAX_EMAIL }}" autocomplete="email" inputmode="email"
                               placeholder="{{ __('site.contact.form.email_placeholder') }}"
                               @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    </div>
                    @error('email')
                        <p class="cf-error" id="email-error">{{ $message }}</p>
                    @enderror

                    <div class="cf-field cf-field--area @error('message') is-invalid @enderror">
                        <label class="cf-legend" for="message">{{ __('site.contact.form.message_label') }}</label>
                        <textarea id="message" name="message" class="cf-input cf-textarea"
                                  rows="5" required maxlength="{{ \App\Forms\ContactForm::MAX_MESSAGE }}"
                                  placeholder="{{ __('site.contact.form.message_placeholder') }}"
                                  @error('message') aria-invalid="true" aria-describedby="message-error" @enderror>{{ old('message') }}</textarea>
                        <span class="cf-counter" aria-hidden="true"><span data-cf-count>{{ intdiv(strlen(mb_convert_encoding((string) old('message', ''), 'UTF-16LE')), 2) }}</span> / {{ \App\Forms\ContactForm::MAX_MESSAGE }}</span>
                    </div>
                    @error('message')
                        <p class="cf-error" id="message-error">{{ $message }}</p>
                    @enderror

                    <div class="cf-actions">
                        <button type="submit" class="tui-btn primary">
                            <span>{{ __('site.contact.form.send') }}</span>
                            <span class="arr" aria-hidden="true">↵</span>
                        </button>
                        {{-- A native reset clears the draft, and the modal closes. --}}
                        <button type="reset" class="tui-btn cf-cancel" data-contact-close>
                            <span>{{ __('site.contact.form.cancel') }}</span>
                        </button>
                    </div>
                </x-kami::form>
            @endunless

            {{-- A live region present, empty, from the page load: the script fills it. --}}
            <p class="cf-success-line" id="contact_form-status" role="status" tabindex="-1" @if ($sent) data-contact-focus @endif>@if ($sent){{ \Psyao\Kami\Facades\Kami::form('contact_form')->confirmation() }}@endif</p>
            <button type="button" class="tui-btn" data-contact-close data-cf-done @unless ($sent) hidden @endunless>
                <span>{{ __('site.contact.form.close') }}</span>
            </button>
        </div>
    </div>
</div>
