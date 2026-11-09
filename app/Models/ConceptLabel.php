<?php

namespace App\Models;

use App\Enums\LabelStatus;
use Database\Factories\ConceptLabelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The current name and definition of a concept in one locale.
 * A cache of the latest revision: the revisions table is the record.
 */
class ConceptLabel extends Model
{
    /** @use HasFactory<ConceptLabelFactory> */
    use HasFactory;

    protected $fillable = [
        'concept_id', 'locale_code', 'pref', 'alt', 'definition', 'slug',
        'status', 'current_revision_id', 'search_text',
    ];

    protected $casts = [
        'alt' => 'array',
        'status' => LabelStatus::class,
    ];

    /** @return BelongsTo<Concept, $this> */
    public function concept(): BelongsTo
    {
        return $this->belongsTo(Concept::class);
    }

    /** @return BelongsTo<Locale, $this> */
    public function locale(): BelongsTo
    {
        return $this->belongsTo(Locale::class, 'locale_code', 'code');
    }

    /** @return HasMany<ConceptLabelRevision, $this> */
    public function revisions(): HasMany
    {
        return $this->hasMany(ConceptLabelRevision::class);
    }
}
