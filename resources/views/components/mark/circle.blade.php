{{-- A loose marker ellipse drawn around a word. Place inside a `relative inline-block` wrapper. --}}
<svg
    {{ $attributes->class(['stroke-fixed pointer-events-none absolute left-[-0.14em] top-[-0.08em] h-[calc(100%+0.16em)] w-[calc(100%+0.28em)] overflow-visible']) }}
    viewBox="0 0 200 100"
    preserveAspectRatio="none"
    fill="none"
    stroke="currentColor"
    stroke-width="2.5"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    focusable="false"
>
    <path d="M34 20C70 3 150 3 184 30c20 22-4 56-62 64C66 101 8 88 5 56 3 30 40 12 96 8c28-2 58 2 76 10" />
</svg>
