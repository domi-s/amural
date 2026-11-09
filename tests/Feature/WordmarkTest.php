<?php

use Illuminate\Support\Facades\Blade;


it('renders the wordmark plain or with stars, in the display font and the text colour', function () {
    $plain = Blade::render('<x-wordmark />');
    $stars = Blade::render('<x-wordmark stars />');

    expect($plain)->toContain('role="img"')->toContain('aria-label="Amural"')->toContain('Amural</text>')->toContain("font-family=\"'Amural Display'")->not->toContain('#141414');
    expect($stars)->toContain('Amural</text>')->toContain('<path')->not->toContain('#141414');
    expect(strlen($stars))->toBeGreaterThan(strlen($plain));
});

it('renders the primary and stacked lockups with the cat, the name and the tagline', function () {
    $primary = Blade::render('<x-logo-lockup />');
    $stacked = Blade::render('<x-logo-lockup stacked />');

    expect($primary)->toContain('twelve hand-drawn stars')->toContain('Amural')->toContain('Lessons, shared across Europe.');
    expect($stacked)->toContain('flex-col')->toContain('Amural')->not->toContain('Lessons, shared across Europe.');
});

it('has a header wordmark whose seven stars become a ring of twelve around the cat', function () {
    $html = Blade::render('<x-header-wordmark />');

    expect(substr_count($html, 'class="hw-star"'))->toBe(7);
    expect(substr_count($html, 'class="hw-extra"'))->toBe(5);
    expect($html)->toContain('class="hw-draw"')->toContain('class="hw-pop"')
        ->toContain('aria-label="Amural"')
        ->toContain('Amural</text>')
        ->toContain('href="'.route('home').'"')
        ->not->toContain('#141414');

    $css = file_get_contents(resource_path('css/app.css'));
    expect($css)->toContain('.header-wordmark:is(:hover, :focus-visible)')->toContain('prefers-reduced-motion: reduce');
});
