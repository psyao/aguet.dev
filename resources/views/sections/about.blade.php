{{-- About: prose (Markdown) + an ASCII fastfetch profile card. --}}
<section id="about" class="about section">
    <div class="wrap">
        <h2 class="visually-hidden">{{ __('site.sections.about') }}</h2>
        <p class="cmd"><span class="prompt">steve@aguet ~ %</span> <span class="arg">cat about.md &amp;&amp; fastfetch</span></p>

        <div class="about-grid">
            <div class="body">{!! \Illuminate\Support\Str::markdown($content->about_body ?? '', [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]) !!}</div>

            <div class="neofetch">
                <pre class="logo" aria-hidden="true">    ◆
   ◆◆◆
  ◆◆◆◆◆
 ◆◆◆◆◆◆◆
  ◆◆◆◆◆
   ◆◆◆
    ◆</pre>
                <div class="nf-info">
                    <div class="nf-title">steve<span>@</span>aguet</div>
                    <div class="nf-rule" aria-hidden="true">———————————————</div>
                    <dl>
                        <dt>OS</dt><dd>Laravel · PHP 8</dd>
                        <dt>Host</dt><dd>{{ $content->hero_location }}</dd>
                        <dt>Uptime</dt><dd>{{ $content->hero_exp }}</dd>
                        <dt>Shell</dt><dd>full-stack · back-end</dd>
                        <dt>Focus</dt><dd class="hot">{{ $skills->first(fn ($g) => $g->focus)?->title ?? $content->hero_focus }}</dd>
                        <dt>Lang</dt><dd>FR · EN · DE</dd>
                    </dl>
                    <div class="nf-sw" aria-hidden="true">
                        <i style="background:var(--color-accent)"></i>
                        <i style="background:#3a4a40"></i>
                        <i style="background:#566459"></i>
                        <i style="background:#869589"></i>
                        <i style="background:#a6b5ab"></i>
                        <i style="background:#cdd8d0"></i>
                        <i style="background:#e7efe9"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
