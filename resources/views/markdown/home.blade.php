{{-- The Home Page as Markdown, served to agents sending Accept: text/markdown (see App\ContentTypes\Home::render). Raw output throughout: Markdown is not HTML-escaped. --}}
@php
    $plain = fn ($html) => trim(html_entity_decode(strip_tags(str_replace('</p>', "</p>\n\n", (string) $html)), ENT_QUOTES | ENT_HTML5));
@endphp
# Steve Aguet — {!! $content->hero_role !!}

{!! str_replace("\n", "  \n", trim((string) $content->hero_title)) !!}

{!! $content->hero_subtitle !!}

- Location: {!! $content->hero_location !!}
- Experience: {!! $content->hero_exp !!}
- Focus: {!! $content->hero_focus !!}

## {!! __('site.sections.about') !!}

{!! $plain($content->about_body) !!}

## {!! __('site.sections.skills') !!}

@foreach ($skills as $group)
- **{!! $group->title !!}**{!! $group->focus ? ' ★' : '' !!}: {!! $group->text ?: $group->tags->pluck('name')->join(', ') !!}
@endforeach

## {!! __('site.sections.projects') !!}

@foreach ($projects as $project)
### {!! $project->title !!}

- {!! __('site.projects.client') !!}: {!! $project->client !!}
- {!! __('site.projects.role') !!}: {!! $project->role !!}
- Stack: {!! $project->tags->pluck('name')->join(', ') !!}
@if ($project->url)
- URL: <{!! $project->url !!}>
@endif

{!! $project->summary !!}

@endforeach
## {!! __('site.sections.contact') !!}

{!! $content->contact_lead !!}

- Contact form: <{{ $homeUrls[$locale] ?? url('/') }}#contact>
- LinkedIn: <{!! $content->contact_linkedin !!}>
- GitHub: <{!! $content->contact_github !!}>

---

Other language: <{!! $altUrl !!}> · Agent guide: <{{ url('/llms.txt') }}> · Privacy: <{{ $locale === 'en' ? url('/en/privacy') : url('/privacy') }}>
