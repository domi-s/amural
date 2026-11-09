@props(['stops'])

{{--
    The path line: a gently wobbling line across the strip, a small dot at each stop, a big one for this topic,
    and an arrowhead at the end. The line and the dots share one function, so the dots sit on the line.
    The strip is 230px tall and the line's drawing is 1000 units wide, stretched to the strip.
--}}
@php
    $y = fn (float $x): float => 115 + 7 * sin($x / 1000 * 2 * M_PI * 1.5 + 0.6);

    $points = [];
    for ($x = 0; $x <= 976; $x += 16) {
        $points[] = ($x === 0 ? 'M' : 'L').$x.' '.round($y($x), 1);
    }
@endphp

<div {{ $attributes->class(['pointer-events-none absolute inset-0']) }} aria-hidden="true">
    <svg class="stroke-fixed absolute inset-0 size-full" viewBox="0 0 1000 230" preserveAspectRatio="none" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" focusable="false">
        <path d="{{ implode(' ', $points) }}" />
    </svg>

    <svg class="stroke-fixed absolute right-0 h-6 w-6" style="top: calc({{ round($y(988) / 230 * 100, 2) }}% - 12px)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" focusable="false">
        <path d="M3 4c6 2 12 5 18 8M21 12c-6 2-12 5-17 8" />
    </svg>

    @foreach ($stops as $stop)
        @php($here = $stop->kind === \App\Support\PathStop::HERE)
        <span
            class="absolute -translate-x-1/2 -translate-y-1/2 rounded-full bg-marker {{ $here ? 'size-[30px]' : 'size-3' }}"
            style="left: {{ $stop->x }}%; top: {{ round($y($stop->x * 10) / 230 * 100, 2) }}%"
        ></span>
    @endforeach
</div>
