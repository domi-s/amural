<?php

use App\Models\Country;
use App\Models\Locale;
use App\Support\FlagSprite;
use App\Support\SvgLibrary;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LocaleSeeder;
use Illuminate\Support\Facades\Blade;

const SPEC_ICON_NAMES = [
    'pen', 'plus', 'document', 'user', 'users', 'search', 'translate', 'comment', 'heart', 'star',
    'bookmark', 'globe', 'upload', 'download', 'check', 'close', 'home', 'bell', 'settings', 'filters',
    'play', 'image', 'trash', 'lock', 'arrow', 'history', 'compare', 'revert',
];

// Interface icons

it('has every icon the spec names', function () {
    expect(app(SvgLibrary::class)->names('icons'))->toContain(...SPEC_ICON_NAMES);
});

it('renders an icon as decorative by default', function () {
    $html = Blade::render('<x-icon name="translate" />');

    expect($html)->toContain('<svg')
        ->toContain('aria-hidden="true"')
        ->toContain('viewBox="0 0 100 100"')
        ->not->toContain('role="img"');
});

it('gives a meaningful icon a name', function () {
    $html = Blade::render('<x-icon name="search" label="Search topics" />');

    expect($html)->toContain('role="img"')->toContain('aria-label="Search topics"')->not->toContain('aria-hidden');
});

it('lets the caller size and style an icon', function () {
    $html = Blade::render('<x-icon name="pen" size="size-8" class="text-red-700" />');

    expect($html)->toContain('size-8')->toContain('text-red-700');
});

it('draws icons in the current text colour so they can be themed', function () {
    foreach (SPEC_ICON_NAMES as $name) {
        $svg = app(SvgLibrary::class)->icon($name);

        expect($svg)->toContain('currentColor')->not->toContain('#141414');
    }
});

it('refuses an icon that does not exist or a name that reaches outside the folder', function () {
    expect(fn () => Blade::render('<x-icon name="nope" />'))->toThrow(Exception::class);
    expect(fn () => app(SvgLibrary::class)->icon('../flags/RO'))->toThrow(InvalidArgumentException::class);
    expect(fn () => app(SvgLibrary::class)->icon('pen.svg'))->toThrow(InvalidArgumentException::class);
});

// Subject icons

it('has an icon for each of the 22 subjects, including maths', function () {
    $names = app(SvgLibrary::class)->names('subjects');

    expect($names)->toHaveCount(22)->toContain('maths', 'physics', 'languages', 'ethics-religion', 'physical-education');
});

it('renders a subject icon', function () {
    $html = Blade::render('<x-subject-icon subject="maths" label="Maths" />');

    expect($html)->toContain('<svg')->toContain('aria-label="Maths"')->toContain('size-8');
});

// Flags

it('renders a flag from the sprite with an accessible name', function () {
    $this->seed(CountrySeeder::class);

    $html = Blade::render('<x-flag country="RO" />');

    expect($html)->toContain('role="img"')
        ->toContain('aria-label="Romania flag"')
        ->toContain('svg/flags.svg#flag-RO')
        ->toContain('viewBox="0 0 120 84"');
});

it('always renders an accessible name for every flag', function () {
    $this->seed(CountrySeeder::class);

    foreach (Country::pluck('code') as $code) {
        $html = Blade::render('<x-flag :country="$code" />', ['code' => $code]);

        expect($html)->toMatch('/aria-label="[^"]+"/');
        expect($html)->not->toContain('aria-label=""');
    }
});

it('names a flag even when the country is not in the database, and accepts a lowercase code', function () {
    expect(Blade::render('<x-flag country="ro" />'))
        ->toContain('aria-label="RO flag"')
        ->toContain('#flag-RO');
});

it('lets the caller give a flag its own name', function () {
    expect(Blade::render('<x-flag country="GB" label="English" />'))->toContain('aria-label="English"');
});

it('sizes flags', function () {
    expect(Blade::render('<x-flag country="RO" size="sm" />'))->toContain('h-4');
    expect(Blade::render('<x-flag country="RO" />'))->toContain('h-5');
    expect(Blade::render('<x-flag country="RO" size="lg" />'))->toContain('h-8');
});

it('translates the flag name', function () {
    $this->seed(CountrySeeder::class);
    app()->setLocale('ro');

    expect(Blade::render('<x-flag country="RO" />'))->toContain('aria-label="Drapelul Romania"');
});

it('has a flag for every country and every language flag country', function () {
    $this->seed([LocaleSeeder::class, CountrySeeder::class]);
    $library = app(SvgLibrary::class);

    foreach (Country::pluck('code') as $code) {
        expect($library->hasFlag($code))->toBeTrue();
    }

    foreach (Locale::pluck('flag_country')->unique() as $code) {
        expect($library->hasFlag($code))->toBeTrue();
    }
});

it('draws every flag in the same shape, with nothing that would fix its size', function () {
    foreach (app(SvgLibrary::class)->names('flags') as $code) {
        $svg = app(SvgLibrary::class)->flag($code);

        expect($svg)->toStartWith('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 84">');
        expect($svg)->not->toMatch('/<svg[^>]*\s(width|height|role|aria-label)=/');
    }
});

it('keeps the flag sprite in step with the flags', function () {
    expect(app(FlagSprite::class)->isUpToDate())->toBeTrue();
});

it('puts every flag in the sprite once, as a symbol with the shared shape', function () {
    $sprite = file_get_contents(public_path('svg/flags.svg'));

    preg_match_all('/<symbol id="flag-([A-Z]{2})" viewBox="0 0 120 84">/', $sprite, $matches);

    expect($matches[1])->toHaveCount(31);
    expect(array_unique($matches[1]))->toHaveCount(31);
    expect($matches[1])->toEqual(app(SvgLibrary::class)->names('flags'));
});

// The map

it('has a map with every served country, with its languages for the language picker', function () {
    $this->seed(CountrySeeder::class);
    $map = file_get_contents(public_path('svg/europe-map.svg'));

    preg_match_all('/<path id="([A-Z]{2})" class="country" data-name="[^"]+" data-local="[^"]+" data-langs="([^"]+)"/', $map, $m);

    expect($m[1])->toHaveCount(32); // 31 seeded countries plus Switzerland, a candidate
    expect(array_diff(Country::pluck('code')->all(), $m[1]))->toBe([]);
    expect($m[2])->each->not->toBe('');
});

it('never loses the size of a rectangle, which would silently empty a clip or a shape', function () {
    $library = app(SvgLibrary::class);

    foreach (['flags', 'icons', 'subjects'] as $group) {
        foreach ($library->names($group) as $name) {
            $svg = $group === 'flags' ? $library->flag($name) : ($group === 'icons' ? $library->icon($name) : $library->subject($name));

            preg_match_all('/<rect\b[^>]*>/', $svg, $rects);

            foreach ($rects[0] as $rect) {
                expect($rect)->toContain('width=')->toContain('height=');
            }
        }
    }
});

it('draws the English flag with the clip that holds its crosses', function () {
    $svg = app(SvgLibrary::class)->flag('GB');

    expect($svg)->toMatch('/<clipPath id="[^"]+"><rect [^>]*width="108" height="72"/');
});
