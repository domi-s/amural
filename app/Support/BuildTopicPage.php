<?php

namespace App\Support;

use App\Models\Concept;
use App\Models\Locale;

/**
 * Gathers what a topic page shows.
 */
class BuildTopicPage
{
    public function __construct(
        private readonly LocaleResolver $locales,
    ) {}

    public function execute(Concept $concept): TopicPage
    {
        $concept->loadMissing('labels');

        $allLocales = Locale::query()->orderByDesc('is_launch')->orderBy('sort')->get();
        $label = $concept->label($this->locales);
        $shown = $allLocales->firstWhere('code', $label->shownLocale());

        return new TopicPage(
            concept: $concept,
            label: $label,
            shownLocale: $shown,
            altNames: $label->label->alt ?? [],
            kicker: $concept->kind->withArticle().' · '.TopicPageData::stageKicker($concept->stage_from, $concept->stage_to),
        );
    }
}
