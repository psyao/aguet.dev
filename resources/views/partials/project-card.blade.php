{{-- $project: App\Models\Project · $variant: 'feat' | 'full' | 'normal' --}}
@php $variant ??= 'normal'; @endphp

@if ($variant === 'feat')
    <article class="pcard feat">
        <span class="legend"><span class="slash">~/projects/</span><b>{{ $project->slug }}</b></span>
        <span class="star">★ {{ __('site.projects.featured') }}</span>
        <div class="feat-grid">
            <div>
                <div class="pname">{{ $project->name }}</div>
                <p class="desc">{!! \App\Support\Content::md($project->summary) !!}</p>
                <div class="stack">
                    @foreach ($project->tags as $tag)<span>{{ $tag->name }}</span>@endforeach
                </div>
                @if ($project->url)
                    <a class="open" href="{{ $project->url }}" target="_blank" rel="noopener">
                        <span>{{ __('site.projects.visit') }}</span> <b>{{ $project->host() }}</b> <span class="arr">↗</span>
                    </a>
                @endif
            </div>
            <dl>
                <dt>{{ __('site.projects.client') }}</dt><dd>{{ $project->client }}</dd>
                <dt>{{ __('site.projects.role') }}</dt><dd>{{ $project->role }}</dd>
            </dl>
        </div>
    </article>
@else
    <article @class(['pcard', 'full' => $variant === 'full'])>
        <span class="legend"><span class="slash">~/projects/</span><b>{{ $project->slug }}</b></span>
        <span class="perms" aria-hidden="true">drwxr-xr-x</span>
        <dl>
            <dt>{{ __('site.projects.client') }}</dt><dd>{{ $project->client }}</dd>
            <dt>{{ __('site.projects.role') }}</dt><dd>{{ $project->role }}</dd>
        </dl>
        <div class="stack">
            @foreach ($project->tags as $tag)<span>{{ $tag->name }}</span>@endforeach
        </div>
        <p class="desc">{!! \App\Support\Content::md($project->summary) !!}</p>
        @if ($project->url)
            <a class="open" href="{{ $project->url }}" target="_blank" rel="noopener">
                <span>{{ __('site.projects.visit') }}</span> <b>{{ $project->host() }}</b> <span class="arr">↗</span>
            </a>
        @endif
    </article>
@endif
