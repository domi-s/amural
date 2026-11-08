<x-layouts.public :title="__('Topics')">
    <header class="border-b border-marker pb-8">
        <h1 class="{{ \App\Support\DisplayFont::classes(__('Topics')) }} text-5xl leading-none sm:text-6xl">{{ __('Topics') }}</h1>

        <dl class="mt-8 flex flex-wrap gap-x-12 gap-y-4">
            <div>
                <dt class="text-xs uppercase tracking-wide opacity-70">{{ __('Topics') }}</dt>
                <dd class="text-3xl font-semibold">{{ number_format($index->topicCount) }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide opacity-70">{{ __('Reviewed') }}</dt>
                <dd class="text-3xl font-semibold">{{ $index->reviewedPercent }}%</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide opacity-70">{{ __('Lessons') }}</dt>
                <dd class="text-3xl font-semibold">{{ number_format($index->lessonCount) }}</dd>
            </div>
        </dl>
    </header>

    @if ($index->cards === [])
        <p class="mt-10 opacity-70">{{ __('No topics yet.') }}</p>
    @else
        <ul class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($index->cards as $card)
                @php($topic = $card->topic)
                <li>
                    <article class="flex h-full flex-col border border-marker">
                        <div class="flex items-start gap-4 p-5">
                            <x-subject-icon :subject="$topic->concept->subject->icon()" size="size-10" />

                            <h2 class="min-w-0 text-2xl leading-tight">
                                <a
                                    href="{{ $topic->url }}"
                                    @if ($topic->langAttribute()) lang="{{ $topic->langAttribute() }}" @endif
                                    class="{{ \App\Support\DisplayFont::classes($topic->name) }} hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marker"
                                >{{ $topic->name }}</a>
                            </h2>
                        </div>

                        @if ($card->children !== [])
                            <details class="group mt-auto border-t border-marker">
                                <summary class="flex list-none items-center justify-between gap-3 px-5 py-3 text-sm hover:bg-marker hover:text-paper focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-marker [&::-webkit-details-marker]:hidden">
                                    <span class="inline-flex items-center gap-2">
                                        <x-icon name="plus" size="size-4" class="transition-transform group-open:rotate-45" />
                                        {{ __('Inside this') }}
                                    </span>
                                    <span class="opacity-70">{{ count($card->children) }}</span>
                                </summary>

                                <ul class="flex flex-col gap-1.5 border-t border-marker px-5 py-4 text-sm">
                                    @foreach ($card->children as $child)
                                        <li>
                                            <a
                                                href="{{ $child->url }}"
                                                @if ($child->langAttribute()) lang="{{ $child->langAttribute() }}" @endif
                                                class="underline decoration-1 underline-offset-4 hover:bg-marker hover:text-paper focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marker"
                                            >{{ $child->name }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    </article>
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.public>
