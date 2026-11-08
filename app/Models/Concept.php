<?php

namespace App\Models;

use App\Enums\ConceptKind;
use App\Enums\ConceptStatus;
use App\Enums\Stage;
use App\Enums\Subject;
use App\Models\Concerns\HasPublicId;
use App\Support\LocaleResolver;
use App\Support\ResolvedLabel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Concept extends Model
{
    /** @use HasFactory<\Database\Factories\ConceptFactory> */
    use HasFactory;

    use HasPublicId;

    protected $fillable = ['subject', 'kind', 'stage_from', 'stage_to', 'status', 'created_by'];

    protected $casts = [
        'subject' => Subject::class,
        'kind' => ConceptKind::class,
        'stage_from' => Stage::class,
        'stage_to' => Stage::class,
        'status' => ConceptStatus::class,
    ];

    public function publicIdPrefix(): string
    {
        return 'c';
    }

    /** @return HasMany<ConceptLabel, $this> */
    public function labels(): HasMany
    {
        return $this->hasMany(ConceptLabel::class);
    }

    /**
     * The name to show for this concept: the first label in the visitor's content-locale
     * chain, flagged as a fallback when it is not their first choice. Eager-load `labels`
     * on lists so this never queries.
     */
    public function label(?LocaleResolver $resolver = null): ResolvedLabel
    {
        $resolver ??= app(LocaleResolver::class);
        $chain = $resolver->contentChain();

        if ($chain === []) {
            return new ResolvedLabel(null, false, LocaleResolver::DEFAULT);
        }

        $labels = $this->labels->keyBy('locale_code');

        foreach ($chain as $code) {
            if ($labels->has($code)) {
                return new ResolvedLabel($labels->get($code), $code !== $chain[0], $chain[0]);
            }
        }

        return new ResolvedLabel(null, false, $chain[0]);
    }

    /** @return HasMany<ConceptEdge, $this> */
    public function outgoingEdges(): HasMany
    {
        return $this->hasMany(ConceptEdge::class, 'from_concept_id');
    }

    /** @return HasMany<ConceptEdge, $this> */
    public function incomingEdges(): HasMany
    {
        return $this->hasMany(ConceptEdge::class, 'to_concept_id');
    }
}
