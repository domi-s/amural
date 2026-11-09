<?php

use App\Actions\Concepts\UpdateConcept;
use App\Actions\Concepts\UpdateConceptLabel;
use App\Enums\ConceptKind;
use App\Enums\ConceptStatus;
use App\Enums\LabelStatus;
use App\Enums\Stage;
use App\Models\Concept;
use App\Models\Locale;
use App\Support\LocaleResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')] class extends Component {
    public Concept $concept;

    #[Locked]
    public string $locale = '';

    /** The language shown read-only beside the form. */
    public string $reference = '';

    public string $pref = '';

    /** @var list<string> */
    public array $alt = [];
    public string $newAlt = '';
    public string $definition = '';
    public string $note = '';
    public bool $markReviewed = false;

    /** 'saved' or 'unchanged' after pressing save. */
    public ?string $result = null;

    // What the topic is: curators set these on the default language's editor.
    public string $kind = '';
    public string $stageFrom = '';
    public string $stageTo = '';
    public string $conceptStatus = '';
    public bool $detailsSaved = false;

    public function mount(Concept $concept, string $locale, LocaleResolver $resolver): void
    {
        abort_unless(Locale::query()->whereKey($locale)->exists(), 404);

        $this->concept = $concept;
        $this->locale = $locale;
        $this->kind = $concept->kind->value;
        $this->stageFrom = $concept->stage_from->value;
        $this->stageTo = $concept->stage_to->value;
        $this->conceptStatus = $concept->status->value;

        $label = $concept->labels()->where('locale_code', $locale)->first();
        $this->pref = $label->pref ?? '';
        $this->alt = $label->alt ?? [];
        $this->definition = $label->definition ?? '';
        $this->markReviewed = $label?->status === LabelStatus::Reviewed && Auth::user()->isCurator();

        $named = $concept->labels()->where('locale_code', '!=', $locale)->pluck('locale_code')->all();
        $this->reference = collect([...$resolver->contentChain(), 'en-GB', ...$named])->first(fn ($code) => in_array($code, $named, true)) ?? '';
    }

    public function addAlt(): void
    {
        $name = trim($this->newAlt);

        if ($name !== '' && ! in_array($name, $this->alt, true)) {
            $this->alt[] = $name;
        }

        $this->newAlt = '';
    }

    public function removeAlt(int $index): void
    {
        unset($this->alt[$index]);
        $this->alt = array_values($this->alt);
    }

    public function save(UpdateConceptLabel $update): void
    {
        $this->addAlt();
        $this->validate(['note' => ['nullable', 'string', 'max:255']]);

        $before = $this->concept->labels()->where('locale_code', $this->locale)->value('current_revision_id');

        $label = $update->execute(
            $this->concept,
            $this->locale,
            ['pref' => $this->pref, 'alt' => $this->alt, 'definition' => $this->definition],
            Auth::user(),
            note: trim($this->note) ?: null,
            markReviewed: $this->markReviewed,
        );

        $this->result = $label->current_revision_id === $before ? 'unchanged' : 'saved';
        $this->note = '';
    }

    public function saveDetails(UpdateConcept $update): void
    {
        $update->execute($this->concept, Auth::user(), [
            'kind' => $this->kind,
            'stage_from' => $this->stageFrom,
            'stage_to' => $this->stageTo,
            'status' => $this->conceptStatus,
        ]);

        $this->detailsSaved = true;
    }

    public function rendering(View $view): void
    {
        $view->title(__('Edit topic'));
    }

    /**
     * @return array<string, mixed>
     */
    public function with(LocaleResolver $resolver): array
    {
        $labels = $this->concept->labels()->get()->keyBy('locale_code');
        $locales = Locale::query()->orderByDesc('is_launch')->orderBy('sort')->get();
        $chain = $resolver->contentChain();

        // Launch languages, the languages already named and the visitor's own are always shown;
        // the rest sit behind "More languages".
        [$shown, $more] = $locales->partition(
            fn (Locale $l) => $l->is_launch || $labels->has($l->code) || in_array($l->code, $chain, true)
        );

        $editing = $locales->firstWhere('code', $this->locale);

        return [
            'editing' => $editing,
            'labels' => $labels,
            'shown' => $shown,
            'more' => $more,
            'reviewedCount' => $labels->where('status', LabelStatus::Reviewed)->count(),
            'total' => $locales->count(),
            'referenceLabel' => $labels->get($this->reference),
            'referenceLocale' => $locales->firstWhere('code', $this->reference),
            'referenceOptions' => $locales->filter(fn (Locale $l) => $l->code !== $this->locale && $labels->has($l->code)),
            'scriptHint' => match ($editing->script) {
                'Grek' => __('Greek script'),
                'Cyrl' => __('Cyrillic script'),
                default => null,
            },
            'canReview' => Auth::user()->isCurator(),
            'showDetails' => Auth::user()->isCurator() && $this->locale === LocaleResolver::DEFAULT,
            'kinds' => ConceptKind::cases(),
            'stages' => Stage::cases(),
            'conceptStatuses' => array_filter(ConceptStatus::cases(), fn (ConceptStatus $status) => $status !== ConceptStatus::Deprecated),
        ];
    }
}; ?>

<div>
    <p class="text-sm"><a href="{{ route('topic.show', $concept->public_id) }}" class="underline underline-offset-4">&larr; {{ __('Back to the topic') }}</a></p>

    <h1 class="mt-4 font-display text-4xl italic leading-tight">{{ __('Edit topic') }}</h1>

    {{-- The language row: which languages have a name, and a way to switch the one being edited. --}}
    <nav aria-label="{{ __('Languages') }}" class="mt-6">
        <ul class="flex flex-wrap gap-2">
            @foreach ($shown as $l)
                @include('livewire.partials.editor-language', ['l' => $l, 'label' => $labels->get($l->code)])
            @endforeach
        </ul>

        @if ($more->isNotEmpty())
            <details class="mt-2">
                <summary class="inline-flex min-h-11 cursor-pointer items-center text-sm underline underline-offset-4">{{ __('More languages') }}</summary>
                <ul class="mt-2 flex flex-wrap gap-2">
                    @foreach ($more as $l)
                        @include('livewire.partials.editor-language', ['l' => $l, 'label' => $labels->get($l->code)])
                    @endforeach
                </ul>
            </details>
        @endif

        <p class="mt-2 text-sm text-muted">{{ __(':named of :total languages · :reviewed reviewed', ['named' => $labels->count(), 'total' => $total, 'reviewed' => $reviewedCount]) }}</p>
    </nav>

    <div class="mt-8 grid gap-8 lg:grid-cols-2">
        {{-- Reference: another language of the same topic, read-only. --}}
        <section aria-labelledby="reference-heading" class="border border-marker p-5">
            <h2 id="reference-heading" class="text-sm font-medium">{{ __('Reference') }}</h2>

            @if ($referenceLabel)
                <label class="mt-3 block text-sm">
                    <span class="sr-only">{{ __('Reference language') }}</span>
                    <select wire:model.live="reference" class="w-full border border-marker bg-paper px-3 py-2">
                        @foreach ($referenceOptions as $option)
                            <option value="{{ $option->code }}">{{ $option->display_name }} ({{ $option->english_name }})</option>
                        @endforeach
                    </select>
                </label>

                <div lang="{{ $referenceLocale->language }}" class="mt-4">
                    <p class="text-xl font-medium">{{ $referenceLabel->pref }}</p>
                    @if ($referenceLabel->alt)
                        <p class="mt-1 text-sm text-muted">{{ implode(', ', $referenceLabel->alt) }}</p>
                    @endif
                    @if ($referenceLabel->definition)
                        <p class="mt-3">{{ $referenceLabel->definition }}</p>
                    @endif
                </div>
            @else
                <p class="mt-3 text-sm text-muted">{{ __('No other language has a name for this topic yet.') }}</p>
            @endif
        </section>

        {{-- The form for the language being edited. --}}
        <form wire:submit="save" class="flex flex-col gap-5" lang="{{ $editing->language }}">
            <h2 class="text-sm font-medium" lang="{{ app()->getLocale() }}">
                <x-language-label :locale="$editing" />
            </h2>

            @if ($scriptHint)
                <p class="text-sm text-muted" lang="{{ app()->getLocale() }}">{{ __('Write this in :script.', ['script' => $scriptHint]) }}</p>
            @endif

            <label class="block">
                <span class="text-sm font-medium" lang="{{ app()->getLocale() }}">{{ __('Name') }}</span>
                <input wire:model="pref" type="text" required maxlength="200" class="mt-1 w-full border border-marker bg-paper px-3 py-2" aria-describedby="pref-error">
                @error('pref') <span id="pref-error" class="mt-1 block text-sm font-medium">{{ $message }}</span> @enderror
            </label>

            <div>
                <label for="new-alt" class="text-sm font-medium" lang="{{ app()->getLocale() }}">{{ __('Other names') }}</label>
                @if ($alt)
                    <ul class="mt-2 flex flex-wrap gap-2">
                        @foreach ($alt as $index => $name)
                            <li class="inline-flex items-center gap-1 border border-marker py-1 pl-3 pr-1">
                                <span>{{ $name }}</span>
                                <button type="button" wire:click="removeAlt({{ $index }})" class="inline-flex size-8 items-center justify-center" aria-label="{{ __('Remove :name', ['name' => $name]) }}">&times;</button>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <input id="new-alt" wire:model="newAlt" wire:keydown.enter.prevent="addAlt" type="text" maxlength="200" class="mt-2 w-full border border-marker bg-paper px-3 py-2" aria-describedby="alt-help">
                <p id="alt-help" class="mt-1 text-sm text-muted" lang="{{ app()->getLocale() }}">{{ __('Press Enter to add a name.') }}</p>
                @error('alt') <span class="mt-1 block text-sm font-medium">{{ $message }}</span> @enderror
                @error('alt.*') <span class="mt-1 block text-sm font-medium">{{ $message }}</span> @enderror
            </div>

            <label class="block">
                <span class="text-sm font-medium" lang="{{ app()->getLocale() }}">{{ __('Definition') }}</span>
                <textarea wire:model="definition" rows="4" maxlength="1000" class="mt-1 w-full border border-marker bg-paper px-3 py-2" aria-describedby="definition-error"></textarea>
                @error('definition') <span id="definition-error" class="mt-1 block text-sm font-medium">{{ $message }}</span> @enderror
            </label>

            <label class="block" lang="{{ app()->getLocale() }}">
                <span class="text-sm font-medium">{{ __('What did you change?') }}</span>
                <input wire:model="note" type="text" maxlength="255" class="mt-1 w-full border border-marker bg-paper px-3 py-2">
                @error('note') <span class="mt-1 block text-sm font-medium">{{ $message }}</span> @enderror
            </label>

            @if ($canReview)
                <label class="inline-flex min-h-11 cursor-pointer items-center gap-3" lang="{{ app()->getLocale() }}">
                    <input wire:model="markReviewed" type="checkbox" class="size-5 accent-marker">
                    <span>{{ __('Mark as reviewed') }}</span>
                </label>
            @endif

            <div class="flex items-center gap-4" lang="{{ app()->getLocale() }}">
                <button type="submit" class="border border-marker bg-marker px-5 py-2.5 font-medium text-paper hover:bg-paper hover:text-marker focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marker">{{ __('Save') }}</button>

                <p role="status" class="text-sm">
                    @if ($result === 'saved') {{ __('Saved.') }} @elseif ($result === 'unchanged') {{ __('Nothing changed.') }} @endif
                </p>
            </div>
        </form>
    </div>

    @if ($showDetails)
        <form wire:submit="saveDetails" class="mt-10 flex max-w-xl flex-col gap-5 border-t border-marker pt-8">
            <h2 class="text-sm font-medium">{{ __('About the topic') }}</h2>

            <label class="block">
                <span class="text-sm font-medium">{{ __('Kind') }}</span>
                <select wire:model="kind" class="mt-1 w-full border border-marker bg-paper px-3 py-2">
                    @foreach ($kinds as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>
            </label>

            <div class="grid gap-5 sm:grid-cols-2">
                <label class="block">
                    <span class="text-sm font-medium">{{ __('First stage') }}</span>
                <select wire:model="stageFrom" class="mt-1 w-full border border-marker bg-paper px-3 py-2">
                    @foreach ($stages as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>
                </label>
                <label class="block">
                    <span class="text-sm font-medium">{{ __('Last stage') }}</span>
                <select wire:model="stageTo" class="mt-1 w-full border border-marker bg-paper px-3 py-2">
                    @foreach ($stages as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>
                    @error('stage_to') <span class="mt-1 block text-sm font-medium">{{ $message }}</span> @enderror
                </label>
            </div>

            <label class="block">
                <span class="text-sm font-medium">{{ __('Status') }}</span>
                <select wire:model="conceptStatus" class="mt-1 w-full border border-marker bg-paper px-3 py-2">
                    @foreach ($conceptStatuses as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>
            </label>

            <div class="flex items-center gap-4">
                <button type="submit" class="border border-marker bg-marker px-5 py-2.5 font-medium text-paper hover:bg-paper hover:text-marker focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marker">{{ __('Save') }}</button>
                <p role="status" class="text-sm">@if ($detailsSaved) {{ __('Saved.') }} @endif</p>
            </div>
        </form>
    @endif
</div>
