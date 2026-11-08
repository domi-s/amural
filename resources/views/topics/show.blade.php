<x-layouts.public :title="$page->name()">
    @php
        $concept = $page->concept;
        $lang = $page->langAttribute();
    @endphp

    <article>
        {{-- Breadcrumb from the "broader" links: the first parent at each level. --}}
        <nav aria-label="{{ __('Breadcrumb') }}" class="text-sm">
            <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <li><a href="{{ route('topics.index') }}" class="underline underline-offset-4">{{ __('Topics') }}</a></li>
                <li aria-hidden="true">/</li>
                @foreach ($page->breadcrumb as $crumb)
                    <li><a href="{{ $crumb->url }}" @if ($crumb->langAttribute()) lang="{{ $crumb->langAttribute() }}" @endif class="underline underline-offset-4">{{ $crumb->name }}</a></li>
                    <li aria-hidden="true">/</li>
                @endforeach
                <li aria-current="page" @if ($lang) lang="{{ $lang }}" @endif>{{ $page->name() }}</li>
            </ol>
        </nav>

        @if ($page->alsoUnder !== [])
            <p class="mt-1 text-sm">
                {{ __('Also under') }}:
                @foreach ($page->alsoUnder as $parent)
                    <a href="{{ $parent->url }}" @if ($parent->langAttribute()) lang="{{ $parent->langAttribute() }}" @endif class="underline underline-offset-4">{{ $parent->name }}</a>@if (! $loop->last), @endif
                @endforeach
            </p>
        @endif

        <header class="mt-6 border-b border-marker pb-8">
            <div class="flex items-start gap-5">
                <x-subject-icon :subject="$concept->subject->icon()" size="size-14" :label="$concept->subject->label()" class="mt-2 hidden sm:block" />

                <div class="min-w-0">
                    {{-- The display font only for text it can draw correctly; Inter otherwise (see DisplayFont). --}}
                    <h1 @if ($lang) lang="{{ $lang }}" @endif class="{{ \App\Support\DisplayFont::classes($page->name()) }} text-4xl leading-[1.08] sm:text-6xl">{{ $page->name() }}</h1>

                    @if ($page->altNames !== [])
                        <p class="mt-3 text-sm" @if ($lang) lang="{{ $lang }}" @endif>
                            <span class="opacity-70">{{ __('Also called') }}:</span>
                            {{ implode(', ', $page->altNames) }}
                        </p>
                    @endif

                    <ul class="mt-4 flex flex-wrap gap-2 text-xs uppercase tracking-wide">
                        <li class="border border-marker px-2 py-1">{{ $concept->subject->label() }}</li>
                        <li class="border border-marker px-2 py-1">{{ $concept->kind->label() }}</li>
                        <li class="border border-marker px-2 py-1">{{ $page->stageText }}</li>
                    </ul>
                </div>
            </div>

            @if ($concept->status === \App\Enums\ConceptStatus::Deprecated)
                <p class="mt-5 border border-dashed border-marker px-3 py-2 text-sm">{{ __('This topic is deprecated.') }}</p>
            @endif

            @if ($page->label->isFallback && $page->requestedLocale && $page->shownLocale)
                <p class="mt-5 border border-dashed border-marker px-3 py-2 text-sm">
                    {{ __('Not yet in :requested · shown in :shown', ['requested' => $page->requestedLocale->display_name, 'shown' => $page->shownLocale->display_name]) }}
                    @if (Route::has('topic.edit'))
                        · <a href="{{ route('topic.edit', ['concept' => $concept->public_id, 'locale' => $page->requestedLocale->code]) }}" class="underline underline-offset-4">{{ __('Add :language name', ['language' => $page->requestedLocale->display_name]) }}</a>
                    @endif
                </p>
            @endif

            <x-language-row :page="$page" />
        </header>

        {{-- Definition --}}
        <section class="mt-10" aria-labelledby="definition-heading">
            <h2 id="definition-heading" class="sr-only">{{ __('Definition') }}</h2>

            @if ($page->label->label?->definition)
                <p class="max-w-prose text-lg leading-relaxed" @if ($lang) lang="{{ $lang }}" @endif>{{ $page->label->label->definition }}</p>
            @else
                <p class="max-w-prose text-lg leading-relaxed opacity-60">{{ __('No definition yet.') }}</p>
            @endif
        </section>

        {{-- Structure: how this topic connects to others. --}}
        <div class="mt-12 grid gap-x-10 gap-y-10 sm:grid-cols-2">
            <x-topic-links :heading="__('Before this')" :links="$page->before" />
            <x-topic-links :heading="__('Leads to')" :links="$page->leadsTo" />
            <x-topic-links :heading="__('Inside this')" :links="$page->inside" />
            <x-topic-links :heading="__('Related')" :links="$page->related" />
        </div>

        <p class="mt-6 text-sm">
            <a href="{{ $page->toggleProposedUrl }}" class="underline underline-offset-4">{{ $page->showProposed ? __('Hide proposed links') : __('Show proposed links') }}</a>
        </p>

        <x-topic-lessons :page="$page" />

        <x-topic-artifacts :page="$page" />

        <x-topic-sources :page="$page" />

        {{-- History and editing arrive with the editor; the links appear once those pages exist. --}}
        @if (Route::has('topic.history') || (auth()->check() && Route::has('topic.edit')))
            <nav class="mt-14 flex flex-wrap gap-6 border-t border-marker pt-4 text-sm" aria-label="{{ __('Topic actions') }}">
                @if (Route::has('topic.history'))
                    <a href="{{ route('topic.history', ['concept' => $concept->public_id]) }}" class="inline-flex items-center gap-2 underline underline-offset-4"><x-icon name="history" size="size-4" />{{ __('History') }}</a>
                @endif
                @auth
                    @if (Route::has('topic.edit'))
                        <a href="{{ route('topic.edit', ['concept' => $concept->public_id, 'locale' => $page->shownLocale?->code ?? 'en-GB']) }}" class="inline-flex items-center gap-2 underline underline-offset-4"><x-icon name="pen" size="size-4" />{{ __('Edit') }}</a>
                    @endif
                @endauth
            </nav>
        @endif
    </article>
</x-layouts.public>
