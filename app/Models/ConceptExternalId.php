<?php

namespace App\Models;

use App\Enums\ExternalIdScheme;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConceptExternalId extends Model
{
    protected $fillable = ['concept_id', 'scheme', 'value'];

    protected $casts = [
        'scheme' => ExternalIdScheme::class,
    ];

    /** @return BelongsTo<Concept, $this> */
    public function concept(): BelongsTo
    {
        return $this->belongsTo(Concept::class);
    }
}
