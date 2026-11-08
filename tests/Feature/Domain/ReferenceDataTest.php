<?php

use App\Models\Country;
use App\Models\Locale;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LocaleSeeder;

it('seeds the 26 content languages with the three launch locales', function () {
    $this->seed(LocaleSeeder::class);

    expect(Locale::count())->toBe(26);
    expect(Locale::where('is_launch', true)->orderBy('code')->pluck('code')->all())
        ->toBe(['en-GB', 'es-ES', 'ro-RO']);
    expect(Locale::where('is_interface', true)->orderBy('code')->pluck('code')->all())
        ->toBe(['en-GB', 'es-ES', 'ro-RO']);
});

it('records script and flag country for every locale', function () {
    $this->seed(LocaleSeeder::class);

    expect(Locale::find('el-GR')->script)->toBe('Grek');
    expect(Locale::find('bg-BG')->script)->toBe('Cyrl');
    expect(Locale::find('ga-IE')->flag_country)->toBe('IE');
    expect(Locale::where('flag_country', '')->orWhereNull('flag_country')->count())->toBe(0);
});

it('seeds EU and EEA members plus the United Kingdom as in-region countries', function () {
    $this->seed(CountrySeeder::class);

    expect(Country::count())->toBe(31);
    expect(Country::where('in_region', false)->count())->toBe(0);
    expect(Country::whereIn('code', ['GB', 'NO', 'IS', 'LI'])->count())->toBe(4);
    expect(Country::find('AT')->native_name)->toBe('Österreich');
});

it('can be seeded twice without duplicating rows', function () {
    $this->seed(LocaleSeeder::class);
    $this->seed(LocaleSeeder::class);
    $this->seed(CountrySeeder::class);
    $this->seed(CountrySeeder::class);

    expect(Locale::count())->toBe(26);
    expect(Country::count())->toBe(31);
});

it('shows language names with a capital first letter without changing what is stored', function () {
    $this->seed(LocaleSeeder::class);

    expect(Locale::find('ro-RO')->native_name)->toBe('română');
    expect(Locale::find('ro-RO')->display_name)->toBe('Română');
    expect(Locale::find('is-IS')->display_name)->toBe('Íslenska');
    expect(Locale::find('cs-CZ')->display_name)->toBe('Čeština');
    expect(Locale::find('bg-BG')->display_name)->toBe('Български');
    expect(Locale::find('nb-NO')->display_name)->toBe('Norsk bokmål');
    expect(Locale::find('en-GB')->display_name)->toBe('English');
});

it('capitalises every language name', function () {
    $this->seed(LocaleSeeder::class);

    foreach (Locale::all() as $locale) {
        expect(mb_substr($locale->display_name, 0, 1))->toBe(mb_strtoupper(mb_substr($locale->display_name, 0, 1)));
    }
});
