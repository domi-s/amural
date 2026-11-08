<?php

namespace App\Support;

use App\Models\Locale;

/**
 * One choice in the artifacts' language filter: all, one language, or language-neutral.
 */
final readonly class ArtifactFilterOption
{
    public const ALL = 'all';

    public const NEUTRAL = 'none';

    public function __construct(
        public string $key,
        public ?Locale $locale,
        public int $count,
        public string $url,
        public bool $active,
    ) {}
}
