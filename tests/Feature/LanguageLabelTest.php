<?php

use App\Models\Country;
use App\Models\Locale;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LocaleSeeder;
use Illuminate\Support\Facades\Blade;

beforeEach(function () {
    $this->seed([LocaleSeeder::class, CountrySeeder::class]);
});

it('shows a language as its flag, its own capitalised name and the English name', function () {
    $html = Blade::render('<x-language-label :locale="$locale" />', ['locale' => Locale::find('ro-RO')]);

    expect($html)
        ->toContain('#flag-RO')
        ->toContain('aria-label="Romanian flag"')
        ->toContain('<span lang="ro" class="font-medium">Română</span>')
        ->toContain('<span class="text-xs opacity-70">Romanian</span>');
});

it('uses the flag of the language, not of the country where it is mostly spoken', function () {
    $irish = Blade::render('<x-language-label :locale="$locale" />', ['locale' => Locale::find('ga-IE')]);
    $english = Blade::render('<x-language-label :locale="$locale" />', ['locale' => Locale::find('en-GB')]);

    expect($irish)->toContain('#flag-IE')->toContain('aria-label="Irish flag"');
    expect($english)->toContain('#flag-GB')->toContain('aria-label="English flag"');
});

it('never shows the English name twice', function () {
    $html = Blade::render('<x-language-label :locale="$locale" />', ['locale' => Locale::find('en-GB')]);

    expect($html)->toContain('lang="en"')->not->toContain('text-xs opacity-70');
});

it('can leave out the English name where there is no room', function () {
    $html = Blade::render('<x-language-label :locale="$locale" :english="false" />', ['locale' => Locale::find('ro-RO')]);

    expect($html)->toContain('Română')->not->toContain('Romanian</span>');
});

it('gives every language a flag with an accessible name', function () {
    foreach (Locale::all() as $locale) {
        $html = Blade::render('<x-language-label :locale="$locale" />', ['locale' => $locale]);

        expect($html)->toContain('role="img"')->toMatch('/aria-label="[^"]+ flag"/');
    }
});

it('shows a country as its flag, its own name and the English name', function () {
    $html = Blade::render('<x-country-label :country="$country" />', ['country' => Country::find('AT')]);

    expect($html)
        ->toContain('#flag-AT')
        ->toContain('aria-label="Austria flag"')
        ->toContain('Österreich')
        ->toContain('<span class="text-xs opacity-70">Austria</span>');
});
