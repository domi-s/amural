@props(['title' => null, 'issue' => null])

{{-- The topic page's layout: Paper, Marker, no rules. One header line, then the page. Other public pages keep layouts.public. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <x-font-preload />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="magazine min-h-dvh bg-paper font-sans text-marker antialiased">
        <a href="#content" class="sr-only focus:not-sr-only focus:absolute focus:left-2 focus:top-2 focus:z-50 focus:bg-paper focus:px-3 focus:py-2">{{ __('Skip to content') }}</a>

        <header class="mx-auto flex max-w-[1240px] items-center justify-between gap-6 px-5 pb-2 pt-6 sm:px-10">
            <div class="flex items-center gap-8">
                <x-header-wordmark />
                <a href="{{ route('topics.index') }}" class="text-sm font-medium underline-offset-4 hover:underline">{{ __('Topics') }}</a>
                <a href="" class="text-sm font-medium underline-offset-4 hover:underline">{{ __('Teachers') }}</a>
                <a href="" class="text-sm font-medium underline-offset-4 hover:underline">{{ __('Students') }}</a>
                <a href="" class="text-sm font-medium underline-offset-4 hover:underline">{{ __('Parents') }}</a>
                <a href="" class="text-sm font-medium underline-offset-4 hover:underline">{{ __('Schools') }}</a>
            </div>

            <div class="flex items-center gap-6 text-sm">
                @if ($issue)
                    <p class="hidden text-muted mag:block">
                        {{ __('Issue') }}:
                        <a href="{{ $issue->url }}" @if ($issue->langAttribute()) lang="{{ $issue->langAttribute() }}" @endif class="font-medium text-marker underline-offset-4 hover:underline">{{ $issue->name }}</a>
                    </p>
                @endif

                @auth
                    <a href="{{ route('profile') }}" class="flex items-center" aria-label="{{ __('Profile') }}">
                        <x-avatar :preset="auth()->user()->avatar_preset" size="size-9" :label="auth()->user()->name" />
                    </a>
                @else
                    <a href="{{ route('login') }}" class="font-medium underline-offset-4 hover:underline">{{ __('Log in') }}</a>
                @endauth
            </div>
        </header>

        <main id="content">
            {{ $slot }}
        </main>

        @livewireScripts
    </body>
</html>
