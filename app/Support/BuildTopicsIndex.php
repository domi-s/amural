<?php

namespace App\Support;

use App\Enums\ConceptStatus;
use App\Enums\EdgeStatus;
use App\Enums\EdgeType;
use App\Enums\LessonStatus;
use App\Models\Concept;
use App\Models\ConceptEdge;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Builds the browse page. A top-level topic is a draft or reviewed topic with no reviewed
 * "broader" link above it. Machine-proposed topics (an import can bring thousands) are
 * left out so the page stays a map you can read; search finds them.
 */
class BuildTopicsIndex
{
    public function __construct(
        private readonly LocaleResolver $locales,
        private readonly TopicResolver $urls,
    ) {}

    public function execute(): TopicsIndex
    {
        $topLevel = Concept::query()
            ->whereIn('status', [ConceptStatus::Draft->value, ConceptStatus::Reviewed->value])
            ->whereDoesntHave('outgoingEdges', fn (Builder $edges) => $edges
                ->where('type', EdgeType::Broader->value)
                ->where('status', EdgeStatus::Reviewed->value))
            ->with('labels')
            ->get();

        $children = $this->childrenOf($topLevel);

        $cards = $topLevel
            ->map(fn (Concept $concept) => new TopicCard(
                topic: $this->link($concept),
                children: $children[$concept->id] ?? [],
            ))
            ->sortBy(fn (TopicCard $card) => mb_strtolower($card->topic->name))
            ->values()
            ->all();

        $live = Concept::query()->where('status', '!=', ConceptStatus::Deprecated->value)->count();
        $reviewed = Concept::query()->where('status', ConceptStatus::Reviewed->value)->count();

        return new TopicsIndex(
            cards: $cards,
            topicCount: $live,
            reviewedPercent: $live === 0 ? 0 : (int) round($reviewed / $live * 100),
            lessonCount: Lesson::query()->where('status', LessonStatus::Published->value)->count(),
        );
    }

    /**
     * The narrower topics inside each top-level topic, by parent id, in one query.
     *
     * @param  Collection<int, Concept>  $parents
     * @return array<int, list<TopicLink>>
     */
    private function childrenOf(Collection $parents): array
    {
        return ConceptEdge::query()
            ->where('type', EdgeType::Broader->value)
            ->where('status', EdgeStatus::Reviewed->value)
            ->whereIn('to_concept_id', $parents->pluck('id'))
            ->with('from.labels')
            ->get()
            ->filter(fn (ConceptEdge $edge) => $edge->from->status !== ConceptStatus::Deprecated)
            ->groupBy('to_concept_id')
            ->map(fn (Collection $edges) => $edges
                ->map(fn (ConceptEdge $edge) => $this->link($edge->from))
                ->sortBy(fn (TopicLink $link) => mb_strtolower($link->name))
                ->values()
                ->all())
            ->all();
    }

    private function link(Concept $concept): TopicLink
    {
        $label = $concept->label($this->locales)->label;

        return new TopicLink(
            concept: $concept,
            name: $label->pref ?? __('Untitled topic'),
            url: $this->urls->canonicalUrl($concept),
            language: $label === null ? null : explode('-', $label->locale_code)[0],
        );
    }
}
