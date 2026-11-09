@props(['page'])

{{-- "In this issue": the lessons, as a numbered list; the visitor's language first. --}}
@php
    $stats = $page->stats;
    $visible = \App\Support\TopicPageData::VISIBLE_LESSONS;
    $first = array_slice($page->lessons, 0, $visible);
    $rest = array_slice($page->lessons, $visible);
    $heading = __('In this issue');
    $create = Route::has('lessons.create') ? route('lessons.create', ['topic' => $page->concept->public_id]) : null;
@endphp

<section class="mt-16 grid gap-x-14 gap-y-8 mag:mt-[88px] mag:grid-cols-[1fr_1.4fr]" aria-labelledby="issue-heading">
    <div>
        <h2 id="issue-heading" class="{{ \App\Support\DisplayFont::classes($heading) }} text-[40px] leading-none mag:text-[56px]">{{ $heading }}</h2>

        @if ($stats->lessons > 0)
            <p class="mt-6 max-w-sm text-[17px] leading-relaxed">{{ __(':lessons, by :teachers, in :languages. Take one, change it, make it yours.', [
                'lessons' => trans_choice('{1} :count lesson|[2,*] :count lessons', $stats->lessons),
                'teachers' => trans_choice('{1} :count teacher|[2,*] :count teachers', $stats->teachers),
                'languages' => trans_choice('{1} :count language|[2,*] :count languages', $stats->languages),
            ]) }}</p>
        @endif

        @if ($create)
            <a href="{{ $create }}" class="mt-8 inline-flex h-12 items-center rounded-hand-sm bg-marker px-7 font-medium text-paper hover:bg-marker/85">{{ __('Write the next one') }}</a>
        @endif
    </div>

    <div>
        @if ($page->lessons === [])
            <p class="{{ \App\Support\DisplayFont::classes(__('Nobody has written this one yet.')) }} text-[32px] leading-tight">{{ __('Nobody has written this one yet.') }}</p>
        @else
            <ol class="flex flex-col gap-7">
                @foreach ($first as $index => $lesson)
                    <x-topic.lesson :lesson="$lesson" :number="$index + 1" />
                @endforeach
            </ol>

            @if ($rest !== [])
                <details class="mt-7">
                    <summary class="inline-flex min-h-11 list-none items-center font-medium underline underline-offset-4 [&::-webkit-details-marker]:hidden">{{ __('All :count lessons', ['count' => count($page->lessons)]) }}</summary>

                    <ol class="mt-5 flex flex-col gap-7">
                        @foreach ($rest as $index => $lesson)
                            <x-topic.lesson :lesson="$lesson" :number="$visible + $index + 1" />
                        @endforeach
                    </ol>
                </details>
            @endif
        @endif
    </div>
</section>
