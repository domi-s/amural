<?php

namespace App\Models;

use App\Models\Concerns\AppendOnly;
use App\Models\Concerns\HasPublicId;
use Database\Factories\RevisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A saved version of a lesson: an ordered snapshot of block versions. Append-only.
 */
class Revision extends Model
{
    use AppendOnly, HasPublicId;

    /** @use HasFactory<RevisionFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'lesson_id', 'parent_id', 'merge_parent_id', 'block_version_ids',
        'title', 'message', 'licence', 'author_id',
    ];

    protected $casts = [
        'block_version_ids' => 'array',
        'created_at' => 'datetime',
    ];

    public function publicIdPrefix(): string
    {
        return 'r';
    }

    /** @return BelongsTo<Lesson, $this> */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
