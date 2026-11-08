<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable content of one block at one point in its history.
 */
class BlockVersion extends Model
{
    /** @use HasFactory<\Database\Factories\BlockVersionFactory> */
    use HasFactory;

    use AppendOnly;

    public const UPDATED_AT = null;

    protected $fillable = ['block_id', 'content', 'content_hash', 'author_id', 'parent_version_id'];

    protected $casts = [
        'content' => 'array',
        'created_at' => 'datetime',
    ];

    /** @return BelongsTo<Block, $this> */
    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
