<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * One lesson in the topic page's list.
 */
final readonly class LessonEntry
{
    public function __construct(
        public string $title,
        public ?string $url,
        public string $author,
        public ?CarbonInterface $updatedAt,
    ) {}
}
