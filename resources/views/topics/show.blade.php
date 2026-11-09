<x-layouts.topic :title="$page->name()" :issue="$page->issue">
    <article class="mx-auto max-w-[1240px] px-5 pb-16 pt-10 sm:px-10 mag:pt-14">
        <div class="grid gap-x-14 mag:grid-cols-[180px_1fr]">
            <div class="mag:col-start-2 mag:row-start-1">
                <x-topic.title :page="$page" />
            </div>

            {{-- Under the title on a phone; in the left margin from 901px. --}}
            <div class="mt-10 mag:col-start-1 mag:row-span-2 mag:row-start-1 mag:mt-3 mag:self-start">
                <x-topic.margin :page="$page" />
            </div>

            <div class="mag:col-start-2 mag:row-start-2">
                <x-topic.path :stops="$page->path" :neighbours="$page->neighbours" />
                <x-topic.lessons :page="$page" />
            </div>
        </div>

        <footer class="mt-24">
            @php($tagline = __('Lessons, shared across Europe.'))
            <div class="flex flex-wrap items-center  justify-between gap-x-10 gap-y-3">
                <p class="{{ \App\Support\DisplayFont::classes($tagline) }} text-2xl">{{ $tagline }}</p>

                <p class="text-[13px] text-muted">{{ __('Edited by teachers') }}</p>
            </div>
        </footer>
    </article>
</x-layouts.topic>
