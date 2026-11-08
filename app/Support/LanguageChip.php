<?php

namespace App\Support;

use App\Models\Locale;

/**
 * One chip of the language row: a language and whether the topic has a name in it.
 */
final readonly class LanguageChip
{
    public const REVIEWED = 'reviewed';

    /** A machine or draft name: shown, but it needs checking. */
    public const NEEDS_CHECKING = 'needs-checking';

    public const MISSING = 'missing';

    public function __construct(
        public Locale $locale,
        public string $state,
        public string $url,
        public bool $current,
    ) {}
}
