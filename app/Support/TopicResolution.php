<?php

namespace App\Support;

use App\Models\Concept;
use Illuminate\Support\Collection;

/**
 * What to do with a request for a topic URL: show the page, redirect (301) to the canonical
 * URL or to what replaced a deprecated topic, or list several replacements.
 */
final readonly class TopicResolution
{
    public const SHOW = 'show';

    public const REDIRECT = 'redirect';

    public const REPLACED_BY_MANY = 'replaced-by-many';

    /**
     * @param  Collection<int, Concept>  $replacements
     */
    private function __construct(
        public string $action,
        public ?string $url = null,
        public Collection $replacements = new Collection,
    ) {}

    public static function show(): self
    {
        return new self(self::SHOW);
    }

    public static function redirect(string $url): self
    {
        return new self(self::REDIRECT, $url);
    }

    /**
     * @param  Collection<int, Concept>  $replacements
     */
    public static function replacedByMany(Collection $replacements): self
    {
        return new self(self::REPLACED_BY_MANY, replacements: $replacements);
    }
}
