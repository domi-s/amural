@props(['country', 'size' => 'md', 'label' => null])

{{--
    A hand-drawn flag, referenced from the cached sprite (public/svg/flags.svg). It always has an
    accessible name. A flag is never shown alone: pair it with the language or country name
    (see <x-language-label> and <x-country-label>).
--}}
@php
    $country = strtoupper($country);

    $height = match ($size) {
        'sm' => 'h-4',
        'lg' => 'h-8',
        default => 'h-5',
    };

    $name = $label ?? __(':country flag', ['country' => app(\App\Support\CountryNames::class)->english($country)]);
@endphp
<svg
    {{ $attributes->class([$height, 'aspect-[120/84] w-auto shrink-0'])->merge([
        'viewBox' => '0 0 120 84',
        'role' => 'img',
        'aria-label' => $name,
    ]) }}
>
    <use href="{{ asset(\App\Support\FlagSprite::PATH) }}#flag-{{ $country }}"></use>
</svg>
