<?php

namespace App\Support;

use App\Models\Locale;

/**
 * The lessons on a topic that are written in one language.
 */
final readonly class LessonGroup
{
    /**
     * @param  list<LessonEntry>  $lessons  Most recently updated first.
     */
    public function __construct(
        public Locale $locale,
        public array $lessons,
    ) {}
}
