<?php

namespace App\Support;

use App\Models\Concept;
use App\Models\Locale;

/**
 * Everything a topic page shows, ready to render.
 */
final readonly class TopicPage
{
    /**
     * @param  list<string>  $altNames
     * @param  list<TopicLink>  $breadcrumb  Ancestors from the top down, following each topic's first parent.
     * @param  list<TopicLink>  $alsoUnder  Other parents of this topic.
     * @param  list<TopicLink>  $before  Direct prerequisites, required first.
     * @param  list<TopicLink>  $leadsTo
     * @param  list<TopicLink>  $inside
     * @param  list<TopicLink>  $related  Related and special-case topics.
     * @param  list<LanguageChip>  $languageRow  Launch languages, then the visitor's own.
     * @param  list<LanguageChip>  $moreLanguages  The rest, behind "more languages".
     */
    public function __construct(
        public Concept $concept,
        public ResolvedLabel $label,
        public ?Locale $shownLocale,
        public ?Locale $requestedLocale,
        public array $altNames,
        public string $stageText,
        public array $breadcrumb,
        public array $alsoUnder,
        public array $before,
        public array $leadsTo,
        public array $inside,
        public array $related,
        public array $languageRow,
        public array $moreLanguages,
        public int $namedCount,
        public int $reviewedCount,
        public int $languageCount,
        public bool $showProposed,
        public string $toggleProposedUrl,
    ) {}

    /**
     * The name to show. Falls back to a plain word when the topic has no name in any language.
     */
    public function name(): string
    {
        return $this->label->label->pref ?? __('Untitled topic');
    }

    /**
     * The lang attribute for the topic's own text when it is in a different language from the
     * page, otherwise null.
     */
    public function langAttribute(): ?string
    {
        $language = $this->shownLocale?->language;

        return $language !== null && $language !== app()->getLocale() ? $language : null;
    }

    public function hasStructure(): bool
    {
        return $this->before !== [] || $this->leadsTo !== [] || $this->inside !== [] || $this->related !== [];
    }
}
