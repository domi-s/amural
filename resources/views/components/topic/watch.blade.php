@props(['page'])

{{-- "To watch": one featured video, one small one, and the rest as a sentence of links. --}}
@php
    $videos = $page->videos;
    $add = Route::has('artifacts.create') ? route('artifacts.create', ['attach' => 'concept:'.$page->concept->public_id]) : null;
    $heading = __('To watch');
@endphp

@if ($page->artifactTotal > 0 || $add)
    <section class="mt-16 mag:mt-[88px]" aria-labelledby="watch-heading">
        @if ($page->artifactTotal === 0)
            <p class="text-muted">
                {{ __('Got a video or worksheet for this?') }}
                <a href="{{ $add }}" class="font-medium text-marker underline underline-offset-4">{{ __('Add it') }}</a>
            </p>
        @else
            <h2 id="watch-heading" class="{{ \App\Support\DisplayFont::classes($heading) }} text-[40px] leading-none mag:text-[56px]">{{ $heading }}</h2>

            <div class="mt-8 grid items-end gap-x-14 gap-y-10 {{ $videos->featured ? 'mag:grid-cols-[2fr_1fr]' : '' }}">
                @if ($videos->featured)
                    <x-topic.video :entry="$videos->featured" large />
                @endif

                <div class="flex flex-col gap-8">
                    @if ($videos->second)
                        <x-topic.video :entry="$videos->second" />
                    @endif

                    @if ($videos->others !== [] || $add)
                        <p class="text-muted">
                            @if ($videos->others !== [])
                                {{ __('Also') }}:
                                @foreach ($videos->others as $entry)
                                    @php($url = $entry->safeUrl())
                                    @if ($url)
                                        <a href="{{ $url }}" rel="ugc nofollow noopener noreferrer" @if ($entry->locale) lang="{{ $entry->locale->language }}" @endif class="text-marker underline underline-offset-4">{{ $entry->artifact->title }}</a>@else<span @if ($entry->locale) lang="{{ $entry->locale->language }}" @endif class="text-marker">{{ $entry->artifact->title }}</span>@endif@if ($loop->remaining > 1), @elseif ($loop->remaining === 1) {{ __('and') }} @else. @endif
                                @endforeach
                            @endif
                            @if ($add)
                                <a href="{{ $add }}" class="underline underline-offset-4">{{ __('Add yours') }}</a>
                            @endif
                        </p>
                    @endif
                </div>
            </div>
        @endif
    </section>
@endif
