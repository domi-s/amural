@props(['page'])

{{--
    The language row (spec 5.5): one chip per language, launch languages first, then the visitor's own,
    then "more languages". Clicking a chip shows the topic in that language (?lang=).
--}}
<section class="mt-8" aria-labelledby="language-row-heading" x-data="{ more: false }">
    <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-1">
        <h2 id="language-row-heading" class="text-xs font-semibold uppercase tracking-wide">{{ __('Languages') }}</h2>
        <p class="text-sm">{{ __(':count of :total languages · :reviewed reviewed', ['count' => $page->namedCount, 'total' => $page->languageCount, 'reviewed' => $page->reviewedCount]) }}</p>
    </div>

    <ul class="mt-3 flex flex-wrap gap-2">
        @foreach ($page->languageRow as $chip)
            <li><x-language-chip :chip="$chip" /></li>
        @endforeach

        @if ($page->moreLanguages !== [])
            <li>
                <button
                    type="button"
                    x-on:click="more = ! more"
                    x-bind:aria-expanded="more.toString()"
                    class="inline-flex items-center gap-2 border border-dashed px-3 py-1.5 text-sm hover:bg-marker hover:text-paper focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marker"
                >
                    <x-icon name="plus" size="size-3.5" x-show="! more" />
                    <span x-text="more ? @js(__('Fewer languages')) : @js(__('More languages'))">{{ __('More languages') }}</span>
                </button>
            </li>
        @endif
    </ul>

    @if ($page->moreLanguages !== [])
        <ul x-show="more" x-cloak class="mt-2 flex flex-wrap gap-2">
            @foreach ($page->moreLanguages as $chip)
                <li><x-language-chip :chip="$chip" /></li>
            @endforeach
        </ul>
    @endif
</section>
