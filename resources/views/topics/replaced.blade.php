<x-layouts.public :title="__('This topic has been replaced')">
    <article class="max-w-prose">
        <h1 class="{{ \App\Support\DisplayFont::classes(__('This topic has been replaced')) }} text-4xl leading-tight sm:text-5xl">{{ __('This topic has been replaced') }}</h1>

        <p class="mt-6">{{ __('It was replaced by:') }}</p>

        <ul class="mt-3 flex flex-col gap-2">
            @foreach ($links as $link)
                <li>
                    <a href="{{ $link->url }}" @if ($link->langAttribute()) lang="{{ $link->langAttribute() }}" @endif class="text-lg underline underline-offset-4 hover:bg-marker hover:text-paper">{{ $link->name }}</a>
                </li>
            @endforeach
        </ul>
    </article>
</x-layouts.public>
