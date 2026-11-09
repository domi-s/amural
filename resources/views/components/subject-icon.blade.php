@props(['subject', 'size' => 'size-8', 'label' => null])

{{-- A hand-drawn subject icon from resources/svg/subjects ("maths", "physics", ...). Decorative unless given a label. --}}
@php
    $svg = app(\App\Support\SvgLibrary::class)->subject($subject);

    $attributes = $attributes->class([$size, 'shrink-0'])->merge(
        $label !== null
            ? ['role' => 'img', 'aria-label' => $label]
            : ['aria-hidden' => 'true', 'focusable' => 'false']
    );
@endphp
{!! \Illuminate\Support\Str::replaceFirst('<svg ', '<svg '.$attributes->toHtml().' ', $svg) !!}
