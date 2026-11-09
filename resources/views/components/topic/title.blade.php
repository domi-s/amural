@props(['page'])

{{-- The title block: kicker, big title with one circled word, and the definition with a drop cap. --}}
@php
    $lang = $page->langAttribute();
    $name = $page->name();
    $words = explode(' ', $name);
    $circled = \App\Support\TopicPageData::circledWordIndex($name);
    $definition = $page->definition();
    $dropCap = $definition === null ? null : \App\Support\TopicPageData::dropCap($definition, $page->shownLocale?->script);
    $alt = collect($page->altNames)->map(fn ($alt) => '<em>'.e($alt).'</em>')->implode(', ');
@endphp

<header>
    <p class="kicker text-muted">{{ $page->kicker }}</p>

    {{-- The display font only for text it can draw correctly; the body font otherwise (see DisplayFont). --}}
    <h1 @if ($lang) lang="{{ $lang }}" @endif class="{{ \App\Support\DisplayFont::classes($name) }} mt-4 text-[56px] leading-[0.95] tracking-[-0.015em] [overflow-wrap:anywhere] mag:text-[104px]">
        @foreach ($words as $index => $word)
            @if ($index === $circled)
                <span class="relative inline-block"><x-mark.circle />{{ $word }}</span>
            @else
                {{ $word }}
            @endif
        @endforeach
    </h1>

    @if ($page->concept->status === \App\Enums\ConceptStatus::Deprecated)
        <p class="mt-4 text-muted">{{ __('This topic is deprecated.') }}</p>
    @endif

    <div class="mt-12 grid gap-x-14 mag:grid-cols-[1.3fr_1fr]">
        <div class="max-w-[34rem] text-[19px] leading-[1.5] mag:text-[22px]" @if ($lang) lang="{{ $lang }}" @endif>
            @if ($definition !== null)
                <p>
                    @if ($dropCap)
                        <span class="float-left mr-3 mt-1 font-display text-[92px] italic leading-[0.78]" aria-hidden="true">{{ $dropCap[0] }}</span>
                        <span class="sr-only">{{ $dropCap[0] }}</span>{{ $dropCap[1] }}
                    @else
                        {{ $definition }}
                    @endif
                    @if ($alt !== '')
                        {!! __('Also known as :names.', ['names' => $alt]) !!}
                    @endif
                </p>
            @else
                <p class="text-muted">
                    {{ __('No definition yet.') }}
                    @if (Route::has('topic.edit'))
                        <a href="{{ route('topic.edit', ['concept' => $page->concept->public_id, 'locale' => $page->shownLocale?->code ?? 'en-GB']) }}" class="font-medium text-marker underline underline-offset-4">{{ __('Write one') }}</a>
                    @endif
                </p>
                @if ($alt !== '')
                    <p class="mt-3">{!! __('Also known as :names.', ['names' => $alt]) !!}</p>
                @endif
            @endif
        </div>

        {{-- Open decision (docs/page_design.md §7): a handwritten worked example goes here once there is somewhere to store one. --}}
        <div></div>
    </div>
</header>
