<?php

namespace App\Support;

/**
 * A top-level topic on the browse page, with the narrower topics inside it.
 */
final readonly class TopicCard
{
    /**
     * @param  list<TopicLink>  $children
     */
    public function __construct(
        public TopicLink $topic,
        public array $children,
    ) {}
}
