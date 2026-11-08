<?php

namespace App\Support;

/**
 * Where some of a topic's content comes from, and the credit its licence requires.
 */
final readonly class SourceCredit
{
    public function __construct(
        public string $name,
        public string $attribution,
        public string $licence,
        public ?string $url,
    ) {}
}
