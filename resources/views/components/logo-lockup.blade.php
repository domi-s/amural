@props(['stacked' => false, 'tagline' => true])

{{-- The cat-in-stars mark with the name beside it (primary lockup) or beneath it (stacked). The wordmark is set in the display font. --}}
<div {{ $attributes->class(['inline-flex text-marker', $stacked ? 'flex-col items-center gap-3 text-center' : 'items-center gap-8']) }}>
    <x-logo :class="$stacked ? 'h-48 w-auto' : 'h-44 w-auto'" />

    <div class="{{ $stacked ? '' : 'flex flex-col gap-3' }}">
        <p class="font-display text-7xl italic leading-[0.9] {{ $stacked ? '' : 'sm:text-8xl' }}">{{ config('app.name') }}</p>

        @if ($tagline && ! $stacked)
            <p class="{{ \App\Support\DisplayFont::classes(__('Lessons, shared across Europe.')) }} pl-2 text-2xl">{{ __('Lessons, shared across Europe.') }}</p>
        @endif
    </div>
</div>
