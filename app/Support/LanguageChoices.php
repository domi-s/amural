<?php

namespace App\Support;

use App\Models\Locale;
use Illuminate\Support\Collection;

/**
 * The content languages grouped by language code ("de", "ro"), which is how the map of
 * Europe names the languages spoken in each country. The map says "no" for Norwegian where
 * the locales say "nb", so that code is an alias.
 */
final class LanguageChoices
{
    /** Map language code => locale language code, where they differ. */
    public const MAP_ALIASES = ['no' => 'nb'];

    /**
     * @param  Collection<int, Locale>  $locales
     * @return array<string, list<array{code: string, name: string, english: string, lang: string, flag: string, flagLabel: string}>>
     */
    public static function byLanguage(Collection $locales): array
    {
        $groups = [];

        foreach ($locales as $locale) {
            $groups[$locale->language][] = [
                'code' => $locale->code,
                'name' => $locale->display_name,
                'english' => $locale->english_name,
                'lang' => $locale->language,
                'flag' => $locale->flag_country,
                'flagLabel' => __(':language flag', ['language' => $locale->english_name]),
            ];
        }

        // The map's own codes point at the same locales.
        foreach (self::MAP_ALIASES as $mapCode => $localeLanguage) {
            if (isset($groups[$localeLanguage])) {
                $groups[$mapCode] = $groups[$localeLanguage];
            }
        }

        return $groups;
    }
}
