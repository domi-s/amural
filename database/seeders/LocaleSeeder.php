<?php

namespace Database\Seeders;

use App\Models\Locale;
use Illuminate\Database\Seeder;

class LocaleSeeder extends Seeder
{
    /**
     * The 26 EU languages (v0 spec, section 3). Interface and launch locales are the v0 three.
     */
    public function run(): void
    {
        $launch = ['en-GB', 'es-ES', 'ro-RO'];

        $locales = [
            ['bg-BG', 'bg', 'BG', 'български', 'Bulgarian', 'Cyrl'],
            ['hr-HR', 'hr', 'HR', 'hrvatski', 'Croatian', 'Latn'],
            ['cs-CZ', 'cs', 'CZ', 'čeština', 'Czech', 'Latn'],
            ['da-DK', 'da', 'DK', 'dansk', 'Danish', 'Latn'],
            ['nl-NL', 'nl', 'NL', 'Nederlands', 'Dutch', 'Latn'],
            ['en-GB', 'en', 'GB', 'English', 'English', 'Latn'],
            ['et-EE', 'et', 'EE', 'eesti', 'Estonian', 'Latn'],
            ['fi-FI', 'fi', 'FI', 'suomi', 'Finnish', 'Latn'],
            ['fr-FR', 'fr', 'FR', 'français', 'French', 'Latn'],
            ['de-DE', 'de', 'DE', 'Deutsch', 'German', 'Latn'],
            ['el-GR', 'el', 'GR', 'Ελληνικά', 'Greek', 'Grek'],
            ['hu-HU', 'hu', 'HU', 'magyar', 'Hungarian', 'Latn'],
            ['ga-IE', 'ga', 'IE', 'Gaeilge', 'Irish', 'Latn'],
            ['it-IT', 'it', 'IT', 'italiano', 'Italian', 'Latn'],
            ['lv-LV', 'lv', 'LV', 'latviešu', 'Latvian', 'Latn'],
            ['lt-LT', 'lt', 'LT', 'lietuvių', 'Lithuanian', 'Latn'],
            ['mt-MT', 'mt', 'MT', 'Malti', 'Maltese', 'Latn'],
            ['pl-PL', 'pl', 'PL', 'polski', 'Polish', 'Latn'],
            ['pt-PT', 'pt', 'PT', 'português', 'Portuguese', 'Latn'],
            ['ro-RO', 'ro', 'RO', 'română', 'Romanian', 'Latn'],
            ['sk-SK', 'sk', 'SK', 'slovenčina', 'Slovak', 'Latn'],
            ['sl-SI', 'sl', 'SI', 'slovenščina', 'Slovenian', 'Latn'],
            ['es-ES', 'es', 'ES', 'español', 'Spanish', 'Latn'],
            ['sv-SE', 'sv', 'SE', 'svenska', 'Swedish', 'Latn'],
            ['nb-NO', 'nb', 'NO', 'norsk bokmål', 'Norwegian', 'Latn'],
            ['is-IS', 'is', 'IS', 'íslenska', 'Icelandic', 'Latn'],
        ];

        foreach ($locales as $sort => [$code, $language, $flag, $native, $english, $script]) {
            Locale::query()->updateOrCreate(['code' => $code], [
                'language' => $language,
                'flag_country' => $flag,
                'native_name' => $native,
                'english_name' => $english,
                'script' => $script,
                'is_interface' => in_array($code, $launch, true),
                'is_launch' => in_array($code, $launch, true),
                'sort' => $sort,
            ]);
        }
    }
}
