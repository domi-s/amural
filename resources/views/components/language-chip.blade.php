@props(['chip'])

{{--
    One chip of the language row. Reviewed: solid. Machine or draft: faded flag and a dot, "needs checking".
    Missing: dashed outline and muted text, "no name yet — add one". The current language is filled.
--}}
@php
    $needsChecking = $chip->state === \App\Support\LanguageChip::NEEDS_CHECKING;
    $missing = $chip->state === \App\Support\LanguageChip::MISSING;

    $title = $needsChecking ? __('needs checking') : ($missing ? __('no name yet — add one') : $chip->locale->english_name);

    $state = $missing ? 'border-dashed text-marker/60' : 'border-solid';
    $state .= $needsChecking ? ' [&_svg]:opacity-60' : '';
    $state .= $chip->current ? ' bg-marker text-paper' : ' hover:bg-marker hover:text-paper';
@endphp
<a
    href="{{ $chip->url }}"
    title="{{ $title }}"
    @if ($chip->current) aria-current="true" @endif
    class="inline-flex items-center gap-2 border px-3 py-1.5 text-sm {{ $state }} focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marker"
>
    <x-language-label :locale="$chip->locale" :english="false" />
    @if ($needsChecking)
        <span class="size-1.5 shrink-0 bg-current" aria-hidden="true"></span>
        <span class="sr-only">{{ __('needs checking') }}</span>
    @elseif ($missing)
        <span class="sr-only">{{ __('no name yet — add one') }}</span>
    @endif
</a>
