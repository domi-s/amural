@props(['page'])

{{-- Sources and credits, collapsed. Shows the attribution statements a source's licence requires. --}}
@if ($page->sources !== [])
    <details class="group mt-14 border-t border-marker pt-3">
        <summary class="inline-flex list-none items-center gap-2 text-xs font-semibold uppercase tracking-wide focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-marker [&::-webkit-details-marker]:hidden">
            <x-icon name="plus" size="size-4" class="transition-transform group-open:rotate-45" />
            {{ __('Sources and credits') }}
        </summary>

        <ul class="mt-4 flex flex-col gap-5 text-sm">
            @foreach ($page->sources as $source)
                <li class="max-w-prose">
                    <p class="font-medium">{{ $source->name }}</p>
                    <p class="mt-1">{{ $source->attribution }}</p>
                    <p class="mt-1 opacity-70">
                        {{ __('Licence') }}: {{ $source->licence }}
                        @if ($source->url && preg_match('#^https?://#i', $source->url) === 1)
                            · <a href="{{ $source->url }}" rel="nofollow noopener noreferrer" class="underline underline-offset-4">{{ parse_url($source->url, PHP_URL_HOST) }}</a>
                        @endif
                    </p>
                </li>
            @endforeach
        </ul>
    </details>
@endif
