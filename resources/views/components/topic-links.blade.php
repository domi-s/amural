@props(['heading', 'links'])

{{-- One of the structure lists on a topic page. Proposed links (not yet reviewed) are dashed. --}}
@if ($links !== [])
    <section {{ $attributes }}>
        <h2 class="border-b border-marker pb-1 text-xs font-semibold uppercase tracking-wide">{{ $heading }}</h2>

        <ul class="mt-3 flex flex-col gap-1.5">
            @foreach ($links as $link)
                <li>
                    <a
                        href="{{ $link->url }}"
                        @if ($link->langAttribute()) lang="{{ $link->langAttribute() }}" @endif
                        class="{{ $link->proposed ? 'border-b border-dashed border-marker' : 'underline decoration-1 underline-offset-4' }} hover:bg-marker hover:text-paper focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marker"
                    >{{ $link->name }}</a>
                    @if ($link->strength === \App\Enums\EdgeStrength::Helpful)
                        <span class="text-xs opacity-70">{{ __('helpful') }}</span>
                    @endif
                    @if ($link->proposed)
                        <span class="text-xs opacity-70">{{ __('proposed') }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>
@endif
