<?php

use App\Actions\Concepts\UpdateConceptLabel;
use App\Enums\ArtifactType;
use App\Enums\LabelStatus;
use App\Enums\LessonStatus;
use App\Models\Artifact;
use App\Models\Concept;
use App\Models\ConceptSource;
use App\Models\Lesson;
use Database\Factories\ConceptFactory;
use Database\Factories\LessonFactory;
use Database\Factories\RevisionFactory;
use Database\Factories\UserFactory;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LocaleSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed([LocaleSeeder::class, CountrySeeder::class]);

    $this->topic = ConceptFactory::new()->create();
    app(UpdateConceptLabel::class)->execute($this->topic, 'en-GB', ['pref' => 'Fractions'], null, LabelStatus::Reviewed);
    $this->url = "/topic/{$this->topic->public_id}/fractions";
});

afterEach(function () {
    Model::preventLazyLoading(false);
});

function lessonOn(Concept $topic, string $title, string $locale = 'en-GB', array $attributes = [], ?Carbon $updated = null): Lesson
{
    $author = $attributes['author'] ?? UserFactory::new()->create(['first_name' => 'Tom', 'last_name' => 'Clarke']);
    unset($attributes['author']);

    $lesson = LessonFactory::new()->create(array_merge([
        'owner_id' => $author->id,
        'locale_code' => $locale,
        'title' => $title,
        'slug' => str($title)->slug()->value(),
        'status' => LessonStatus::Published,
    ], $attributes));

    $lesson->concepts()->attach($topic->id, ['is_primary' => true]);

    $revision = RevisionFactory::new()->create([
        'lesson_id' => $lesson->id,
        'author_id' => $author->id,
        'created_at' => $updated ?? now(),
    ]);
    $lesson->update(['head_revision_id' => $revision->id]);

    return $lesson;
}

function artifactOn(Concept $topic, array $attributes = [], int $position = 0): Artifact
{
    $artifact = Artifact::create(array_merge([
        'owner_id' => UserFactory::new()->create()->id,
        'type' => ArtifactType::Link,
        'locale_code' => null,
        'title' => 'An artifact',
        'licence' => 'CC-BY-SA-4.0',
    ], $attributes));

    $topic->artifacts()->attach($artifact->id, ['position' => $position]);

    return $artifact;
}

function refreshRoutes(): void
{
    Route::getRoutes()->refreshNameLookups();
}

// Lessons: "In this issue"

it('shows History and Edit only when those pages exist, and Edit only when signed in', function () {
    $this->get($this->url)->assertDontSee('Topic actions');

    Route::get('/topic/{concept}/history', fn () => 'history')->name('topic.history');
    Route::get('/topic/{concept}/edit/{locale}', fn () => 'edit')->name('topic.edit');
    refreshRoutes();

    $this->get($this->url)->assertSee('History')->assertDontSee('Edit this topic');

    $this->actingAs(UserFactory::new()->create())->get($this->url)
        ->assertSee('History')
        ->assertSee('Edit this topic')
        ->assertSee("/topic/{$this->topic->public_id}/edit/en-GB", false);
});

it('shows a signed-in visitor\'s avatar in the header, and a log in link otherwise', function () {
    $this->get($this->url)->assertSee('Log in');

    $user = UserFactory::new()->create();
    $this->actingAs($user)->get($this->url)->assertDontSee('Log in')->assertSee('aria-label="Profile"', false);
});

// Queries

function queriesForContent(int $items): int
{
    $topic = ConceptFactory::new()->create();
    app(UpdateConceptLabel::class)->execute($topic, 'en-GB', ['pref' => "Busy {$items}"], null, LabelStatus::Reviewed);

    foreach (range(1, $items) as $i) {
        lessonOn($topic, "Lesson {$items}-{$i}", $i % 2 ? 'en-GB' : 'ro-RO');
        artifactOn($topic, ['title' => "Artifact {$items}-{$i}", 'locale_code' => $i % 3 === 0 ? null : 'en-GB', 'url' => 'https://example.org/'.$i]);
        ConceptSource::create([
            'concept_id' => $topic->id, 'source' => 'learning-commons-math-lc', 'source_version' => '1',
            'source_item_id' => "s-{$items}-{$i}", 'locale' => 'en-US', 'text' => 'x', 'path' => [], 'licence' => 'CC-BY-4.0',
            'attribution' => "Credit {$i}", 'raw' => [],
        ]);
    }

    Model::preventLazyLoading();
    DB::flushQueryLog();
    DB::enableQueryLog();

    test()->get("/topic/{$topic->public_id}/busy-{$items}")->assertOk();

    $count = count(DB::getQueryLog());
    DB::disableQueryLog();
    Model::preventLazyLoading(false);

    return $count;
}
