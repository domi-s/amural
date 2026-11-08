<?php

namespace App\Support;

/**
 * What the browse page shows: the top-level topics and a few counts.
 */
final readonly class TopicsIndex
{
    /**
     * @param  list<TopicCard>  $cards
     */
    public function __construct(
        public array $cards,
        public int $topicCount,
        public int $reviewedPercent,
        public int $lessonCount,
    ) {}
}
