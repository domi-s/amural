@props(['page'])

{{-- Published lessons on the topic, by language: the visitor's own languages first. --}}
<section class="mt-14" aria-labelledby="lessons-heading">
    <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-2 border-b border-marker pb-1">
        <h2 id="lessons-heading" class="text-xs font-semibold uppercase tracking-wide">{{ __('Lessons') }}</h2>

        @if (Route::has('lessons.create'))
            <a href="{{ route('lessons.create', ['topic' => $page->concept->public_id]) }}" class="inline-flex items-center gap-2 border border-marker px-3 py-1.5 text-sm font-medium hover:bg-marker hover:text-paper focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marker">
                <x-icon name="pen" size="size-4" />
                {{ __('Write a lesson on this topic') }}
            </a>
        @endif
    </div>

    @forelse ($page->lessonGroups as $group)
        <div class="mt-6">
            <h3 class="text-sm"><x-language-label :locale="$group->locale" /></h3>

            <ul class="mt-2 divide-y divide-marker/20 border-y border-marker/20">
                @foreach ($group->lessons as $entry)
                    <li class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-1 py-3">
                        @if ($entry->url)
                            <a href="{{ $entry->url }}" lang="{{ $group->locale->language }}" class="text-lg underline decoration-1 underline-offset-4 hover:bg-marker hover:text-paper">{{ $entry->title }}</a>
                        @else
                            <span lang="{{ $group->locale->language }}" class="text-lg">{{ $entry->title }}</span>
                        @endif

                        <span class="text-sm opacity-70">
                            {{ __('by :author', ['author' => $entry->author]) }}
                            @if ($entry->updatedAt)
                                · {{ __('Updated :date', ['date' => $entry->updatedAt->isoFormat('LL')]) }}
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @empty
        <p class="mt-4 text-sm opacity-70">{{ __('No lessons on this topic yet.') }}</p>
    @endforelse
</section>
