{{-- Hero: typed boot intro → headline reveal. Base state is visible (no-JS / print safe). --}}
<section class="hero block" aria-label="{{ __('site.sections.hero') }}">
    <div class="wrap">
        <p class="who">
            <span class="prompt">steve@aguet ~ %</span>
            <span class="type" data-type="whoami">whoami</span>
        </p>
        <p class="who who-out boot-hide">
            <b>steve_aguet</b> &nbsp;<span class="cmt"># {{ $content->hero_role }}</span>
        </p>

        <p class="cmd" style="margin-top:20px">
            <span class="prompt">steve@aguet ~ %</span>
            <span class="type arg" data-type="cat headline.txt">cat headline.txt</span>
        </p>
        <h1 class="boot-hide">{!! \App\Support\Content::heroTitle($content->hero_title) !!}</h1>
        <p class="sub boot-hide">{{ $content->hero_subtitle }}</p>

        <dl class="kv boot-hide">
            <dt>name</dt><dd>Steve Aguet</dd>
            <dt>location</dt><dd>{{ $content->hero_location }}</dd>
            <dt>exp</dt><dd>{{ $content->hero_exp }}</dd>
            <dt>focus</dt><dd>{{ $content->hero_focus }}</dd>
        </dl>

        <div class="tui-row boot-hide">
            <a href="#projects" class="tui-btn primary"><span>{{ __('site.hero.cta_projects') }}</span> <span class="arr">→</span></a>
            <a href="#contact" class="tui-btn"><span>{{ __('site.hero.cta_contact') }}</span> <span class="arr">→</span></a>
        </div>

        <p class="cline"><span class="prompt">steve@aguet ~ %</span> <span class="cur" aria-hidden="true"></span></p>
    </div>
</section>
