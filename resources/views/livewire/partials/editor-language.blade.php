{{-- One language in the editor's language row. A missing name is a dashed outline; one that is not reviewed is dimmed with a dot. --}}
@php
    $missing = $label === null;
    $needsChecking = ! $missing && $label->status !== \App\Enums\LabelStatus::Reviewed;
    $current = $l->code === $this->locale;
@endphp
<li>
    <a
        href="{{ route('topic.edit', ['concept' => $concept->public_id, 'locale' => $l->code]) }}"
        @if ($current) aria-current="page" @endif
        title="{{ $missing ? __('No name yet. Add one.') : ($needsChecking ? __('Needs checking') : '') }}"
        @class([
            'inline-flex min-h-11 items-center gap-2 border px-3 text-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marker',
            'border-marker bg-marker text-paper' => $current,
            'border-dashed border-marker text-muted' => $missing && ! $current,
            'border-marker' => ! $missing && ! $current,
        ])
    >
        <span @class(['inline-flex items-center gap-2', 'opacity-60' => $needsChecking && ! $current])>
            <x-language-label :locale="$l" :english="false" />
        </span>
        @if ($needsChecking)<span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>@endif
    </a>
</li>
