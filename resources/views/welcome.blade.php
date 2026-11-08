<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-dvh bg-paper text-marker font-sans antialiased">
        <main class="flex min-h-dvh flex-col items-center justify-center px-4 text-center">
            <x-logo />
            <h1 class="mt-2 font-display text-5xl leading-none italic">{{ config('app.domain') }}</h1>
            <p class="mt-6 max-w-xl text-sm font-medium leading-relaxed">{{ __('Teachers share, improve and remix lessons in every European language, and credit follows the work.') }}</p>
        </main>
        <footer class="absolute bottom-0 left-1/2 -translate-x-1/2 w-full text-center p-4">
            <p class="font-medium text-sm leading-relaxed">{{ __('An Aspire Education Product. Coming soon :-)') }}</p>
        </footer>
    </body>
</html>
