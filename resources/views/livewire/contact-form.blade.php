{{-- Terminal-prompt contact form. State lives in App\Livewire\ContactForm. --}}
<div class="cf">
    @if ($sent)
        {{-- Success: announced to SR and focused when Livewire morphs it in. --}}
        <div class="cf-success" role="status" tabindex="-1" data-cf-success
             x-init="$nextTick(() => $el.focus())">
            {{-- Terminal order: the work (rail delivery) prints first, the human
                 result line prints last. Polls the row's rail flags every second
                 while any rail is pending; the wire:poll attribute is dropped once
                 $deliveryDone flips, which stops the polling. A rail left pending
                 at timeout shows "queued" — the contact:notify sweep delivers it. --}}
            @if ($rails !== [])
                <ul class="cf-progress" @unless ($deliveryDone) wire:poll.1s="refreshDelivery" @endunless>
                    @foreach ($rails as $rail => $status)
                        @php($state = $deliveryDone && $status === 'pending' ? 'queued' : $status)
                        <li class="cf-progress-line" data-status="{{ $state }}">
                            <span class="prompt" aria-hidden="true">$</span>
                            <span class="cf-progress-rail">{{ __('site.contact.form.progress.'.$rail) }}</span>
                            <span class="cf-progress-state">{{ __('site.contact.form.progress.state.'.$state) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            {{-- Final result. Shown once delivery settles, or immediately when
                 there are no rails to run (honeypot / unconfigured). --}}
            @if ($rails === [] || $deliveryDone)
                <p class="cf-success-line"><span class="prompt" aria-hidden="true">$</span> {{ __('site.contact.form.success') }}</p>
            @endif

            <button type="button" class="tui-btn" wire:click="resetForm">
                <span>{{ __('site.contact.form.another') }}</span>
            </button>
        </div>
    @else
        <form wire:submit="submit" novalidate aria-label="{{ __('site.contact.form.title') }}"
              x-data="{ len: $wire.message.length, max: {{ \App\Livewire\ContactForm::MAX_MESSAGE }} }">

            {{-- General error (e.g. the DB write failed). --}}
            @if ($generalError)
                <p class="cf-alert" role="alert">{{ $generalError }}</p>
            @endif

            {{-- Rate-limited. --}}
            @if ($throttled)
                <p class="cf-alert" role="alert">{{ __('site.contact.form.throttled') }}</p>
            @endif

            {{-- Each field is a bordered box with its label cut into the top
                 border (Laravel Prompts style). The <label> stays a real,
                 associated label for screen readers. --}}

            {{-- Subject --}}
            <div class="cf-field @error('subject') is-invalid @enderror">
                <label class="cf-legend" for="cf-subject">{{ __('site.contact.form.subject_label') }}</label>
                <input id="cf-subject" type="text" class="cf-input" wire:model="subject"
                       maxlength="150" autocomplete="off"
                       placeholder="{{ __('site.contact.form.subject_placeholder') }}"
                       @error('subject') aria-invalid="true" aria-describedby="cf-subject-error" @enderror>
            </div>
            @error('subject')
                <p class="cf-error" id="cf-subject-error">{{ $message }}</p>
            @enderror

            {{-- Email --}}
            <div class="cf-field @error('email') is-invalid @enderror">
                <label class="cf-legend" for="cf-email">{{ __('site.contact.form.email_label') }}</label>
                <input id="cf-email" type="email" class="cf-input" wire:model="email"
                       maxlength="255" autocomplete="email" inputmode="email"
                       placeholder="{{ __('site.contact.form.email_placeholder') }}"
                       @error('email') aria-invalid="true" aria-describedby="cf-email-error" @enderror>
            </div>
            @error('email')
                <p class="cf-error" id="cf-email-error">{{ $message }}</p>
            @enderror

            {{-- Message --}}
            <div class="cf-field cf-field--area @error('message') is-invalid @enderror">
                <label class="cf-legend" for="cf-message">{{ __('site.contact.form.message_label') }}</label>
                <textarea id="cf-message" class="cf-input cf-textarea" wire:model="message"
                          rows="5" maxlength="{{ \App\Livewire\ContactForm::MAX_MESSAGE }}"
                          placeholder="{{ __('site.contact.form.message_placeholder') }}"
                          x-on:input="len = $event.target.value.length"
                          @error('message') aria-invalid="true" aria-describedby="cf-message-error" @enderror></textarea>
                <span class="cf-counter" aria-hidden="true"><span x-text="len">0</span> / <span x-text="max"></span></span>
            </div>
            @error('message')
                <p class="cf-error" id="cf-message-error">{{ $message }}</p>
            @enderror

            {{-- Honeypot: off-screen, never focusable, never announced. --}}
            <div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden">
                <label for="cf-website">Website</label>
                <input id="cf-website" type="text" tabindex="-1" autocomplete="off" wire:model="website">
            </div>

            <div class="cf-actions">
                <button type="submit" class="tui-btn primary" wire:loading.attr="aria-busy" data-cf-submit>
                    <span wire:loading.remove wire:target="submit">{{ __('site.contact.form.send') }}</span>
                    <span wire:loading wire:target="submit">{{ __('site.contact.form.sending') }}</span>
                    <span class="arr" aria-hidden="true">↵</span>
                </button>
                <button type="button" class="tui-btn cf-cancel"
                        wire:click="resetForm" @click="$store.contact.close()">
                    <span>{{ __('site.contact.form.cancel') }}</span>
                </button>
            </div>
        </form>
    @endif
</div>
