<?php

namespace App\Models;

use App\Enums\LabelStatus;
use App\Models\Concerns\AppendOnly;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only snapshot of a concept label. Never updated or deleted.
 */
class ConceptLabelRevision extends Model
{
    /** @use HasFactory<\Database\Factories\ConceptLabelRevisionFactory> */
    use HasFactory;

    use AppendOnly;

    public const UPDATED_AT = null;

    protected $fillable = ['concept_label_id', 'pref', 'alt', 'definition', 'status', 'author_id', 'note'];

    protected $casts = [
        'alt' => 'array',
        'status' => LabelStatus::class,
        'created_at' => 'datetime',
    ];

    /** @return BelongsTo<ConceptLabel, $this> */
    public function label(): BelongsTo
    {
        return $this->belongsTo(ConceptLabel::class, 'concept_label_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
