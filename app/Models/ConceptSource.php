<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConceptSource extends Model
{
    protected $fillable = [
        'concept_id', 'source', 'source_version', 'source_item_id', 'locale', 'text',
        'path', 'licence', 'attribution', 'url', 'raw',
    ];

    protected $casts = [
        'path' => 'array',
        'raw' => 'array',
    ];

    /** @return BelongsTo<Concept, $this> */
    public function concept(): BelongsTo
    {
        return $this->belongsTo(Concept::class);
    }
}
