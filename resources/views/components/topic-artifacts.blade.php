@props(['page'])

{{--
    Videos, files and links on the topic. A YouTube video shows its thumbnail and loads the player from
    youtube-nocookie.com only when it is clicked. A language filter appears when there is a choice.
--}}
<section class="mt-14" aria-labelledby="artifacts-heading">
    <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-2 border-b border-marker pb-1">
        <h2 id="artifacts-heading" class="text-xs font-semibold uppercase tracking-wide">{{ __('Artifacts') }}</h2>

        @if (Route::has('artifacts.create'))
            <a href="{{ route('artifacts.create', ['attach' => 'concept:'.$page->concept->public_id]) }}" class="inline-flex items-center gap-2 border border-marker px-3 py-1.5 text-sm font-medium hover:bg-marker hover:text-paper focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marker">
                <x-icon name="plus" size="size-4" />
                {{ __('Attach a video or file') }}
            </a>
        @endif
    </div>

    @if ($page->artifactFilter !== [])
        <nav class="mt-4" aria-label="{{ __('Filter by language') }}">
            <ul class="flex flex-wrap gap-2 text-sm">
                @foreach ($page->artifactFilter as $option)
                    <li>
                        <a
                            href="{{ $option->url }}"
                            @if ($option->active) aria-current="true" @endif
                            class="inline-flex items-center gap-2 border border-marker px-3 py-1.5 {{ $option->active ? 'bg-marker text-paper' : 'hover:bg-marker hover:text-paper' }} focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marker"
                        >
                            @if ($option->locale)
                                <x-language-label :locale="$option->locale" :english="false" />
                            @elseif ($option->key === \App\Support\ArtifactFilterOption::NEUTRAL)
                                {{ __('No language') }}
                            @else
                                {{ __('All languages') }}
                            @endif
                            <span class="opacity-70">{{ $option->count }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif

    @if ($page->artifacts === [])
        <p class="mt-4 text-sm opacity-70">{{ $page->artifactTotal === 0 ? __('No videos, files or links yet.') : __('Nothing in this language yet.') }}</p>
    @else
        <ul class="mt-6 grid gap-6 sm:grid-cols-2">
            @foreach ($page->artifacts as $entry)
                @php($artifact = $entry->artifact)
                <li class="flex flex-col gap-3 border border-marker p-3">
                    @if ($entry->isYoutube())
                        <div x-data="{ playing: false }" class="aspect-video border border-marker bg-marker/5">
                            <template x-if="playing">
                                <iframe
                                    src="https://www.youtube-nocookie.com/embed/{{ rawurlencode($artifact->youtube_id) }}?autoplay=1&amp;rel=0"
                                    title="{{ $artifact->title }}"
                                    allow="autoplay; encrypted-media; picture-in-picture; fullscreen"
                                    allowfullscreen
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    class="size-full"
                                ></iframe>
                            </template>
                            <button
                                type="button"
                                x-show="! playing"
                                x-on:click="playing = true"
                                aria-label="{{ __('Play video: :title', ['title' => $artifact->title]) }}"
                                class="relative block size-full focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marker"
                            >
                                <img
                                    src="https://i.ytimg.com/vi/{{ rawurlencode($artifact->youtube_id) }}/hqdefault.jpg"
                                    alt=""
                                    loading="lazy"
                                    referrerpolicy="no-referrer"
                                    class="size-full object-cover"
                                />
                                <span class="absolute inset-0 grid place-items-center">
                                    <span class="border border-marker bg-paper p-3"><x-icon name="play" size="size-8" /></span>
                                </span>
                            </button>
                        </div>
                    @endif

                    <div class="flex items-start gap-3">
                        <x-icon :name="$artifact->type->icon()" size="size-6" class="mt-0.5" />

                        <div class="min-w-0">
                            <p class="font-medium" @if ($entry->locale) lang="{{ $entry->locale->language }}" @endif>
                                @if (! $entry->isYoutube() && $entry->safeUrl())
                                    <a href="{{ $entry->safeUrl() }}" rel="ugc nofollow noopener noreferrer" class="underline decoration-1 underline-offset-4 hover:bg-marker hover:text-paper">{{ $artifact->title }}</a>
                                @else
                                    {{ $artifact->title }}
                                @endif
                            </p>
                            <p class="text-xs opacity-70">
                                {{ $artifact->type->label() }}
                                @if ($entry->host && ! $entry->isYoutube()) · {{ $entry->host }} @endif
                                · {{ $artifact->licence }}
                            </p>
                            @if ($artifact->description)
                                <p class="mt-2 text-sm" @if ($entry->locale) lang="{{ $entry->locale->language }}" @endif>{{ $artifact->description }}</p>
                            @endif
                        </div>
                    </div>

                    <p class="text-sm">
                        @if ($entry->locale)
                            <x-language-label :locale="$entry->locale" :english="false" />
                        @else
                            <span class="opacity-70">{{ __('No language') }}</span>
                        @endif
                    </p>
                </li>
            @endforeach
        </ul>
    @endif
</section>
