<x-layouts.public :title="$concept->label()->label->pref ?? __('Untitled topic')">
    <h1 class="text-4xl">{{ $concept->label()->label->pref ?? __('Untitled topic') }}</h1>

    @if ($concept->status === \App\Enums\ConceptStatus::Deprecated)
        <p class="mt-4 text-muted">{{ __('This topic is deprecated.') }}</p>
    @endif
</x-layouts.public>
