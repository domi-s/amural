<?php

namespace App\Support;

/**
 * Which artifacts go where under "To watch": the featured video, the smaller second one, and
 * everything else as a sentence of links.
 */
final readonly class VideoPicks
{
    /**
     * @param  list<ArtifactEntry>  $others
     */
    public function __construct(
        public ?ArtifactEntry $featured,
        public ?ArtifactEntry $second,
        public array $others,
    ) {}
}
