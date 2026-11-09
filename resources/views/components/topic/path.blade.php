@props(['stops', 'neighbours'])

{{--
    The concept graph, reduced: one hand-drawn line with this topic on it, and one sentence about its neighbours.
    The strip is an ordered list, so a screen reader hears the same path. Under 900px it is a plain vertical list.
--}}
@php
    $kicker = [
        \App\Support\PathStop::FIRST => __('First'),
        \App\Support\PathStop::HERE => __('You are here'),
        \App\Support\PathStop::THEN => __('Then'),
    ];
@endphp

@if ($stops !== [] || $neighbours !== [])
    <section class="mt-14 mag:mt-[72px]" aria-label="{{ __('Where this topic sits') }}">
        @if ($stops !== [])
            <div class="relative text-marker mag:h-[230px]">
                <div class="hidden mag:block"><x-mark.path :stops="$stops" /></div>

                {{-- A short hand-drawn line down the left side of the vertical list. --}}
                <svg class="absolute bottom-3 left-[7px] top-3 w-2 mag:hidden" viewBox="0 0 8 100" preserveAspectRatio="none" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true" focusable="false">
                    <path d="M4 0c-2 12 3 20 0 33s-3 22 0 34 2 20 0 33" />
                </svg>

                <ol class="relative flex flex-col gap-6 pl-8 mag:static mag:block mag:pl-0">
                    @foreach ($stops as $index => $stop)
                        <li
                            class="mag:absolute mag:left-(--x) mag:w-[150px] mag:-translate-x-1/2 mag:text-center {{ $index % 2 === 0 ? 'mag:bottom-[calc(50%+26px)]' : 'mag:top-[calc(50%+26px)]' }}"
                            style="--x: {{ $stop->x }}%"
                            @if ($stop->kind === \App\Support\PathStop::HERE) aria-current="location" @endif
                        >
                            <p class="kicker text-muted">{{ $kicker[$stop->kind] }}</p>

                            @if ($stop->url)
                                <a href="{{ $stop->url }}" @if ($stop->lang) lang="{{ $stop->lang }}" @endif class="text-[19px] leading-snug underline-offset-4 hover:underline">{{ $stop->name }}</a>
                            @else
                                <p @if ($stop->lang) lang="{{ $stop->lang }}" @endif class="text-[19px] font-semibold leading-snug">{{ $stop->name }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        @if ($neighbours !== [])
            <p class="mt-6 text-muted">
                {{ __('Next door') }}:
                @foreach ($neighbours as $link)
                    <a href="{{ $link->url }}" @if ($link->langAttribute()) lang="{{ $link->langAttribute() }}" @endif class="text-marker underline underline-offset-4">{{ $link->name }}</a>@if ($loop->remaining > 1), @elseif ($loop->remaining === 1) {{ __('and') }} @else. @endif
                @endforeach
            </p>
        @endif
    </section>
@endif
