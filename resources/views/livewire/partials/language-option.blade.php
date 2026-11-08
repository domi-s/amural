<li>
    <button
        type="button"
        wire:click="select('{{ $locale->code }}')"
        @if ($locale->code === $current?->code) aria-current="true" @endif
        class="flex w-full items-baseline justify-between gap-3 px-3 py-2 text-left hover:bg-marker hover:text-paper focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-marker"
    >
        <span lang="{{ $locale->language }}" class="font-medium">
            @if ($locale->code === $current?->code)<span aria-hidden="true">&#9632;</span> @endif{{ $locale->native_name }}
        </span>
        @if ($locale->english_name !== $locale->native_name)
            <span class="text-xs opacity-70">{{ $locale->english_name }}</span>
        @endif
    </button>
</li>
