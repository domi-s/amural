<x-layouts.public :title="__('Curator queue')">
    <div class="max-w-3xl">
        <h1 class="font-display text-4xl italic leading-tight">{{ __('Curator queue') }}</h1>
        <p class="mt-2 text-muted">{{ __('Names waiting to be checked, the oldest first.') }}</p>

        @if ($total === 0)
            <p class="mt-8">{{ __('Nothing is waiting to be checked.') }}</p>
        @else
            <nav aria-label="{{ __('Languages') }}" class="mt-6">
                <ul class="flex flex-wrap gap-2">
                    <li>
                        <a href="{{ route('curate') }}" @if (! $filter) aria-current="page" @endif @class(['inline-flex min-h-11 items-center border border-marker px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marker', 'bg-marker text-paper' => ! $filter])>{{ __('All languages') }} ({{ $total }})</a>
                    </li>
                    @foreach ($locales as $locale)
                        <li>
                            <a href="{{ route('curate', ['locale' => $locale->code]) }}" @if ($filter?->code === $locale->code) aria-current="page" @endif @class(['inline-flex min-h-11 items-center gap-2 border border-marker px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marker', 'bg-marker text-paper' => $filter?->code === $locale->code])>
                                <x-language-label :locale="$locale" :english="false" /> ({{ $counts[$locale->code] }})
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <ul class="mt-8 flex flex-col">
                @foreach ($labels as $label)
                    <li class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2 border-t border-marker py-4 last:border-b">
                        <div>
                            <p lang="{{ $label->locale->language }}" class="text-lg font-medium">{{ $label->pref }}</p>
                            <p class="mt-1 flex flex-wrap items-center gap-x-3 text-sm text-muted">
                                <x-language-label :locale="$label->locale" :english="false" />
                                <span>{{ $label->status === \App\Enums\LabelStatus::Machine ? __('Machine') : __('Draft') }}</span>
                                <span>{{ __('Updated :date', ['date' => $label->updated_at->isoFormat('D MMM YYYY')]) }}</span>
                            </p>
                        </div>
                        <a href="{{ route('topic.edit', ['concept' => $label->concept->public_id, 'locale' => $label->locale_code]) }}" class="inline-flex min-h-11 items-center border border-marker px-4 text-sm font-medium hover:bg-marker hover:text-paper focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marker">{{ __('Review') }}</a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6">{{ $labels->links() }}</div>
        @endif
    </div>
</x-layouts.public>
