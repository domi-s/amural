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
     * @param  list<TopicLink>  $filedUnder  The chain of broader topics, from the top down.
     * @param  list<LanguageChip>  $languages  The languages the topic has a name in, in the visitor's order.
     * @param  list<PathStop>  $path  Empty when the topic has no prerequisites and leads nowhere.
     * @param  list<TopicLink>  $neighbours  Related, special-case and narrower topics, at most five.
     * @param  list<LessonEntry>  $lessons  In reading order: the visitor's language first.
     * @param  list<SourceCredit>  $sources
     */
    public function __construct(
        public Concept $concept,
        public ResolvedLabel $label,
        public ?Locale $shownLocale,
        public ?Locale $requestedLocale,
        public array $altNames,
        public string $kicker,
        public string $ages,
        public ?TopicLink $issue,
        public array $filedUnder,
        public array $languages,
        public array $path,
        public array $neighbours,
        public array $lessons,
        public LessonStats $stats,
        public VideoPicks $videos,
        public int $artifactTotal,
        public array $sources,
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

    public function definition(): ?string
    {
        $definition = $this->label->label?->definition;

        return $definition === null || trim($definition) === '' ? null : trim($definition);
    }
}
