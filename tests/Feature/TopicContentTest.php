<?php

use App\Actions\Concepts\UpdateConceptLabel;
use App\Actions\Locales\ReplaceContentLocales;
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

it('lists published lessons as a numbered list with author, language and versions', function () {
    lessonOn($this->topic, 'Adding fractions step by step', 'en-GB', updated: Carbon::parse('2026-03-14'));

    $html = $this->get($this->url)->assertOk()->content();

    expect($html)->toContain('In this issue')
        ->toContain('<ol class="flex flex-col gap-7">')
        ->toContain('>01<')
        ->toContain('Adding fractions step by step')
        ->toContain('<em>Tom Clarke</em> · English · 1 version');
});

it('counts a lesson\'s versions from its revisions', function () {
    $lesson = lessonOn($this->topic, 'Versioned');
    RevisionFactory::new()->count(2)->create(['lesson_id' => $lesson->id, 'author_id' => $lesson->owner_id]);

    $this->get($this->url)->assertSee('3 versions');
});

it('says how many lessons, teachers and languages there are', function () {
    $tom = UserFactory::new()->create();
    lessonOn($this->topic, 'One', 'en-GB', ['author' => $tom]);
    lessonOn($this->topic, 'Two', 'ro-RO', ['author' => $tom]);
    lessonOn($this->topic, 'Three', 'ro-RO');

    $this->get($this->url)->assertSee('3 lessons, by 2 teachers, in 2 languages. Take one, change it, make it yours.');
});

it('uses the singular for one of each', function () {
    lessonOn($this->topic, 'Only');

    $this->get($this->url)->assertSee('1 lesson, by 1 teacher, in 1 language.');
});

it('leaves out lessons that are drafts or archived', function () {
    lessonOn($this->topic, 'Published lesson');
    lessonOn($this->topic, 'Draft lesson', attributes: ['status' => LessonStatus::Draft]);
    lessonOn($this->topic, 'Archived lesson', attributes: ['status' => LessonStatus::Archived]);

    $this->get($this->url)->assertSee('Published lesson')->assertDontSee('Draft lesson')->assertDontSee('Archived lesson');
});

it('leaves out lessons on other topics, and shows the empty state', function () {
    $other = ConceptFactory::new()->create();
    lessonOn($other, 'A lesson on something else');

    $this->get($this->url)->assertDontSee('A lesson on something else')->assertSee('Nobody has written this one yet.');
});

it('puts the visitor\'s own languages first, then the rest', function () {
    lessonOn($this->topic, 'English lesson', 'en-GB');
    lessonOn($this->topic, 'Lecție în română', 'ro-RO');
    lessonOn($this->topic, 'Lección en español', 'es-ES');
    lessonOn($this->topic, 'Lektion auf Deutsch', 'de-DE');

    $user = UserFactory::new()->create();
    app(ReplaceContentLocales::class)->execute($user, ['de-DE', 'es-ES']);

    $this->actingAs($user)->get($this->url)
        ->assertSeeInOrder(['Lektion auf Deutsch', 'Lección en español']);

    $html = $this->actingAs($user)->get($this->url)->content();
    expect(strpos($html, 'Lección en español'))->toBeLessThan(strpos($html, 'English lesson'));
});

it('puts the most recently updated lesson first within a language', function () {
    lessonOn($this->topic, 'Older lesson', updated: now()->subYear());
    lessonOn($this->topic, 'Newer lesson', updated: now()->subDay());

    $this->get($this->url)->assertSeeInOrder(['Newer lesson', 'Older lesson']);
});

it('shows eight lessons and keeps the rest behind "All n lessons"', function () {
    foreach (range(1, 10) as $i) {
        lessonOn($this->topic, "Lesson number {$i}", updated: now()->subDays($i));
    }

    $html = $this->get($this->url)->content();

    expect($html)->toContain('All 10 lessons')->toContain('<details');
    expect(strpos($html, 'Lesson number 8'))->toBeLessThan(strpos($html, 'All 10 lessons'));
    expect(strpos($html, 'Lesson number 9'))->toBeGreaterThan(strpos($html, 'All 10 lessons'));
    expect($html)->toContain('>09<')->toContain('>10<');
});

it('links a lesson only when lesson pages exist', function () {
    $lesson = lessonOn($this->topic, 'Adding fractions step by step');

    $this->get($this->url)->assertDontSee("/lessons/{$lesson->public_id}", false);

    Route::get('/lessons/{lesson}/{slug?}', fn () => 'lesson')->name('lessons.show');
    refreshRoutes();

    $this->get($this->url)->assertSee("/lessons/{$lesson->public_id}/adding-fractions-step-by-step", false);
});

it('offers to write the next lesson only when the lesson editor exists', function () {
    $this->get($this->url)->assertDontSee('Write the next one');

    Route::get('/lessons/create', fn () => 'create')->name('lessons.create');
    refreshRoutes();

    $this->get($this->url)
        ->assertSee('Write the next one')
        ->assertSee("/lessons/create?topic={$this->topic->public_id}", false);
});

// Videos and files: "To watch"

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
