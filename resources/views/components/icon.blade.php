@props(['name', 'size' => 'size-5', 'label' => null])

{{-- A hand-drawn interface icon from resources/svg/icons. Decorative unless given a label. --}}
@php
    $svg = app(\App\Support\SvgLibrary::class)->icon($name);

    $attributes = $attributes->class([$size, 'shrink-0'])->merge(
        $label !== null
            ? ['role' => 'img', 'aria-label' => $label]
            : ['aria-hidden' => 'true', 'focusable' => 'false']
    );
@endphp
{!! \Illuminate\Support\Str::replaceFirst('<svg ', '<svg '.$attributes->toHtml().' ', $svg) !!}
