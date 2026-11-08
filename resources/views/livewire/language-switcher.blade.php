<div
    x-data="{ open: false }"
    x-on:keydown.escape.window="open = false"
    x-on:click.outside="open = false"
    class="relative font-sans text-sm text-marker"
>
    <button
        type="button"
        x-on:click="open = ! open"
        x-bind:aria-expanded="open.toString()"
        aria-haspopup="true"
        aria-label="{{ __('Language') }}: {{ $current?->display_name }}"
        class="inline-flex items-center gap-2 border border-marker bg-paper px-3 py-1.5 text-marker hover:bg-marker hover:text-paper focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marker"
    >
        <span lang="{{ $current?->language }}">{{ $current?->display_name }}</span>
        <svg aria-hidden="true" viewBox="0 0 12 12" class="size-3" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 4.5 6 8.5 10 4.5" /></svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition.opacity.duration.100ms
        class="absolute right-0 z-50 mt-2 max-h-96 w-72 overflow-y-auto border border-marker bg-paper"
    >
        <p class="border-b border-marker px-3 py-2 text-xs uppercase tracking-wide">{{ __('Show topics in') }}</p>

        <ul>
            @foreach ($launch as $locale)
                @include('livewire.partials.language-option', ['locale' => $locale, 'current' => $current])
            @endforeach

            <li role="separator" class="border-t border-marker"></li>

            @foreach ($others as $locale)
                @include('livewire.partials.language-option', ['locale' => $locale, 'current' => $current])
            @endforeach
        </ul>
    </div>
</div>
