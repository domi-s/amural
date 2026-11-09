<?php

namespace App\Support;

use App\Enums\ConceptStatus;
use App\Enums\EdgeStatus;
use App\Enums\EdgeStrength;
use App\Enums\EdgeType;
use App\Enums\LabelStatus;
use App\Models\Concept;
use App\Models\ConceptEdge;
use App\Models\Locale;
use Illuminate\Support\Collection;

/**
 * Gathers what a topic page shows, with a fixed number of queries however many links there
 * are: the topic with its labels, its edges with both ends and their labels, the languages,
 * and one query per level of the breadcrumb.
 */
class BuildTopicPage
{
    private const MAX_BREADCRUMB_DEPTH = 8;

    public function __construct(
        private readonly LocaleResolver $locales,
        private readonly TopicResolver $urls,
    ) {}

    public function execute(Concept $concept): TopicPage
    {
        $concept->loadMissing('labels');

        $statuses = [EdgeStatus::Reviewed->value];

        $allLocales = Locale::query()->orderByDesc('is_launch')->orderBy('sort')->get();
        $label = $concept->label($this->locales);
        $shown = $allLocales->firstWhere('code', $label->shownLocale());

        $edges = $this->edges($concept, $statuses);
        $parents = $this->parents($edges, $concept);
        $before = $this->links($edges, EdgeType::Prerequisite, fromThis: false, concept: $concept);
        $leadsTo = $this->links($edges, EdgeType::Prerequisite, fromThis: true, concept: $concept);

        $name = $label->label->pref ?? __('Untitled topic');
        $language = $shown?->language;

        return new TopicPage(
            concept: $concept,
            label: $label,
            shownLocale: $shown,
            requestedLocale: $allLocales->firstWhere('code', $label->requestedLocale),
            altNames: $label->label->alt ?? [],
            kicker: $concept->kind->withArticle().' · '.TopicPageData::stageKicker($concept->stage_from, $concept->stage_to),
            ages: TopicPageData::stageAges($concept->stage_from, $concept->stage_to),
            issue: $parents[0] ?? null,
            filedUnder: $this->breadcrumb($parents[0] ?? null, $statuses),
            languages: $this->languages($concept, $allLocales),
            path: TopicPageData::pathStops($before, $name, $language !== null && $language !== app()->getLocale() ? $language : null, $leadsTo),
            neighbours: $this->neighbours($edges, $concept),
        );
    }

    /**
     * "Next door": related, special-case and narrower topics, once each, at most five.
     *
     * @param  Collection<int, ConceptEdge>  $edges
     * @return list<TopicLink>
     */
    private function neighbours(Collection $edges, Concept $concept): array
    {
        $links = array_merge(
            $this->related($edges, $concept),
            $this->links($edges, EdgeType::Broader, fromThis: false, concept: $concept),
        );

        $seen = [];
        $unique = [];

        foreach ($links as $link) {
            if (! isset($seen[$link->concept->id])) {
                $seen[$link->concept->id] = true;
                $unique[] = $link;
            }
        }

        return array_slice($unique, 0, TopicPageData::MAX_NEIGHBOURS);
    }

    /**
     * Every live edge touching the topic, with both ends and their labels loaded.
     *
     * @param  list<string>  $statuses
     * @return Collection<int, ConceptEdge>
     */
    private function edges(Concept $concept, array $statuses): Collection
    {
        return ConceptEdge::query()
            ->where(fn ($query) => $query
                ->where('from_concept_id', $concept->id)
                ->orWhere('to_concept_id', $concept->id))
            ->whereIn('status', $statuses)
            ->with(['from.labels', 'to.labels'])
            ->get();
    }

    /**
     * Links of one kind of edge. `fromThis` says which end of the edge is this topic: when it is
     * the `from` end the link is to the `to` topic, and the other way round.
     *
     * @param  Collection<int, ConceptEdge>  $edges
     * @return list<TopicLink>
     */
    private function links(Collection $edges, EdgeType $type, bool $fromThis, Concept $concept): array
    {
        $links = $edges
            ->filter(fn (ConceptEdge $edge) => $edge->type === $type
                && ($fromThis ? $edge->from_concept_id === $concept->id : $edge->to_concept_id === $concept->id))
            ->map(fn (ConceptEdge $edge) => $this->link($edge, $fromThis ? $edge->to : $edge->from))
            ->filter();

        return $this->sorted($links, byStrength: $type === EdgeType::Prerequisite && ! $fromThis);
    }

    /**
     * This topic's parents, in the order the links were first recorded, so "first parent" is
     * stable and does not change when a name does.
     *
     * @param  Collection<int, ConceptEdge>  $edges
     * @return list<TopicLink>
     */
    private function parents(Collection $edges, Concept $concept): array
    {
        return $edges
            ->filter(fn (ConceptEdge $edge) => $edge->type === EdgeType::Broader && $edge->from_concept_id === $concept->id)
            ->sortBy('id')
            ->map(fn (ConceptEdge $edge) => $this->link($edge, $edge->to))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Related and special-case topics, whichever end of the edge this topic is.
     *
     * @param  Collection<int, ConceptEdge>  $edges
     * @return list<TopicLink>
     */
    private function related(Collection $edges, Concept $concept): array
    {
        $links = $edges
            ->filter(fn (ConceptEdge $edge) => in_array($edge->type, [EdgeType::Related, EdgeType::SpecialCase], true))
            ->map(fn (ConceptEdge $edge) => $this->link($edge, $edge->from_concept_id === $concept->id ? $edge->to : $edge->from))
            ->filter()
            ->unique(fn (TopicLink $link) => $link->concept->id);

        return $this->sorted($links, byStrength: false);
    }

    private function link(ConceptEdge $edge, Concept $other): ?TopicLink
    {
        if ($other->status === ConceptStatus::Deprecated) {
            return null;
        }

        $label = $other->label($this->locales)->label;

        return new TopicLink(
            concept: $other,
            name: $label->pref ?? __('Untitled topic'),
            url: $this->urls->canonicalUrl($other),
            proposed: $edge->status === EdgeStatus::MachineProposed,
            strength: $edge->strength,
            language: $label === null ? null : explode('-', $label->locale_code)[0],
        );
    }

    /**
     * @param  Collection<int, TopicLink>  $links
     * @return list<TopicLink>
     */
    private function sorted(Collection $links, bool $byStrength): array
    {
        return $links
            ->sortBy(fn (TopicLink $link) => [
                $byStrength ? match ($link->strength) {
                    EdgeStrength::Required => 0,
                    EdgeStrength::Helpful => 1,
                    null => 2,
                } : 0,
                mb_strtolower($link->name),
            ])
            ->values()
            ->all();
    }

    /**
     * From the top of the tree down to (not including) the topic's own parent link, following
     * each topic's first parent.
     *
     * @param  list<string>  $statuses
     * @return list<TopicLink>
     */
    private function breadcrumb(?TopicLink $parent, array $statuses): array
    {
        $trail = [];
        $seen = [];

        for ($depth = 0; $parent !== null && $depth < self::MAX_BREADCRUMB_DEPTH; $depth++) {
            if (isset($seen[$parent->concept->id])) {
                break; // a loop in the data: stop rather than spin
            }

            $seen[$parent->concept->id] = true;
            array_unshift($trail, $parent);

            $next = ConceptEdge::query()
                ->where('from_concept_id', $parent->concept->id)
                ->where('type', EdgeType::Broader->value)
                ->whereIn('status', $statuses)
                ->with('to.labels')
                ->orderBy('id')
                ->first();

            $parent = $next === null ? null : $this->link($next, $next->to);
        }

        return $trail;
    }

    /**
     * The languages the topic has a name in, in the visitor's order. Each links to the topic in
     * that language; the page form sends ?lang= from the button.
     *
     * @param  Collection<int, Locale>  $allLocales
     * @return list<LanguageChip>
     */
    private function languages(Concept $concept, Collection $allLocales): array
    {
        $labels = $concept->labels->keyBy('locale_code');
        $current = $concept->label($this->locales)->shownLocale();
        $chips = [];

        foreach ($this->locales->contentChain() as $code) {
            $label = $labels->get($code);
            $locale = $allLocales->firstWhere('code', $code);

            if ($label === null || $locale === null) {
                continue;
            }

            $chips[] = new LanguageChip(
                locale: $locale,
                needsChecking: $label->status !== LabelStatus::Reviewed,
                url: $this->urls->urlWithSlug($concept, $label->slug ?: $this->urls->slugFor($concept)),
                current: $code === $current,
            );
        }

        return $chips;
    }
}
