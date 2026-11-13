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

it('shows no thumbnail from YouTube, and loads the privacy-friendly player only when clicked', function () {
    artifactOn($this->topic, ['type' => ArtifactType::Youtube, 'title' => 'Fractions explained', 'youtube_id' => 'dQw4w9WgXcQ']);

    $html = $this->get($this->url)->assertOk()->content();

    expect($html)->toContain('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?autoplay=1')
        ->toContain('aria-label="Play video: Fractions explained"')
        ->toContain('x-on:click="play()"')
        ->not->toContain('ytimg')
        ->not->toContain('https://www.youtube.com/embed')
        ->not->toContain('<img');
});

it('cannot be tricked into breaking out of the video address by a hostile video id', function () {
    artifactOn($this->topic, ['type' => ArtifactType::Youtube, 'youtube_id' => 'x"></iframe><script>alert(1)</script>']);

    $html = $this->get($this->url)->content();

    expect($html)->not->toContain('<script>alert(1)</script>');
    expect($html)->toContain('x%22%3E%3C%2Fiframe%3E%3Cscript%3Ealert%281%29%3C%2Fscript%3E');
});

it('features the first video in the visitor\'s language and shows the second smaller', function () {
    artifactOn($this->topic, ['type' => ArtifactType::Youtube, 'youtube_id' => 'aaaaaaaaaaa', 'title' => 'Romanian video', 'locale_code' => 'ro-RO'], position: 1);
    artifactOn($this->topic, ['type' => ArtifactType::Youtube, 'youtube_id' => 'bbbbbbbbbbb', 'title' => 'English video', 'locale_code' => 'en-GB'], position: 2);

    $html = $this->get($this->url)->content();

    // English is the visitor's language, so it is featured (large) and the Romanian one is the small second.
    expect($html)->toMatch('/text-\[28px\][^>]*>English video.*text-lg font-medium[^>]*>Romanian video/s');
    expect($html)->toContain('Video · ')->toContain('· English');
});

it('lists the other artifacts in a sentence of links, marking web pages as user content', function () {
    artifactOn($this->topic, ['type' => ArtifactType::Link, 'title' => 'Fraction games', 'url' => 'https://www.example.org/games'], position: 1);
    artifactOn($this->topic, ['type' => ArtifactType::Worksheet, 'title' => 'Practice sheet', 'url' => 'https://example.org/sheet.pdf'], position: 2);

    $html = $this->get($this->url)->content();

    expect($html)->toContain('Also:')
        ->toContain('href="https://www.example.org/games"')
        ->toContain('rel="ugc nofollow noopener noreferrer"')
        ->toMatch('/Fraction games<\/a>\s*and\s*<a[^>]*>Practice sheet<\/a>\./s');
});

it('never turns a stored non-web address into a link', function () {
    artifactOn($this->topic, ['type' => ArtifactType::Link, 'title' => 'Sneaky link', 'url' => 'javascript:alert(1)']);

    $this->get($this->url)->assertSee('Sneaky link')->assertDontSee('javascript:alert(1)', false);
});

it('keeps the other artifacts in the order they were arranged', function () {
    artifactOn($this->topic, ['title' => 'Second', 'url' => 'https://example.org/2'], position: 2);
    artifactOn($this->topic, ['title' => 'First', 'url' => 'https://example.org/1'], position: 1);

    $this->get($this->url)->assertSeeInOrder(['First', 'Second']);
});

it('gives the other artifacts the full width when there is no video', function () {
    artifactOn($this->topic, ['title' => 'A diagram', 'url' => 'https://example.org/d']);

    $html = $this->get($this->url)->content();

    expect($html)->toContain('A diagram')->not->toContain('mag:grid-cols-[2fr_1fr]')->not->toContain('aria-label="Play video');
});

it('shows nothing for artifacts when there are none and nowhere to add one', function () {
    $this->get($this->url)->assertDontSee('To watch')->assertDontSee('Got a video or worksheet for this?');
});

it('offers to add an artifact only when that page exists', function () {
    Route::get('/artifacts/create', fn () => 'create')->name('artifacts.create');
    refreshRoutes();

    $this->get($this->url)
        ->assertSee('Got a video or worksheet for this?')
        ->assertSee('attach=concept%3A'.$this->topic->public_id, false);

    artifactOn($this->topic, ['title' => 'A diagram', 'url' => 'https://example.org/d']);

    $this->get($this->url)->assertSee('Add yours')->assertDontSee('Got a video or worksheet for this?');
});

// Sources, in the footer

it('names each source with its licence in the footer, and gives the full attribution', function () {
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

    expect($html)->toContain('Lessons, shared across Europe.')
        ->toContain('href="#sources"')
        ->toContain('Learning Commons Knowledge Graph, math learning components (CC-BY-4.0)')
        ->toContain('Edited by teachers')
        ->toContain('id="sources"')
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

it('shows each credit once however many items share it, and no sources line when there are none', function () {
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

    $this->get("/topic/{$bare->public_id}/decimals")->assertDontSee('href="#sources"', false)->assertSee('Edited by teachers');
});

// History and edit

it('shows Edit only when signed in', function () {
    $this->get($this->url)->assertDontSee('Edit this topic');

    $this->actingAs(UserFactory::new()->create())->get($this->url)
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

it('makes the same number of queries however many lessons, artifacts and sources there are', function () {
    $few = queriesForContent(1);
    $many = queriesForContent(14);

    expect($many)->toBe($few);
});

// Content hosted outside Europe

it('tells people a video is hosted outside Europe when they play it, and remembers when they dismiss that', function () {
    artifactOn($this->topic, ['type' => ArtifactType::Youtube, 'youtube_id' => 'dQw4w9WgXcQ', 'title' => 'Fractions explained']);

    $html = $this->get($this->url)->content();

    // Alpine shows it only after a click; the remembered choice lives in this browser, not in an account.
    expect($html)->toContain("x-data=\"externalMedia('youtube')\"")
        ->toContain('x-show="notice"')
        ->toContain('European servers')
        ->toContain('x-on:click="dismissNotice()"');
});

it('says nothing about hosting on a topic with no external video', function () {
    artifactOn($this->topic, ['type' => ArtifactType::Link, 'title' => 'Fraction games', 'url' => 'https://example.org/games']);

    $this->get($this->url)->assertDontSee('externalMedia', false)->assertDontSee('European servers');
});

it('translates the notice', function () {
    artifactOn($this->topic, ['type' => ArtifactType::Youtube, 'youtube_id' => 'dQw4w9WgXcQ']);

    $this->get($this->url, ['Accept-Language' => 'ro'])->assertSee('nu este găzduit pe servere europene', false);
    $this->get($this->url, ['Accept-Language' => 'es'])->assertSee('no está alojado en servidores europeos', false);
});
