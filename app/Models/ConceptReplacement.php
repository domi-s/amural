<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConceptReplacement extends Model
{
    protected $fillable = ['concept_id', 'replaced_by_id', 'reason'];

    /** @return BelongsTo<Concept, $this> */
    public function concept(): BelongsTo
    {
        return $this->belongsTo(Concept::class);
    }

    /** @return BelongsTo<Concept, $this> */
    public function replacedBy(): BelongsTo
    {
        return $this->belongsTo(Concept::class, 'replaced_by_id');
    }
}
