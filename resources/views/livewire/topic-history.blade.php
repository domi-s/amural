<?php

use App\Actions\Concepts\RestoreConceptLabel;
use App\Models\Concept;
use App\Models\ConceptLabelRevision;
use App\Models\Locale;
use App\Support\LocaleResolver;
use App\Support\WordDiff;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')] class extends Component {
    public Concept $concept;

    #[Url(as: 'locale')]
    public string $locale = '';

    public function mount(Concept $concept, LocaleResolver $resolver): void
    {
        $this->concept = $concept;

        $named = $concept->labels()->pluck('locale_code')->all();

        if (! in_array($this->locale, $named, true)) {
            $this->locale = collect($resolver->contentChain())->first(fn ($code) => in_array($code, $named, true)) ?? ($named[0] ?? '');
        }
    }

    public function restore(int $revisionId, RestoreConceptLabel $restore): void
    {
        abort_unless(Auth::check(), 403);

        $revision = ConceptLabelRevision::query()
            ->whereHas('label', fn ($q) => $q->where('concept_id', $this->concept->id))
            ->findOrFail($revisionId);

        $restore->execute($revision, Auth::user());
        $this->locale = $revision->label->locale_code;
    }

    public function rendering(View $view): void
    {
        $view->title(__('History'));
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $labels = $this->concept->labels()->get()->keyBy('locale_code');
        $label = $labels->get($this->locale);

        $revisions = $label
            ? ConceptLabelRevision::query()->with('author')->where('concept_label_id', $label->id)->orderByDesc('id')->get()
            : collect();

        // Each revision is compared with the one before it (the next in this newest-first list).
        $entries = $revisions->values()->map(function (ConceptLabelRevision $revision, int $i) use ($revisions) {
            $previous = $revisions->get($i + 1);

            return [
                'revision' => $revision,
                'changes' => array_filter([
                    __('Name') => WordDiff::between($previous->pref ?? '', $revision->pref),
                    __('Other names') => WordDiff::between(implode(', ', $previous->alt ?? []), implode(', ', $revision->alt)),
                    __('Definition') => WordDiff::between($previous->definition ?? '', (string) $revision->definition),
                ], fn (array $diff) => collect($diff)->contains(fn ($segment) => $segment[0] !== WordDiff::SAME)),
            ];
        });

        return [
            'locales' => Locale::query()->whereIn('code', $labels->keys())->orderByDesc('is_launch')->orderBy('sort')->get(),
            'currentId' => $label?->current_revision_id,
            'entries' => $entries,
        ];
    }
}; ?>

<div class="max-w-3xl">
    <p class="text-sm"><a href="{{ route('topic.show', $concept->public_id) }}" class="underline underline-offset-4">&larr; {{ __('Back to the topic') }}</a></p>

    <h1 class="mt-4 font-display text-4xl italic leading-tight">{{ __('History') }}</h1>

    @if ($locales->isEmpty())
        <p class="mt-6 text-muted">{{ __('This topic has no name in any language yet.') }}</p>
    @else
        <nav aria-label="{{ __('Languages') }}" class="mt-6">
            <ul class="flex flex-wrap gap-2">
                @foreach ($locales as $l)
                    <li>
                        <a
                            href="{{ route('topic.history', ['concept' => $concept->public_id, 'locale' => $l->code]) }}"
                            @if ($l->code === $locale) aria-current="page" @endif
                            @class([
                                'inline-flex min-h-11 items-center border border-marker px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marker',
                                'bg-marker text-paper' => $l->code === $locale,
                            ])
                        >
                            <x-language-label :locale="$l" :english="false" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <ol class="mt-8 flex flex-col gap-8">
            @foreach ($entries as $entry)
                @php($revision = $entry['revision'])
                <li class="border border-marker p-5">
                    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                        <p class="font-medium">
                            {{ $revision->author?->name ?? __('Imported') }}
                            <span class="font-normal text-muted">· <time datetime="{{ $revision->created_at->toIso8601String() }}">{{ $revision->created_at->isoFormat('D MMM YYYY, HH:mm') }}</time></span>
                        </p>
                        <p class="text-sm">
                            {{ match ($revision->status->value) { 'reviewed' => __('Reviewed'), 'draft' => __('Draft'), default => __('Machine') } }}
                            @if ($revision->id === $currentId) · {{ __('Current version') }} @endif
                        </p>
                    </div>

                    @if ($revision->note)
                        <p class="mt-2">{{ $revision->note }}</p>
                    @endif

                    <dl class="mt-4 flex flex-col gap-3 text-sm">
                        @foreach ($entry['changes'] as $field => $segments)
                            <div>
                                <dt class="font-medium">{{ $field }}</dt>
                                <dd lang="{{ explode('-', $locale)[0] }}">
                                    @foreach ($segments as [$kind, $text])
                                        @if ($kind === 'added')<ins class="bg-marker px-0.5 text-paper no-underline">{{ $text }}</ins>@elseif ($kind === 'removed')<del class="px-0.5 line-through opacity-70">{{ $text }}</del>@else{{ $text }}@endif
                                    @endforeach
                                </dd>
                            </div>
                        @endforeach
                    </dl>

                    @auth
                        @if ($revision->id !== $currentId)
                            <button type="button" wire:click="restore({{ $revision->id }})" wire:confirm="{{ __('Restore this version? It is added as a new version; nothing is lost.') }}" class="mt-4 inline-flex min-h-11 items-center border border-marker px-4 text-sm font-medium hover:bg-marker hover:text-paper focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marker">{{ __('Restore this version') }}</button>
                        @endif
                    @endauth
                </li>
            @endforeach
        </ol>
    @endif
</div>
