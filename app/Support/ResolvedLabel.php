<?php

namespace App\Support;

use App\Models\ConceptLabel;

/**
 * The label chosen for a concept, and whether it is a fallback: shown in a language other
 * than the visitor's first choice. `label` is null when the concept has no names at all.
 */
final readonly class ResolvedLabel
{
    public function __construct(
        public ?ConceptLabel $label,
        public bool $isFallback,
        public string $requestedLocale,
    ) {}

    /**
     * The locale the label is actually in, or null when there is none.
     */
    public function shownLocale(): ?string
    {
        return $this->label?->locale_code;
    }
}
