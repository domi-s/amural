<?php

namespace App\Models;

use App\Enums\LessonStatus;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    /** @use HasFactory<\Database\Factories\LessonFactory> */
    use HasFactory;

    use HasPublicId;

    protected $fillable = [
        'owner_id', 'locale_code', 'title', 'slug', 'licence', 'status',
        'head_revision_id', 'forked_from_revision_id', 'translated_from_revision_id',
    ];

    protected $casts = [
        'status' => LessonStatus::class,
    ];

    public function publicIdPrefix(): string
    {
        return 'l';
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return BelongsTo<Locale, $this> */
    public function locale(): BelongsTo
    {
        return $this->belongsTo(Locale::class, 'locale_code', 'code');
    }

    /** @return BelongsTo<Revision, $this> */
    public function headRevision(): BelongsTo
    {
        return $this->belongsTo(Revision::class, 'head_revision_id');
    }

    /** @return HasMany<Revision, $this> */
    public function revisions(): HasMany
    {
        return $this->hasMany(Revision::class);
    }

    /** @return BelongsToMany<Concept, $this> */
    public function concepts(): BelongsToMany
    {
        return $this->belongsToMany(Concept::class, 'lesson_concepts')
            ->withPivot('is_primary')
            ->withTimestamps();
    }
}
