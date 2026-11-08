<?php

namespace App\Models;

use App\Enums\ArtifactType;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Artifact extends Model
{
    use HasPublicId;

    protected $fillable = [
        'owner_id', 'type', 'locale_code', 'title', 'description', 'url', 'youtube_id', 'licence',
    ];

    protected $casts = [
        'type' => ArtifactType::class,
    ];

    public function publicIdPrefix(): string
    {
        return 'a';
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

    /** @return MorphToMany<Concept, $this> */
    public function concepts(): MorphToMany
    {
        return $this->morphedByMany(Concept::class, 'artifactable', 'artifactables')
            ->withPivot(['position', 'attached_by'])
            ->withTimestamps();
    }

    /** @return MorphToMany<Lesson, $this> */
    public function lessons(): MorphToMany
    {
        return $this->morphedByMany(Lesson::class, 'artifactable', 'artifactables')
            ->withPivot(['position', 'attached_by'])
            ->withTimestamps();
    }
}
