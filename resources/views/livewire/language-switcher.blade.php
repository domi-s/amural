<div
    x-data="{ open: false, tab: 'list' }"
    x-on:keydown.escape.window="open = false"
    x-on:click.outside="open = false"
    wire:loading.class="opacity-60"
    wire:target="select"
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
        @if ($current)
            <x-language-label :locale="$current" :english="false" />
        @endif
        <svg aria-hidden="true" viewBox="0 0 12 12" class="size-3" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 4.5 6 8.5 10 4.5" /></svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition.opacity.duration.100ms
        x-bind:class="tab === 'map' ? 'w-[min(94vw,46rem)]' : 'w-72'"
        class="absolute right-0 z-50 mt-2 max-h-[85vh] overflow-y-auto border border-marker bg-paper"
    >
        <p class="border-b border-marker px-3 py-2 text-xs uppercase tracking-wide">{{ __('Show topics in') }}</p>

        {{-- Two ways to choose: the list (always works, keyboard first) or the map of Europe. --}}
        <div role="tablist" aria-label="{{ __('Choose by') }}" class="flex border-b border-marker">
            <button
                type="button"
                role="tab"
                id="language-tab-list"
                aria-controls="language-panel-list"
                x-bind:aria-selected="(tab === 'list').toString()"
                x-on:click="tab = 'list'"
                x-bind:class="tab === 'list' ? 'bg-marker text-paper' : 'hover:bg-marker hover:text-paper'"
                class="flex flex-1 items-center justify-center gap-2 px-3 py-2 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-marker"
            >
                <x-icon name="filters" size="size-4" />{{ __('List') }}
            </button>
            <button
                type="button"
                role="tab"
                id="language-tab-map"
                aria-controls="language-panel-map"
                x-bind:aria-selected="(tab === 'map').toString()"
                x-on:click="tab = 'map'"
                x-bind:class="tab === 'map' ? 'bg-marker text-paper' : 'hover:bg-marker hover:text-paper'"
                class="flex flex-1 items-center justify-center gap-2 border-l border-marker px-3 py-2 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-marker"
            >
                <x-icon name="globe" size="size-4" />{{ __('Map') }}
            </button>
        </div>

        <div id="language-panel-list" role="tabpanel" aria-labelledby="language-tab-list" x-show="tab === 'list'">
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

        {{-- The map is fetched the first time this tab opens, so pages do not carry it. --}}
        <div
            id="language-panel-map"
            role="tabpanel"
            aria-labelledby="language-tab-map"
            x-show="tab === 'map'"
            x-cloak
            x-data="europeMap(@js($mapConfig))"
            x-effect="if (open && tab === 'map') load()"
            class="p-3"
        >
            <p class="min-h-6 text-base font-medium" aria-live="polite" x-text="caption"></p>

            <p x-show="status === 'loading'" class="py-16 text-center opacity-70">{{ __('Loading the map…') }}</p>
            <p x-show="status === 'failed'" x-cloak role="alert" class="py-16 text-center">{{ __('The map could not be loaded. Use the list instead.') }}</p>

            <div
                x-ref="map"
                x-html="markup"
                x-show="status === 'ready'"
                x-on:click="choose($event)"
                x-on:keydown.enter.prevent="choose($event)"
                x-on:keydown.space.prevent="choose($event)"
                x-on:mouseover="hover($event)"
                x-on:focusin="hover($event)"
                x-on:mouseleave="hovered = null"
                class="europe-map"
            ></div>

            <p x-show="status === 'ready' && config.current" class="mt-2 text-xs opacity-70">
                {{ __('Shaded: where :language is spoken', ['language' => $current?->display_name]) }}
            </p>

            <div x-show="country" x-cloak class="mt-3 border-t border-marker pt-3" aria-live="polite">
                <ul class="flex flex-wrap gap-2">
                    <template x-for="locale in locales" x-bind:key="locale.code">
                        <li>
                            <button
                                type="button"
                                x-on:click="locale.code === config.currentCode ? (open = false) : $wire.select(locale.code)"
                                x-bind:aria-current="locale.code === config.currentCode ? 'true' : null"
                                class="inline-flex items-center gap-2 border border-marker px-3 py-1.5 hover:bg-marker hover:text-paper focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marker"
                                x-bind:class="locale.code === config.currentCode ? 'bg-marker text-paper' : ''"
                            >
                                <svg viewBox="0 0 120 84" role="img" x-bind:aria-label="locale.flagLabel" class="h-4 aspect-[120/84] w-auto shrink-0"><use x-bind:href="config.flagsUrl + '#flag-' + locale.flag"></use></svg>
                                <span x-bind:lang="locale.lang" class="font-medium" x-text="locale.name"></span>
                                <span class="text-xs opacity-70" x-text="locale.english !== locale.name ? locale.english : ''"></span>
                            </button>
                        </li>
                    </template>
                </ul>

                <p x-show="locales.length === 0" class="text-sm">{{ __('No language of this country is available yet.') }}</p>
                <p x-show="locales.length > 0 && unavailable > 0" class="mt-2 text-xs opacity-70">{{ __('Other languages spoken here are not available yet.') }}</p>
            </div>
        </div>
    </div>
</div>
