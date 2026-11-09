<?php

namespace App\Support;

/**
 * One stop on the path strip: a prerequisite ("first"), the topic itself ("here"), or a topic
 * it leads to ("then"). `x` is the stop's place along the line, as a percentage.
 */
final readonly class PathStop
{
    public const FIRST = 'first';

    public const HERE = 'here';

    public const THEN = 'then';

    public function __construct(
        public string $kind,
        public string $name,
        public ?string $url,
        public ?string $lang,
        public float $x,
    ) {}
}
