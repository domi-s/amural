@props(['country', 'size' => 'sm', 'english' => true])

{{-- A country: its flag, its name in its own language, and the English name beside it. Countries say where a person is, never a language. --}}
<span {{ $attributes->class(['inline-flex items-center gap-2']) }}>
    <x-flag :country="$country->code" :size="$size" />
    <span class="font-medium">{{ $country->native_name }}</span>
    @if ($english && $country->english_name !== $country->native_name)
        <span class="text-xs opacity-70">{{ $country->english_name }}</span>
    @endif
</span>
