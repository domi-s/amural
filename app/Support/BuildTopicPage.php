<?php

namespace App\Support;

use App\Enums\ConceptStatus;
use App\Enums\EdgeStatus;
use App\Enums\EdgeStrength;
use App\Enums\EdgeType;
use App\Enums\LabelStatus;
use App\Enums\LessonStatus;
use App\Models\Artifact;
use App\Models\Concept;
use App\Models\ConceptEdge;
use App\Models\ConceptSource;
use App\Models\Lesson;
use App\Models\Locale;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

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

    public function execute(Concept $concept, bool $showProposed = false, ?string $artifactLanguage = null): TopicPage
    {
        $concept->loadMissing('labels');

        $statuses = $showProposed
            ? [EdgeStatus::Reviewed->value, EdgeStatus::MachineProposed->value]
            : [EdgeStatus::Reviewed->value];

        $allLocales = Locale::query()->orderByDesc('is_launch')->orderBy('sort')->get();
        $label = $concept->label($this->locales);

        $edges = $this->edges($concept, $statuses);
        $parents = $this->parents($edges, $concept);

        [$row, $more] = $this->languageRows($concept, $allLocales, $showProposed);
        [$artifacts, $artifactFilter, $artifactTotal] = $this->artifacts($concept, $allLocales, $artifactLanguage, $showProposed);

        return new TopicPage(
            concept: $concept,
            label: $label,
            shownLocale: $allLocales->firstWhere('code', $label->shownLocale()),
            requestedLocale: $allLocales->firstWhere('code', $label->requestedLocale),
            altNames: $label->label->alt ?? [],
            stageText: $this->stageText($concept),
            breadcrumb: $this->breadcrumb($parents[0] ?? null, $statuses),
            alsoUnder: array_slice($parents, 1),
            before: $this->links($edges, EdgeType::Prerequisite, fromThis: false, concept: $concept),
            leadsTo: $this->links($edges, EdgeType::Prerequisite, fromThis: true, concept: $concept),
            inside: $this->links($edges, EdgeType::Broader, fromThis: false, concept: $concept),
            related: $this->related($edges, $concept),
            languageRow: $row,
            moreLanguages: $more,
            namedCount: $concept->labels->count(),
            reviewedCount: $concept->labels->where('status', LabelStatus::Reviewed)->count(),
            languageCount: $allLocales->count(),
            showProposed: $showProposed,
            toggleProposedUrl: $this->urls->canonicalUrl($concept, $showProposed ? [] : ['proposed' => 1]),
            lessonGroups: $this->lessonGroups($concept, $allLocales),
            artifacts: $artifacts,
            artifactFilter: $artifactFilter,
            artifactTotal: $artifactTotal,
            sources: $this->sources($concept),
        );
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
     * Published lessons on the topic, by language: the visitor's own languages first, then
     * launch languages, then the rest.
     *
     * @param  Collection<int, Locale>  $allLocales
     * @return list<LessonGroup>
     */
    private function lessonGroups(Concept $concept, Collection $allLocales): array
    {
        $lessons = $concept->lessons()
            ->where('lessons.status', LessonStatus::Published->value)
            ->with(['owner', 'headRevision'])
            ->get();

        $position = $allLocales->pluck('code')->flip();
        $preferred = array_flip($this->locales->preferredLocales());

        return $lessons
            ->groupBy('locale_code')
            ->map(function (Collection $group, string $code) use ($allLocales) {
                $locale = $allLocales->firstWhere('code', $code);

                return $locale === null ? null : new LessonGroup(
                    locale: $locale,
                    lessons: $group
                        ->map(fn (Lesson $lesson) => new LessonEntry(
                            title: $lesson->title,
                            url: Route::has('lessons.show')
                                ? route('lessons.show', array_filter(['lesson' => $lesson->public_id, 'slug' => $lesson->slug]))
                                : null,
                            author: $lesson->owner->name,
                            updatedAt: $lesson->headRevision->created_at ?? $lesson->updated_at,
                        ))
                        ->sortByDesc(fn (LessonEntry $entry) => $entry->updatedAt?->getTimestamp() ?? 0)
                        ->values()
                        ->all(),
                );
            })
            ->filter()
            ->sortBy(fn (LessonGroup $group) => [$preferred[$group->locale->code] ?? PHP_INT_MAX, $position[$group->locale->code] ?? PHP_INT_MAX])
            ->values()
            ->all();
    }

    /**
     * The artifacts on the topic, narrowed by the language filter.
     *
     * @param  Collection<int, Locale>  $allLocales
     * @return array{0: list<ArtifactEntry>, 1: list<ArtifactFilterOption>, 2: int}
     */
    private function artifacts(Concept $concept, Collection $allLocales, ?string $filter, bool $showProposed): array
    {
        $entries = $concept->artifacts()->get()->map(function (Artifact $artifact) use ($allLocales) {
            $host = $artifact->url === null ? null : parse_url($artifact->url, PHP_URL_HOST);

            return new ArtifactEntry(
                artifact: $artifact,
                locale: $artifact->locale_code === null ? null : $allLocales->firstWhere('code', $artifact->locale_code),
                host: is_string($host) ? preg_replace('/^www\./', '', $host) : null,
            );
        });

        $total = $entries->count();

        // The languages present, in the language row's order, plus "no language" if any.
        $present = $allLocales->filter(fn (Locale $locale) => $entries->contains(fn (ArtifactEntry $e) => $e->locale?->code === $locale->code));
        $hasNeutral = $entries->contains(fn (ArtifactEntry $e) => $e->locale === null);

        $valid = array_merge(
            [ArtifactFilterOption::ALL],
            $present->pluck('code')->all(),
            $hasNeutral ? [ArtifactFilterOption::NEUTRAL] : [],
        );
        $active = in_array($filter, $valid, true) ? $filter : ArtifactFilterOption::ALL;

        $url = fn (string $key) => $this->urls->canonicalUrl($concept, array_filter([
            'proposed' => $showProposed ? 1 : null,
            'artifact_lang' => $key === ArtifactFilterOption::ALL ? null : $key,
        ]));

        $options = [];

        // A filter only helps when there is something to choose between.
        if (count($valid) > 2) {
            $options[] = new ArtifactFilterOption(ArtifactFilterOption::ALL, null, $total, $url(ArtifactFilterOption::ALL), $active === ArtifactFilterOption::ALL);

            foreach ($present as $locale) {
                $count = $entries->filter(fn (ArtifactEntry $e) => $e->locale?->code === $locale->code)->count();
                $options[] = new ArtifactFilterOption($locale->code, $locale, $count, $url($locale->code), $active === $locale->code);
            }

            if ($hasNeutral) {
                $count = $entries->filter(fn (ArtifactEntry $e) => $e->locale === null)->count();
                $options[] = new ArtifactFilterOption(ArtifactFilterOption::NEUTRAL, null, $count, $url(ArtifactFilterOption::NEUTRAL), $active === ArtifactFilterOption::NEUTRAL);
            }
        }

        $shown = match ($active) {
            ArtifactFilterOption::ALL => $entries,
            ArtifactFilterOption::NEUTRAL => $entries->filter(fn (ArtifactEntry $e) => $e->locale === null),
            default => $entries->filter(fn (ArtifactEntry $e) => $e->locale?->code === $active),
        };

        return [$shown->values()->all(), $options, $total];
    }

    /**
     * The credits a topic's sources require, one per source and statement.
     *
     * @return list<SourceCredit>
     */
    private function sources(Concept $concept): array
    {
        return $concept->sources()->get()
            ->unique(fn (ConceptSource $source) => $source->source.'|'.$source->attribution)
            ->map(fn (ConceptSource $source) => new SourceCredit(
                name: (string) config("amural.sources.{$source->source}", $source->source),
                attribution: $source->attribution,
                licence: $source->licence,
                url: $source->url,
            ))
            ->sortBy('name')
            ->values()
            ->all();
    }

    private function stageText(Concept $concept): string
    {
        return $concept->stage_from === $concept->stage_to
            ? $concept->stage_from->label()
            : $concept->stage_from->label().' – '.$concept->stage_to->label();
    }

    /**
     * The language row: launch languages first, then the visitor's own, then the rest behind
     * "more languages".
     *
     * @param  Collection<int, Locale>  $allLocales
     * @return array{0: list<LanguageChip>, 1: list<LanguageChip>}
     */
    private function languageRows(Concept $concept, Collection $allLocales, bool $showProposed): array
    {
        $labels = $concept->labels->keyBy('locale_code');
        $currentSlug = $this->urls->slugFor($concept);
        $current = $concept->label($this->locales)->shownLocale();
        $preferred = $this->locales->preferredLocales();

        $chip = function (Locale $locale) use ($concept, $labels, $currentSlug, $current, $showProposed): LanguageChip {
            $label = $labels->get($locale->code);

            $state = match (true) {
                $label === null => LanguageChip::MISSING,
                $label->status === LabelStatus::Reviewed => LanguageChip::REVIEWED,
                default => LanguageChip::NEEDS_CHECKING,
            };

            $query = ['lang' => $locale->code] + ($showProposed ? ['proposed' => 1] : []);

            return new LanguageChip(
                locale: $locale,
                state: $state,
                url: $this->urls->urlWithSlug($concept, $label?->slug ?: $currentSlug, $query),
                current: $locale->code === $current,
            );
        };

        $launch = $allLocales->where('is_launch', true);
        $own = $allLocales
            ->where('is_launch', false)
            ->filter(fn (Locale $locale) => in_array($locale->code, $preferred, true))
            ->sortBy(fn (Locale $locale) => array_search($locale->code, $preferred, true));
        $rest = $allLocales
            ->where('is_launch', false)
            ->reject(fn (Locale $locale) => in_array($locale->code, $preferred, true));

        return [
            $launch->merge($own)->map($chip)->values()->all(),
            $rest->map($chip)->values()->all(),
        ];
    }
}
