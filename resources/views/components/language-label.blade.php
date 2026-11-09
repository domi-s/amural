@props(['locale', 'size' => 'sm', 'english' => true])

{{--
    A language as the spec wants it shown: its flag, its name in its own language (capitalised, with a
    lang attribute), and the English name beside it where there is room. A flag never appears alone.
--}}
<span {{ $attributes->class(['inline-flex items-center gap-2']) }}>
    <x-flag :country="$locale->flag_country" :size="$size" :label="__(':language flag', ['language' => $locale->english_name])" />
    <span lang="{{ $locale->language }}" class="font-medium">{{ $locale->display_name }}</span>
    @if ($english && $locale->english_name !== $locale->native_name)
        <span class="text-xs opacity-70">{{ $locale->english_name }}</span>
    @endif
</span>
