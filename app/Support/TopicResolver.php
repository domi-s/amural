<?php

namespace App\Support;

use App\Enums\ConceptStatus;
use App\Models\Concept;
use App\Models\ConceptReplacement;
use Illuminate\Support\Collection;

/**
 * Decides where a topic URL should go (spec rule 4):
 *  - a missing or wrong slug redirects (301) to the canonical URL, whose slug comes from the
 *    visitor's content language;
 *  - a deprecated topic redirects (301) to its replacement, or lists them if there are several.
 * Query strings (?lang=, ?proposed=) are kept on every redirect.
 */
class TopicResolver
{
    /** How many merges and splits to follow before giving up on a chain of replacements. */
    private const MAX_HOPS = 5;

    public function __construct(private readonly LocaleResolver $locales) {}

    /**
     * @param  array<string, mixed>  $query
     */
    public function resolve(Concept $concept, ?string $slug, array $query = []): TopicResolution
    {
        if ($concept->status === ConceptStatus::Deprecated) {
            $replacements = $this->activeReplacements($concept);

            if ($replacements->count() === 1) {
                return TopicResolution::redirect($this->canonicalUrl($replacements->firstOrFail(), $query));
            }

            if ($replacements->count() > 1) {
                return TopicResolution::replacedByMany($replacements);
            }
            // Deprecated with nothing to replace it: show the page, with a notice.
        }

        if (($slug ?: null) !== $this->slugFor($concept)) {
            return TopicResolution::redirect($this->canonicalUrl($concept, $query));
        }

        return TopicResolution::show();
    }

    /**
     * The canonical URL of a topic in the visitor's content language.
     *
     * @param  array<string, mixed>  $query
     */
    public function canonicalUrl(Concept $concept, array $query = []): string
    {
        return $this->urlWithSlug($concept, $this->slugFor($concept), $query);
    }

    /**
     * A topic's URL with an explicit slug (for a link into a particular language).
     *
     * @param  array<string, mixed>  $query
     */
    public function urlWithSlug(Concept $concept, ?string $slug, array $query = []): string
    {
        $url = route('topic.show', array_filter(
            ['concept' => $concept->public_id, 'slug' => $slug],
            fn ($value) => $value !== null && $value !== '',
        ));

        return $query === [] ? $url : $url.'?'.http_build_query($query);
    }

    /**
     * The slug of the label the visitor would see, or null when the topic has no name yet.
     */
    public function slugFor(Concept $concept): ?string
    {
        $concept->loadMissing('labels');

        $slug = $concept->label($this->locales)->label?->slug;

        return $slug === null || $slug === '' ? null : $slug;
    }

    /**
     * The live topics that replaced a deprecated one, following merges of merges, with their
     * labels loaded. Cycles and over-long chains end quietly.
     *
     * @return Collection<int, Concept>
     */
    private function activeReplacements(Concept $deprecated): Collection
    {
        $seen = [$deprecated->id => true];
        $frontier = [$deprecated->id];
        $active = collect();

        for ($hop = 0; $hop < self::MAX_HOPS && $frontier !== []; $hop++) {
            $rows = ConceptReplacement::query()
                ->whereIn('concept_id', $frontier)
                ->with('replacedBy.labels')
                ->orderBy('id')
                ->get();

            $frontier = [];

            foreach ($rows as $row) {
                $target = $row->replacedBy;

                if (isset($seen[$target->id])) {
                    continue;
                }

                $seen[$target->id] = true;

                if ($target->status === ConceptStatus::Deprecated) {
                    $frontier[] = $target->id;
                } else {
                    $active->push($target);
                }
            }
        }

        return $active->values();
    }
}
