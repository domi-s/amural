<?php

use App\Enums\ArtifactType;
use App\Models\Artifact;
use Database\Factories\ConceptFactory;
use Database\Factories\LessonFactory;
use Database\Factories\RevisionFactory;
use Database\Factories\UserFactory;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('allows only one primary concept per lesson', function () {
    $lesson = LessonFactory::new()->create();
    $first = ConceptFactory::new()->create();
    $second = ConceptFactory::new()->create();
    $third = ConceptFactory::new()->create();

    $lesson->concepts()->attach($first->id, ['is_primary' => true]);
    $lesson->concepts()->attach($second->id, ['is_primary' => false]);

    expect(fn () => $lesson->concepts()->attach($third->id, ['is_primary' => true]))
        ->toThrow(QueryException::class);
});

it('does not allow the same concept to be attached to a lesson twice', function () {
    $lesson = LessonFactory::new()->create();
    $concept = ConceptFactory::new()->create();

    $lesson->concepts()->attach($concept->id, ['is_primary' => false]);

    expect(fn () => $lesson->concepts()->attach($concept->id, ['is_primary' => false]))
        ->toThrow(QueryException::class);
});

it('points a lesson at its head revision once one is saved', function () {
    $revision = RevisionFactory::new()->create();
    $lesson = $revision->lesson;

    $lesson->update(['head_revision_id' => $revision->id]);

    expect($lesson->fresh()->headRevision->is($revision))->toBeTrue();
});

it('stores artifact attachments with short morph names', function () {
    $artifact = Artifact::create([
        'owner_id' => UserFactory::new()->create()->id,
        'type' => ArtifactType::Youtube,
        'title' => 'Demo video',
        'youtube_id' => 'abc123',
        'licence' => 'CC-BY-SA-4.0',
    ]);
    $concept = ConceptFactory::new()->create();
    $lesson = LessonFactory::new()->create();

    $artifact->concepts()->attach($concept->id, ['position' => 0]);
    $artifact->lessons()->attach($lesson->id, ['position' => 0]);

    expect(DB::table('artifactables')->orderBy('id')->pluck('artifactable_type')->all())
        ->toBe(['concept', 'lesson']);
    expect($artifact->public_id)->toStartWith('a_');
});

it('does not attach the same artifact to the same topic twice', function () {
    $artifact = Artifact::create([
        'owner_id' => UserFactory::new()->create()->id,
        'type' => ArtifactType::Link,
        'title' => 'A link',
        'url' => 'https://example.com',
        'licence' => 'CC-BY-SA-4.0',
    ]);
    $concept = ConceptFactory::new()->create();

    $artifact->concepts()->attach($concept->id);

    expect(fn () => $artifact->concepts()->attach($concept->id))->toThrow(QueryException::class);
});
