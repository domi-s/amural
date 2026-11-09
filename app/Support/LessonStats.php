<?php

namespace App\Support;

/**
 * The counts in the sentence under "In this issue".
 */
final readonly class LessonStats
{
    public function __construct(
        public int $lessons,
        public int $teachers,
        public int $languages,
    ) {}
}
