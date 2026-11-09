<?php

namespace App\Support;

use App\Models\Locale;

/**
 * One language in the topic page's margin: a language the topic has a name in.
 */
final readonly class LanguageChip
{
    public function __construct(
        public Locale $locale,
        public bool $needsChecking,
        public string $url,
        public bool $current,
    ) {}
}
