<?php

namespace App\Support;

use App\Models\Locale;
use Carbon\CarbonInterface;

/**
 * One lesson in the topic page's numbered list.
 */
final readonly class LessonEntry
{
    public function __construct(
        public string $title,
        public ?string $url,
        public string $author,
        public int $authorId,
        public Locale $locale,
        public int $versions,
        public bool $primary,
        public ?CarbonInterface $updatedAt,
    ) {}
}
