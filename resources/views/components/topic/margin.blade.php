@props(['page'])

{{-- The left margin: the language setting and where the topic is filed. Plain text in small groups, no box. --}}
@php
    $concept = $page->concept;
    $requested = $page->requestedLocale;
@endphp

<aside class="text-sm" aria-label="{{ __('About this topic') }}">
    <section aria-labelledby="reading-in">
        <h2 id="reading-in" class="kicker text-muted">{{ __('Reading in') }}</h2>

        @if ($page->label->isFallback && $requested)
            <p class="mt-2 text-muted">{{ __('Not in :language yet.', ['language' => \Illuminate\Support\Str::ucfirst($requested->native_name)]) }}</p>
        @endif

        {{-- A real button per language: it sends ?lang= to that language's address, no script needed. --}}
        <form method="get" action="{{ url()->current() }}" class="mt-2 flex flex-wrap gap-x-4 gap-y-0 mag:flex-col">
            @foreach ($page->languages as $chip)
                <button
                    type="submit"
                    name="lang"
                    value="{{ $chip->locale->code }}"
                    formaction="{{ $chip->url }}"
                    lang="{{ $chip->locale->language }}"
                    aria-pressed="{{ $chip->current ? 'true' : 'false' }}"
                    class="min-h-11 py-2 text-left {{ $chip->current ? 'font-semibold underline decoration-2 underline-offset-[6px]' : 'hover:underline' }}"
                >{{ \Illuminate\Support\Str::ucfirst($chip->locale->native_name) }}@if ($chip->needsChecking) <span class="ml-1 font-normal text-muted" lang="{{ app()->getLocale() }}">{{ __('(needs checking)') }}</span>@endif</button>
            @endforeach
        </form>

        @if ($page->label->isFallback && $requested && Route::has('topic.edit'))
            <a href="{{ route('topic.edit', ['concept' => $concept->public_id, 'locale' => $requested->code]) }}" class="mt-1 inline-flex min-h-11 items-center text-muted underline-offset-4 hover:underline">{{ __('+ translate it') }}</a>
        @endif
    </section>

    <section class="mt-8" aria-labelledby="filed-under">
        <h2 id="filed-under" class="kicker text-muted">{{ __('Filed under') }}</h2>
        <p class="mt-2">{{ $concept->subject->label() }}</p>

        @if ($page->filedUnder !== [])
            <p class="mt-1">
                @foreach ($page->filedUnder as $parent)
                    <a href="{{ $parent->url }}" @if ($parent->langAttribute()) lang="{{ $parent->langAttribute() }}" @endif class="underline-offset-4 hover:underline">{{ $parent->name }}</a>@if (! $loop->last), @endif
                @endforeach
            </p>
        @endif

        <p class="mt-1 text-muted">{{ $page->ages }}</p>
    </section>

    @if (Route::has('topic.history') || (auth()->check() && Route::has('topic.edit')))
        <nav class="mt-8 flex flex-col" aria-label="{{ __('Topic actions') }}">
            @if (Route::has('topic.history'))
                <a href="{{ route('topic.history', ['concept' => $concept->public_id]) }}" class="inline-flex min-h-11 items-center underline-offset-4 hover:underline">{{ __('History') }}</a>
            @endif
            @auth
                @if (Route::has('topic.edit'))
                    <a href="{{ route('topic.edit', ['concept' => $concept->public_id, 'locale' => $page->shownLocale?->code ?? 'en-GB']) }}" class="inline-flex min-h-11 items-center underline-offset-4 hover:underline">{{ __('Edit this topic') }}</a>
                @endif
            @endauth
        </nav>
    @endif
</aside>
