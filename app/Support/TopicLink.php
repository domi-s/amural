<?php

namespace App\Support;

use App\Enums\EdgeStrength;
use App\Models\Concept;

/**
 * A link from a topic page to another topic, named in the visitor's language.
 */
final readonly class TopicLink
{
    public function __construct(
        public Concept $concept,
        public string $name,
        public string $url,
        public bool $proposed = false,
        public ?EdgeStrength $strength = null,
        public ?string $language = null,
    ) {}

    /**
     * The value for a lang attribute when the name is in a different language from the page,
     * otherwise null.
     */
    public function langAttribute(): ?string
    {
        return $this->language !== null && $this->language !== app()->getLocale() ? $this->language : null;
    }
}
