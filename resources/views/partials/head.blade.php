<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml" />

<title>{{ $title ?? config('app.name') }}</title>

<x-font-preload />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
