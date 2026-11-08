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

// Lessons

it('lists published lessons with their language, author and date', function () {
    lessonOn($this->topic, 'Adding fractions step by step', 'en-GB', updated: Carbon::parse('2026-03-14'));

    $html = $this->get($this->url)->assertOk()->content();

    expect($html)->toContain('Adding fractions step by step')
        ->toContain('by Tom Clarke')
        ->toContain('Updated 14 March 2026')
        ->toContain('aria-label="English flag"');
});

it('leaves out lessons that are drafts or archived', function () {
    lessonOn($this->topic, 'Published lesson');
    lessonOn($this->topic, 'Draft lesson', attributes: ['status' => LessonStatus::Draft]);
    lessonOn($this->topic, 'Archived lesson', attributes: ['status' => LessonStatus::Archived]);

    $this->get($this->url)->assertSee('Published lesson')->assertDontSee('Draft lesson')->assertDontSee('Archived lesson');
});

it('leaves out lessons on other topics', function () {
    $other = ConceptFactory::new()->create();
    lessonOn($other, 'A lesson on something else');

    $this->get($this->url)->assertDontSee('A lesson on something else')->assertSee('No lessons on this topic yet.');
});

it('groups lessons by language, with the visitor\'s own languages first', function () {
    lessonOn($this->topic, 'English lesson', 'en-GB');
    lessonOn($this->topic, 'Lecție în română', 'ro-RO');
    lessonOn($this->topic, 'Lección en español', 'es-ES');
    lessonOn($this->topic, 'Lektion auf Deutsch', 'de-DE');

    // A guest sees the launch languages first, in the usual order.
    $this->get($this->url)->assertSeeInOrder(['English lesson', 'Lecție în română', 'Lección en español', 'Lektion auf Deutsch']);

    // A Spanish-then-German reader sees those first.
    $user = UserFactory::new()->create();
    app(ReplaceContentLocales::class)->execute($user, ['de-DE', 'es-ES']);

    $this->actingAs($user)->get($this->url)
        ->assertSeeInOrder(['Lektion auf Deutsch', 'Lección en español', 'English lesson', 'Lecție în română']);
});

it('puts the most recently updated lesson first within a language', function () {
    lessonOn($this->topic, 'Older lesson', updated: now()->subYear());
    lessonOn($this->topic, 'Newer lesson', updated: now()->subDay());

    $this->get($this->url)->assertSeeInOrder(['Newer lesson', 'Older lesson']);
});

it('shows lesson dates in the interface language', function () {
    lessonOn($this->topic, 'Lecție', updated: Carbon::parse('2026-03-14'));

    $this->get($this->url, ['Accept-Language' => 'ro'])->assertSee('14 martie 2026');
});

it('links a lesson only when lesson pages exist', function () {
    $lesson = lessonOn($this->topic, 'Adding fractions step by step');

    $this->get($this->url)->assertDontSee("/lessons/{$lesson->public_id}", false);

    Route::get('/lessons/{lesson}/{slug?}', fn () => 'lesson')->name('lessons.show');
    refreshRoutes();

    $this->get($this->url)->assertSee("/lessons/{$lesson->public_id}/adding-fractions-step-by-step", false);
});

it('offers to write a lesson only when the lesson editor exists', function () {
    $this->get($this->url)->assertDontSee('Write a lesson on this topic');

    Route::get('/lessons/create', fn () => 'create')->name('lessons.create');
    refreshRoutes();

    $this->get($this->url)
        ->assertSee('Write a lesson on this topic')
        ->assertSee("/lessons/create?topic={$this->topic->public_id}", false);
});

// Artifacts

it('shows a YouTube video as a thumbnail that loads the privacy-friendly player when clicked', function () {
    artifactOn($this->topic, ['type' => ArtifactType::Youtube, 'title' => 'Fractions explained', 'youtube_id' => 'dQw4w9WgXcQ']);

    $html = $this->get($this->url)->assertOk()->content();

    expect($html)->toContain('https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg')
        ->toContain('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?autoplay=1')
        ->toContain('aria-label="Play video: Fractions explained"')
        ->not->toContain('https://www.youtube.com/embed')
        ->toContain('x-on:click="playing = true"');
});

it('cannot be tricked into breaking out of the video address by a hostile video id', function () {
    artifactOn($this->topic, ['type' => ArtifactType::Youtube, 'youtube_id' => 'x"></iframe><script>alert(1)</script>']);

    $html = $this->get($this->url)->content();

    expect($html)->not->toContain('<script>alert(1)</script>');
    expect($html)->toContain('x%22%3E%3C%2Fiframe%3E%3Cscript%3Ealert%281%29%3C%2Fscript%3E');
});

it('links a web page with its host and marks it as user content', function () {
    artifactOn($this->topic, ['type' => ArtifactType::Link, 'title' => 'Fraction games', 'url' => 'https://www.example.org/games']);

    $this->get($this->url)
        ->assertSee('Fraction games')
        ->assertSee('example.org')
        ->assertSee('href="https://www.example.org/games"', false)
        ->assertSee('rel="ugc nofollow noopener noreferrer"', false);
});

it('never turns a stored non-web address into a link', function () {
    artifactOn($this->topic, ['type' => ArtifactType::Link, 'title' => 'Sneaky link', 'url' => 'javascript:alert(1)']);

    $this->get($this->url)->assertSee('Sneaky link')->assertDontSee('javascript:alert(1)', false);
});

it('shows files and worksheets with their type, licence and description', function () {
    artifactOn($this->topic, ['type' => ArtifactType::Worksheet, 'title' => 'Practice sheet', 'description' => 'Twenty questions.', 'licence' => 'CC-BY-4.0']);

    $this->get($this->url)->assertSee('Practice sheet')->assertSee('Worksheet')->assertSee('CC-BY-4.0')->assertSee('Twenty questions.');
});

it('keeps artifacts in the order they were arranged', function () {
    artifactOn($this->topic, ['title' => 'Second'], position: 2);
    artifactOn($this->topic, ['title' => 'First'], position: 1);

    $this->get($this->url)->assertSeeInOrder(['First', 'Second']);
});

it('says when a topic has no artifacts', function () {
    $this->get($this->url)->assertSee('No videos, files or links yet.');
});

it('labels each artifact with its language, or says it has none', function () {
    artifactOn($this->topic, ['title' => 'Romanian video', 'locale_code' => 'ro-RO']);
    artifactOn($this->topic, ['title' => 'A diagram']);

    $html = $this->get($this->url)->content();

    expect($html)->toContain('aria-label="Romanian flag"')->toContain('No language');
});

it('filters artifacts by language when there is a choice', function () {
    artifactOn($this->topic, ['title' => 'English video', 'locale_code' => 'en-GB']);
    artifactOn($this->topic, ['title' => 'Romanian video', 'locale_code' => 'ro-RO']);
    artifactOn($this->topic, ['title' => 'Diagram without text']);

    $all = $this->get($this->url)->assertSee('English video')->assertSee('Romanian video')->assertSee('Diagram without text');
    $all->assertSee('All languages')->assertSee('artifact_lang=ro-RO', false);

    $this->get("{$this->url}?artifact_lang=ro-RO")
        ->assertSee('Romanian video')
        ->assertDontSee('English video')
        ->assertDontSee('Diagram without text');

    $this->get("{$this->url}?artifact_lang=none")
        ->assertSee('Diagram without text')
        ->assertDontSee('Romanian video');
});

it('ignores a language filter that matches nothing and shows everything', function () {
    artifactOn($this->topic, ['title' => 'English video', 'locale_code' => 'en-GB']);
    artifactOn($this->topic, ['title' => 'Romanian video', 'locale_code' => 'ro-RO']);

    $this->get("{$this->url}?artifact_lang=xx-XX")->assertSee('English video')->assertSee('Romanian video');
    $this->get("{$this->url}?artifact_lang=de-DE")->assertSee('English video')->assertSee('Romanian video');
});

it('shows no filter when everything is in one language', function () {
    artifactOn($this->topic, ['title' => 'One', 'locale_code' => 'en-GB']);
    artifactOn($this->topic, ['title' => 'Two', 'locale_code' => 'en-GB']);

    $this->get($this->url)->assertDontSee('All languages')->assertDontSee('aria-label="Filter by language"', false);
});

it('keeps the proposed-links choice when filtering artifacts', function () {
    artifactOn($this->topic, ['locale_code' => 'en-GB']);
    artifactOn($this->topic, ['locale_code' => 'ro-RO']);

    $this->get("{$this->url}?proposed=1")->assertSee('proposed=1&amp;artifact_lang=ro-RO', false);
});

it('offers to attach an artifact only when that page exists', function () {
    $this->get($this->url)->assertDontSee('Attach a video or file');

    Route::get('/artifacts/create', fn () => 'create')->name('artifacts.create');
    refreshRoutes();

    $this->get($this->url)->assertSee('Attach a video or file')->assertSee('attach=concept%3A'.$this->topic->public_id, false);
});

// Sources and credits

it('shows a source\'s credit in a collapsed section, with the licence and a friendly name', function () {
    ConceptSource::create([
        'concept_id' => $this->topic->id,
        'source' => 'learning-commons-math-lc',
        'source_version' => '2026-03',
        'source_item_id' => 'lc-1',
        'locale' => 'en-US',
        'text' => 'Add fractions with unlike denominators',
        'path' => [],
        'licence' => 'CC-BY-4.0',
        'attribution' => 'Knowledge Graph is provided by Learning Commons under the CC BY-4.0 license.',
        'url' => 'https://learningcommons.org/',
        'raw' => ['standard' => 'CCSS.MATH.CONTENT.5.NF.A.1'],
    ]);

    $html = $this->get($this->url)->content();

    expect($html)->toContain('<details')->not->toMatch('/<details[^>]*\sopen/');
    expect($html)->toContain('Sources and credits')
        ->toContain('Learning Commons Knowledge Graph, math learning components')
        ->toContain('Knowledge Graph is provided by Learning Commons under the CC BY-4.0 license.')
        ->toContain('Licence: CC-BY-4.0')
        ->toContain('learningcommons.org');
});

it('never shows the original standard codes from a source', function () {
    ConceptSource::create([
        'concept_id' => $this->topic->id,
        'source' => 'learning-commons-math-lc',
        'source_version' => '1',
        'source_item_id' => 'lc-1',
        'locale' => 'en-US',
        'text' => 'x',
        'path' => ['CCSS', '5.NF'],
        'licence' => 'CC-BY-4.0',
        'attribution' => 'Credit.',
        'raw' => ['standard' => 'CCSS.MATH.CONTENT.5.NF.A.1'],
    ]);

    $this->get($this->url)->assertDontSee('CCSS')->assertDontSee('5.NF');
});

it('shows each credit once however many items share it, and nothing when there are none', function () {
    foreach (range(1, 3) as $i) {
        ConceptSource::create([
            'concept_id' => $this->topic->id, 'source' => 'learning-commons-math-lc', 'source_version' => '1',
            'source_item_id' => "lc-{$i}", 'locale' => 'en-US', 'text' => 'x', 'path' => [], 'licence' => 'CC-BY-4.0',
            'attribution' => 'The same credit.', 'raw' => [],
        ]);
    }

    expect(substr_count($this->get($this->url)->content(), 'The same credit.'))->toBe(1);

    $bare = ConceptFactory::new()->create();
    app(UpdateConceptLabel::class)->execute($bare, 'en-GB', ['pref' => 'Decimals'], null, LabelStatus::Reviewed);

    $this->get("/topic/{$bare->public_id}/decimals")->assertDontSee('Sources and credits');
});

// History and edit

it('shows History and Edit only when those pages exist, and Edit only when signed in', function () {
    $this->get($this->url)->assertDontSee('Topic actions');

    Route::get('/topic/{concept}/history', fn () => 'history')->name('topic.history');
    Route::get('/topic/{concept}/edit/{locale}', fn () => 'edit')->name('topic.edit');
    refreshRoutes();

    $this->get($this->url)->assertSee('History')->assertDontSee('>Edit<', false);

    $this->actingAs(UserFactory::new()->create())->get($this->url)
        ->assertSee('History')
        ->assertSee("/topic/{$this->topic->public_id}/edit/en-GB", false);
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

it('makes the same number of queries however many lessons, artifacts and sources there are', function () {
    $few = queriesForContent(1);
    $many = queriesForContent(14);

    expect($many)->toBe($few);
});
