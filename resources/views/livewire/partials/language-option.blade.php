<li>
    <button
        type="button"
        @if ($locale->code === $current?->code)
            aria-current="true"
            x-on:click="open = false"
        @else
            wire:click="select('{{ $locale->code }}')"
        @endif
        class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left hover:bg-marker hover:text-paper focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-marker"
    >
        <span class="inline-flex items-center gap-2">
            @if ($locale->code === $current?->code)<span aria-hidden="true">&#9632;</span>@endif
            <x-language-label :locale="$locale" :english="false" />
        </span>
        @if ($locale->english_name !== $locale->native_name)
            <span class="text-xs opacity-70">{{ $locale->english_name }}</span>
        @endif
    </button>
</li>
