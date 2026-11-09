@props(['entry', 'large' => false])

{{--
    A video: a tinted shape with the sketched play mark. Nothing is fetched from YouTube until it is clicked,
    and then only from youtube-nocookie.com. We never show YouTube's thumbnail: that would be a request to Google.
--}}
@php
    $artifact = $entry->artifact;
    $lang = $entry->locale?->language;
    $title = $artifact->title;
@endphp

<figure x-data="{ playing: false }">
    <div class="aspect-video overflow-hidden rounded-hand bg-tint">
        <template x-if="playing">
            <iframe
                src="https://www.youtube-nocookie.com/embed/{{ rawurlencode($artifact->youtube_id) }}?autoplay=1&amp;rel=0"
                title="{{ $title }}"
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
            aria-label="{{ __('Play video: :title', ['title' => $title]) }}"
            class="grid size-full place-items-center text-marker hover:bg-tint-2"
        >
            <x-icon.play class="{{ $large ? 'size-20' : 'size-14' }}" />
        </button>
    </div>

    <figcaption class="mt-4">
        <p @if ($lang) lang="{{ $lang }}" @endif class="{{ $large ? \App\Support\DisplayFont::classes($title).' text-[28px] leading-tight' : 'text-lg font-medium leading-snug' }}">{{ $title }}</p>
        <p class="mt-1 text-sm text-muted">{{ $artifact->type->label() }} · {{ $artifact->owner->name }}@if ($entry->locale) · {{ \Illuminate\Support\Str::ucfirst($entry->locale->native_name) }}@endif</p>
    </figcaption>
</figure>
