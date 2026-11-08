<?php

namespace App\Models;

use App\Enums\EdgeStatus;
use App\Enums\EdgeStrength;
use App\Enums\EdgeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConceptEdge extends Model
{
    protected $fillable = [
        'from_concept_id', 'to_concept_id', 'type', 'strength', 'confidence',
        'evidence', 'status', 'created_by',
    ];

    protected $casts = [
        'type' => EdgeType::class,
        'strength' => EdgeStrength::class,
        'status' => EdgeStatus::class,
        'evidence' => 'array',
        'confidence' => 'decimal:2',
    ];

    /** @return BelongsTo<Concept, $this> */
    public function from(): BelongsTo
    {
        return $this->belongsTo(Concept::class, 'from_concept_id');
    }

    /** @return BelongsTo<Concept, $this> */
    public function to(): BelongsTo
    {
        return $this->belongsTo(Concept::class, 'to_concept_id');
    }
}
