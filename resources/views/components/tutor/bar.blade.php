@props(['headline', 'modes'])

{{--
    The tutor bar: ask for a lesson, ask for exercises, or ask to be guided to an answer. It is a tutor, not a
    question-answering chatbot, so the bar makes you pick which job you want. Nothing is connected yet: sending shows
    a "coming soon" note. The modes, hints and examples come from App\Support\TutorBar.
--}}
@php
    $first = $modes[0];
    $config = [
        'mode' => $first->key,
        'placeholders' => collect($modes)->mapWithKeys(fn ($m) => [$m->key => $m->placeholder])->all(),
        'hints' => collect($modes)->mapWithKeys(fn ($m) => [$m->key => $m->hint])->all(),
        'examples' => collect($modes)->mapWithKeys(fn ($m) => [$m->key => $m->examples])->all(),
    ];
@endphp

<section class="mx-auto my-16 max-w-3xl sm:my-20" aria-labelledby="tutor-heading">
    <h2 id="tutor-heading" class="{{ \App\Support\DisplayFont::classes($headline) }} text-balance text-center text-[34px] leading-[1.05] sm:text-5xl">{{ $headline }}</h2>

    <form
        class="tutor relative mt-24"
        x-data="{ ...@js($config), text: '', soon: false }"
        x-on:submit.prevent="if (text.trim() !== '') soon = true"
        novalidate
    >
        <x-tutor.peek class="absolute bottom-full right-24 hidden sm:block" />

        <div class="rounded-hand bg-tint p-4 sm:p-5">
            <label for="tutor-input" class="sr-only">{{ __('Ask the tutor') }}</label>
            <textarea
                id="tutor-input"
                x-ref="input"
                x-model="text"
                x-bind:placeholder="placeholders[mode]"
                x-on:input="soon = false"
                x-on:keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); $el.form.requestSubmit(); }"
                rows="3"
                placeholder="{{ $first->placeholder }}"
                class="block w-full resize-none bg-transparent px-2 py-2 text-[19px] leading-relaxed text-marker placeholder:text-muted focus:outline-none"
            ></textarea>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-x-4 gap-y-3">
                <fieldset class="flex flex-wrap gap-2">
                    <legend class="sr-only">{{ __('How can we help?') }}</legend>

                    @foreach ($modes as $mode)
                        <div>
                            <input
                                type="radio"
                                name="mode"
                                id="tutor-mode-{{ $mode->key }}"
                                value="{{ $mode->key }}"
                                x-model="mode"
                                class="peer sr-only"
                                @checked($loop->first)
                            >
                            <label
                                for="tutor-mode-{{ $mode->key }}"
                                class="inline-flex h-11 items-center rounded-hand-sm px-4 text-[15px] font-[450] hover:bg-tint-2 peer-checked:bg-marker peer-checked:text-paper peer-checked:hover:bg-marker peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-marker"
                            >{{ $mode->label }}</label>
                        </div>
                    @endforeach
                </fieldset>

                <button
                    type="submit"
                    x-bind:disabled="text.trim() === ''"
                    class="inline-flex size-12 items-center justify-center rounded-hand-sm bg-marker text-paper hover:bg-marker/85 disabled:cursor-not-allowed disabled:opacity-30"
                    aria-label="{{ __('Send') }}"
                    disabled
                >
                    <x-icon name="arrow" size="size-6" />
                </button>
            </div>
        </div>

        <div class="mt-5 px-2" aria-live="polite">
            <p x-show="! soon" class="text-muted" x-text="hints[mode]">{{ $first->hint }}</p>
            <p x-show="soon" x-cloak role="status" class="font-medium">{{ __("The tutor isn't open yet, but this is where you'll ask. Meanwhile, browse the topics below.") }}</p>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2 px-2">
            <span class="kicker text-muted">{{ __('Try') }}</span>

            <template x-for="example in examples[mode]" :key="example">
                <button
                    type="button"
                    x-text="example"
                    x-on:click="text = example; soon = false; $refs.input.focus()"
                    class="min-h-11 rounded-hand-sm px-3 text-left text-sm hover:bg-tint"
                ></button>
            </template>
        </div>
        {{-- What the tutor does and does not do, in a line each. --}}
        <ul class="mt-10 flex flex-col gap-3 px-2 text-[15px]">
            @foreach ([
                [true, __("Ask questions, get answers and exercise what you've learned")],
                [true, __('Follow all exercises and animations for the most effective learning')],
                [false, __("Doesn't solve it for you")],
            ] as [$yes, $line])
                <li class="flex items-start gap-3">
                    <x-icon :name="$yes ? 'check' : 'close'" size="size-5" class="mt-0.5" :label="$yes ? __('Yes') : __('No')" />
                    <span>{{ $line }}</span>
                </li>
            @endforeach
        </ul>
    </form>
</section>
