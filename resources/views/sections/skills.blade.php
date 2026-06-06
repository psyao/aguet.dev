{{-- Skills: a tree of stack groups; "Intégration & automatisation" is highlighted. --}}
@php
    $techCount = collect($skills)->sum(fn ($g) => isset($g['items']) ? count($g['items']) : 0);
@endphp
<section id="skills" class="block">
    <div class="wrap">
        <h2 class="visually-hidden">{{ __('site.sections.skills') }}</h2>
        <p class="cmd"><span class="prompt">steve@aguet ~ %</span> <span class="arg">tree ~/stack</span></p>
        <p class="tree-head"><b>~/stack</b></p>

        <div class="tree">
            @foreach ($skills as $group)
                <div @class(['row', 'focus' => $group['focus'] ?? false])>
                    <span class="branch">{{ $loop->last ? '└─' : '├─' }}
                        @if ($group['focus'] ?? false)<span class="star">★</span> @endif<span class="name">{{ __($group['title_key']) }}</span>@isset($group['items']) <span class="cnt">({{ count($group['items']) }})</span>@endisset
                    </span>
                    <span class="items">
                        @isset($group['items'])
                            @foreach ($group['items'] as $item)<span>{{ $item }}</span>@endforeach
                        @else
                            <span>{{ __($group['items_key']) }}</span>
                        @endisset
                    </span>
                </div>
            @endforeach
        </div>

        <p class="tree-foot">└─ <b>{{ count($skills) }}</b> {{ __('site.skills.groups_word') }} · <b>{{ $techCount }}</b> {{ __('site.skills.tech_word') }} · {{ __('site.skills.g3_note') }} ▸ <span class="accent">{{ __('site.skills.g3') }}</span></p>
    </div>
</section>
