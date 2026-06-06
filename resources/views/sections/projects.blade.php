{{-- Projects: featured cards span full-width; a lone trailing non-featured card
     also spans full (reproduces the 4-project design, degrades sensibly otherwise). --}}
@php
    $featured = $projects->where('featured', true)->values();
    $rest = $projects->where('featured', false)->values();
    $restCount = $rest->count();
@endphp
<section id="projects" class="block">
    <div class="wrap">
        <h2 class="visually-hidden">{{ __('site.sections.projects') }}</h2>
        <p class="cmd"><span class="prompt">steve@aguet ~ %</span> <span class="arg">ls -l ~/projects</span></p>

        <div class="proj-grid">
            @foreach ($featured as $project)
                @include('partials.project-card', ['project' => $project, 'variant' => 'feat'])
            @endforeach

            @foreach ($rest as $project)
                @include('partials.project-card', [
                    'project' => $project,
                    'variant' => ($loop->last && $restCount % 2 === 1) ? 'full' : 'normal',
                ])
            @endforeach
        </div>
    </div>
</section>
