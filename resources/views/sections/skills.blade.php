{{-- Skills: a tree of stack groups; the ★ focus group is highlighted. --}}
@php
    $techCount = $skills->sum(fn ($g) => count($g->items ?? []));
    $focusGroup = $skills->first(fn ($g) => $g->focus && $g->note);
@endphp
<section id="skills" class="block">
    <div class="wrap">
        <h2 class="visually-hidden">{{ __('site.sections.skills') }}</h2>
        <p class="cmd"><span class="prompt">steve@aguet ~ %</span> <span class="arg">tree ~/stack</span></p>
        <p class="tree-head"><b>~/stack</b></p>

        <div class="tree">
            @foreach ($skills as $group)
                <div @class(['row', 'focus' => $group->focus])>
                    <span class="branch">{{ $loop->last ? '└─' : '├─' }}
                        @if ($group->focus)<span class="star">★</span> @endif<span class="name">{{ $group->title }}</span>@if ($group->items) <span class="cnt">({{ count($group->items) }})</span>@endif
                    </span>
                    <span class="items">
                        @if ($group->text)
                            <span>{{ $group->text }}</span>
                        @else
                            @foreach ($group->items ?? [] as $item)<span>{{ $item }}</span>@endforeach
                        @endif
                    </span>
                </div>
            @endforeach
        </div>

        <p class="tree-foot">└─ <b>{{ $skills->count() }}</b> {{ __('site.skills.groups_word') }} · <b>{{ $techCount }}</b> {{ __('site.skills.tech_word') }}@if ($focusGroup) · {{ $focusGroup->note }} ▸ <span class="accent">{{ $focusGroup->title }}</span>@endif</p>
    </div>
</section>
