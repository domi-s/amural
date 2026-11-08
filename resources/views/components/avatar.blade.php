@props(['preset' => 1, 'size' => 'size-12', 'label' => null])

{{-- Placeholder for the eight default sketched people (the real drawings come later). --}}
@php
    $preset = max(1, min(8, (int) $preset));
@endphp

<svg
    {{ $attributes->class([$size, 'shrink-0 text-marker'])->merge([
        'viewBox' => '0 0 64 64',
        'role' => 'img',
        'aria-label' => $label ?? __('Avatar :number', ['number' => $preset]),
    ]) }}
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
>
    <circle cx="32" cy="32" r="30" fill="var(--color-paper)" />
    {{-- shoulders --}}
    <path d="M14 54c3-9 10-13 18-13s15 4 18 13" />
    {{-- head --}}
    <circle cx="32" cy="28" r="10" fill="var(--color-paper)" />
    {{-- face --}}
    <circle cx="28.5" cy="28" r="0.9" fill="currentColor" stroke="none" />
    <circle cx="35.5" cy="28" r="0.9" fill="currentColor" stroke="none" />
    <path d="M29 32.5c1.8 1.6 4.2 1.6 6 0" />

    @switch($preset)
        @case(1) {{-- short hair --}}
            <path d="M22 26c0-7 5-11 10-11s10 4 10 11c-3-3-6-4-10-4s-7 1-10 4z" fill="currentColor" />
            @break
        @case(2) {{-- long hair --}}
            <path d="M22 28c0-8 4-13 10-13s10 5 10 13" />
            <path d="M22 28c-2 7-1 13 2 16M42 28c2 7 1 13-2 16" />
            @break
        @case(3) {{-- bun --}}
            <circle cx="32" cy="14" r="4.5" fill="currentColor" />
            <path d="M22 25c1-6 5-9 10-9s9 3 10 9" />
            @break
        @case(4) {{-- curly hair --}}
            <circle cx="24" cy="19" r="4" /><circle cx="32" cy="16" r="4" /><circle cx="40" cy="19" r="4" />
            <circle cx="21" cy="26" r="3" /><circle cx="43" cy="26" r="3" />
            @break
        @case(5) {{-- cap --}}
            <path d="M22 24c0-7 4-11 10-11s10 4 10 11z" fill="currentColor" />
            <path d="M20 24h26" />
            @break
        @case(6) {{-- glasses --}}
            <path d="M22 24c1-6 5-8 10-8s9 2 10 8" />
            <circle cx="28" cy="28" r="3.6" /><circle cx="36" cy="28" r="3.6" /><path d="M31.6 28h.8" />
            @break
        @case(7) {{-- beard --}}
            <path d="M22 26c0-7 4-10 10-10s10 3 10 10" />
            <path d="M22 30c0 8 4 12 10 12s10-4 10-12c-2 3-5 4-10 4s-8-1-10-4z" fill="currentColor" />
            @break
        @case(8) {{-- headscarf --}}
            <path d="M21 29c-1-9 3-15 11-15s12 6 11 15c-2-5-6-8-11-8s-9 3-11 8z" fill="currentColor" />
            <path d="M21 29c-1 6 0 11 3 14M43 29c1 6 0 11-3 14" />
            @break
    @endswitch
</svg>
