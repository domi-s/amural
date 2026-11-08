<?php

namespace App\Support;

use App\Models\Country;

/**
 * English country names by ISO code, loaded once per request, so a flag can always be given
 * an accessible name without a query per flag.
 */
class CountryNames
{
    /** @var array<string, string>|null */
    private ?array $names = null;

    public function english(string $code): string
    {
        $code = strtoupper($code);

        $this->names ??= Country::query()->pluck('english_name', 'code')->all();

        return $this->names[$code] ?? $code;
    }
}
