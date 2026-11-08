<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * All EU and EEA members plus GB. Countries say where a person is, never a language.
     */
    public function run(): void
    {
        $countries = [
            ['AT', 'Österreich', 'Austria'],
            ['BE', 'België / Belgique / Belgien', 'Belgium'],
            ['BG', 'България', 'Bulgaria'],
            ['HR', 'Hrvatska', 'Croatia'],
            ['CY', 'Κύπρος', 'Cyprus'],
            ['CZ', 'Česko', 'Czechia'],
            ['DK', 'Danmark', 'Denmark'],
            ['EE', 'Eesti', 'Estonia'],
            ['FI', 'Suomi', 'Finland'],
            ['FR', 'France', 'France'],
            ['DE', 'Deutschland', 'Germany'],
            ['GR', 'Ελλάδα', 'Greece'],
            ['HU', 'Magyarország', 'Hungary'],
            ['IE', 'Éire', 'Ireland'],
            ['IT', 'Italia', 'Italy'],
            ['LV', 'Latvija', 'Latvia'],
            ['LT', 'Lietuva', 'Lithuania'],
            ['LU', 'Lëtzebuerg', 'Luxembourg'],
            ['MT', 'Malta', 'Malta'],
            ['NL', 'Nederland', 'Netherlands'],
            ['PL', 'Polska', 'Poland'],
            ['PT', 'Portugal', 'Portugal'],
            ['RO', 'România', 'Romania'],
            ['SK', 'Slovensko', 'Slovakia'],
            ['SI', 'Slovenija', 'Slovenia'],
            ['ES', 'España', 'Spain'],
            ['SE', 'Sverige', 'Sweden'],
            ['IS', 'Ísland', 'Iceland'],
            ['LI', 'Liechtenstein', 'Liechtenstein'],
            ['NO', 'Norge', 'Norway'],
            ['GB', 'United Kingdom', 'United Kingdom'],
        ];

        foreach ($countries as [$code, $native, $english]) {
            Country::query()->updateOrCreate(['code' => $code], [
                'native_name' => $native,
                'english_name' => $english,
                'in_region' => true,
            ]);
        }
    }
}
