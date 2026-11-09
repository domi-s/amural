<?php

use App\Actions\Locales\ReplaceContentLocales;
use App\Livewire\LanguageSwitcher;
use App\Models\Country;
use App\Models\Locale;
use App\Support\LanguageChoices;
use Database\Factories\UserFactory;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LocaleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([LocaleSeeder::class, CountrySeeder::class]);
});

/** @return array<string, list<string>> country id => language codes, read from the map file */
function mapCountries(): array
{
    $map = file_get_contents(public_path('svg/europe-map.svg'));

    preg_match_all('/<path id="([A-Z]{2})" class="country"[^>]*data-langs="([^"]+)"/', $map, $matches, PREG_SET_ORDER);

    $countries = [];
    foreach ($matches as $match) {
        $countries[$match[1]] = explode(' ', $match[2]);
    }

    return $countries;
}

// The language data the map uses

it('groups the 26 content languages by language code', function () {
    $groups = LanguageChoices::byLanguage(Locale::orderBy('sort')->get());

    expect($groups['ro'])->toHaveCount(1);
    expect($groups['ro'][0])->toMatchArray([
        'code' => 'ro-RO',
        'name' => 'Română',
        'english' => 'Romanian',
        'lang' => 'ro',
        'flag' => 'RO',
        'flagLabel' => 'Romanian flag',
    ]);
    expect(count(array_diff_key($groups, LanguageChoices::MAP_ALIASES)))->toBe(26);
});

it('lets the map\'s "no" mean Norwegian Bokmål', function () {
    $groups = LanguageChoices::byLanguage(Locale::all());

    expect($groups['no'])->toBe($groups['nb']);
    expect($groups['no'][0]['code'])->toBe('nb-NO');
});

it('translates the flag names it hands to the map', function () {
    app()->setLocale('ro');

    $groups = LanguageChoices::byLanguage(Locale::all());

    expect($groups['ro'][0]['flagLabel'])->toBe('Drapelul Romanian');
});

// The map and our languages agree

it('has a country for every seeded country, and Switzerland, which is a candidate', function () {
    $map = mapCountries();

    expect($map)->toHaveCount(32);
    expect(array_diff(Country::pluck('code')->all(), array_keys($map)))->toBe([]);
    expect($map)->toHaveKey('CH');
});

it('leads somewhere from every country on the map', function () {
    $groups = LanguageChoices::byLanguage(Locale::all());

    foreach (mapCountries() as $country => $languages) {
        $available = array_filter($languages, fn ($language) => isset($groups[$language]));

        expect($available)->not->toBeEmpty("{$country} offers no language we have");
    }
});

it('knows exactly which map languages we do not have yet', function () {
    $groups = LanguageChoices::byLanguage(Locale::all());

    $missing = collect(mapCountries())->flatten()->unique()->reject(fn ($language) => isset($groups[$language]))->sort()->values()->all();

    // Luxembourgish, Romansh and Turkish are spoken on the map but are not among the 26.
    expect($missing)->toBe(['lb', 'rm', 'tr']);
});

// The switcher

it('offers the list and the map as two tabs', function () {
    $html = Livewire::test(LanguageSwitcher::class)->html();

    expect($html)
        ->toContain('role="tablist"')
        ->toContain('id="language-tab-list"')
        ->toContain('id="language-tab-map"')
        ->toContain('role="tabpanel"');
});

it('hands the map the data it needs, in the interface language', function () {
    app()->setLocale('ro');

    $component = Livewire::test(LanguageSwitcher::class);
    $config = $component->viewData('mapConfig');

    expect($component->html())->toContain('x-data="europeMap(');
    expect($config['mapUrl'])->toEndWith('/svg/europe-map.svg');
    expect($config['flagsUrl'])->toEndWith('/svg/flags.svg');
    expect($config['flags'])->toContain('RO', 'GB', 'AT');
    expect($config['current'])->toBe('en');
    expect($config['currentCode'])->toBe('en-GB');
    expect($config['languages']['no'][0]['code'])->toBe('nb-NO');
    expect($config['labels']['choose'])->toBe('Alege o țară pe hartă');
});

it('shades the countries of the language the visitor is in', function () {
    $component = Livewire::test(LanguageSwitcher::class);

    // The map file is shaded in the browser from these two values.
    expect($component->viewData('mapConfig')['current'])->toBe('en');

    $user = UserFactory::new()->create();
    app(ReplaceContentLocales::class)->execute($user, ['ro-RO', 'en-GB']);

    $config = Livewire::actingAs($user)->test(LanguageSwitcher::class)->viewData('mapConfig');

    expect($config['current'])->toBe('ro');
    expect($config['currentCode'])->toBe('ro-RO');
});

it('does not carry the map itself in the page, only where to fetch it', function () {
    $html = Livewire::test(LanguageSwitcher::class)->html();

    expect($html)->not->toContain('data-langs')->not->toContain('id="served"');
    // The map file is about 66 KB; the whole switcher, with its 26 options, stays well under that.
    expect(strlen($html))->toBeLessThan(60000);
});

it('keeps the list working as it did', function () {
    $component = Livewire::test(LanguageSwitcher::class);

    expect(substr_count($component->html(), 'wire:click="select('))->toBe(25);
});

it('still switches language when the map asks it to', function () {
    // The map calls the same select() the list does, through $wire.
    Livewire::test(LanguageSwitcher::class)->call('select', 'de-DE')->assertHasNoErrors();

    expect(session('content_locale'))->toBe('de-DE');
});

it('loads the page script that registers the map on the pages that have the switcher', function () {
    foreach (['views/welcome.blade.php', 'views/partials/head.blade.php'] as $view) {
        expect(file_get_contents(resource_path($view)))->toContain('resources/js/app.js');
    }
});
