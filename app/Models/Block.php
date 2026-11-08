<?php

namespace App\Models;

use App\Enums\BlockType;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A stable identity for a piece of a lesson. Its content lives in block_versions.
 */
class Block extends Model
{
    /** @use HasFactory<\Database\Factories\BlockFactory> */
    use HasFactory;

    use HasPublicId;

    protected $fillable = ['lesson_id', 'type', 'derived_from'];

    protected $casts = [
        'type' => BlockType::class,
        'derived_from' => 'array',
    ];

    public function publicIdPrefix(): string
    {
        return 'b';
    }

    /** @return BelongsTo<Lesson, $this> */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /** @return HasMany<BlockVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(BlockVersion::class);
    }
}
