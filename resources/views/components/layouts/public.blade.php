@props(['title' => null])

{{-- The light, public layout: Paper background, Marker text and thin rules. Anyone can read these pages. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css'])
        @livewireStyles
    </head>
    <body class="min-h-dvh bg-paper font-sans text-marker antialiased">
        <a href="#content" class="sr-only focus:not-sr-only focus:absolute focus:left-2 focus:top-2 focus:z-50 focus:bg-paper focus:px-3 focus:py-2 focus:outline-2 focus:outline-marker">{{ __('Skip to content') }}</a>

        <header class="border-b border-marker">
            <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-x-6 gap-y-3 px-4 py-4 sm:px-6">
                <div class="flex items-baseline gap-8">
                    <a href="{{ route('home') }}" class="font-display text-3xl italic leading-none focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-marker">{{ config('app.name') }}</a>

                    <nav aria-label="{{ __('Main') }}">
                        <a href="{{ route('topics.index') }}" class="text-sm font-medium underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-marker">{{ __('Topics') }}</a>
                    </nav>
                </div>

                <div class="flex items-center gap-4 text-sm">
                    <livewire:language-switcher />

                    @auth
                        <a href="{{ route('profile') }}" class="font-medium underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-marker">{{ __('Profile') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="font-medium underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-marker">{{ __('Log in') }}</a>
                        <a href="{{ route('register') }}" class="border border-marker px-3 py-1.5 font-medium hover:bg-marker hover:text-paper focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-marker">{{ __('Register') }}</a>
                    @endauth
                </div>
            </div>
        </header>

        <main id="content" class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
            {{ $slot }}
        </main>

        <footer class="mt-16 border-t border-marker">
            <div class="mx-auto max-w-5xl px-4 py-6 text-sm sm:px-6">
                <p class="{{ \App\Support\DisplayFont::classes(__('Lessons, shared across Europe.')) }}">{{ __('Lessons, shared across Europe.') }}</p>
            </div>
        </footer>

        @livewireScripts
    </body>
</html>
