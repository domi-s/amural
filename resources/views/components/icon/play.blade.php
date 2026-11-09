{{-- A sketched circle with a filled triangle: the play mark on a video. 80px by default. --}}
<svg
    {{ $attributes->class(['stroke-fixed size-20 shrink-0'])->merge(['viewBox' => '0 0 80 80']) }}
    fill="none"
    stroke="currentColor"
    stroke-width="2.5"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    focusable="false"
>
    <path d="M41 6C60 5 75 20 74 40c-1 19-15 34-35 34C21 74 6 59 6 40 6 22 20 8 38 7" />
    <path d="M32 26c9 5 17 9 25 14-8 5-16 9-25 14-1-9-1-19 0-28z" fill="currentColor" />
</svg>
