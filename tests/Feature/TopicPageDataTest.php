<?php

use App\Enums\ArtifactType;
use App\Enums\Stage;
use App\Models\Artifact;
use App\Models\Concept;
use App\Models\Locale;
use App\Support\ArtifactEntry;
use App\Support\LessonEntry;
use App\Support\PathStop;
use App\Support\TopicLink;
use App\Support\TopicPageData;
use Illuminate\Support\Carbon;

// The rules the topic page applies to its content, without a database or a view.

function lessonEntry(string $title, string $locale, bool $primary = true, ?string $updated = null): LessonEntry
{
    return new LessonEntry(
        title: $title,
        url: null,
        author: 'Someone',
        authorId: 1,
        locale: (new Locale)->forceFill(['code' => $locale, 'language' => substr($locale, 0, 2)]),
        versions: 1,
        primary: $primary,
        updatedAt: $updated === null ? null : Carbon::parse($updated),
    );
}

function artifactEntry(string $title, ?string $locale, bool $youtube = true): ArtifactEntry
{
    return new ArtifactEntry(
        artifact: new Artifact(['title' => $title, 'type' => $youtube ? ArtifactType::Youtube : ArtifactType::Link, 'youtube_id' => $youtube ? 'abc' : null]),
        locale: $locale === null ? null : (new Locale)->forceFill(['code' => $locale, 'language' => substr($locale, 0, 2)]),
        host: null,
    );
}

function topicLink(string $name): TopicLink
{
    return new TopicLink(new Concept(['id' => crc32($name)]), $name, "/topic/{$name}");
}

// The circled word

it('circles the longest word of five or more letters that is not the first word', function () {
    expect(TopicPageData::circledWordIndex('Adding unlike fractions'))->toBe(2);
    expect(TopicPageData::circledWordIndex('Equivalent fractions'))->toBe(1);
});

it('never circles the first word, even when it is the longest', function () {
    expect(TopicPageData::circledWordIndex('Quadrilaterals and squares'))->toBe(2);
});

it('circles the earlier word when two are as long', function () {
    expect(TopicPageData::circledWordIndex('Number lines plots graph'))->toBe(1);
});

it('circles nothing when no later word has five letters', function () {
    expect(TopicPageData::circledWordIndex('Pi'))->toBeNull();
    expect(TopicPageData::circledWordIndex('Area of a box'))->toBeNull();
});

it('counts letters, not punctuation, and works in any script', function () {
    expect(TopicPageData::circledWordIndex('Adding (fractions)'))->toBe(1);
    expect(TopicPageData::circledWordIndex('Адитивни суми'))->toBeNull();
    expect(TopicPageData::circledWordIndex('Προσθέτοντας κλάσματα'))->toBe(1);
});

// The drop cap

it('gives a drop cap to a Latin definition that starts with a letter the display font can draw', function () {
    expect(TopicPageData::dropCap('Numbers that name a part.', 'Latn'))->toBe(['N', 'umbers that name a part.']);
});

it('gives no drop cap in Greek or Cyrillic, or for a letter the display font cannot draw', function () {
    expect(TopicPageData::dropCap('Αριθμοί', 'Grek'))->toBeNull();
    expect(TopicPageData::dropCap('Числа', 'Cyrl'))->toBeNull();
    expect(TopicPageData::dropCap('Își', 'Latn'))->toBeNull();
    expect(TopicPageData::dropCap('2 + 2', 'Latn'))->toBeNull();
    expect(TopicPageData::dropCap('', 'Latn'))->toBeNull();
});

// The path

it('has no path without prerequisites or a next step', function () {
    expect(TopicPageData::pathStops([], 'Here', null, []))->toBe([]);
});

it('places the stops left to right: first, here, then, at most two on each side', function () {
    $stops = TopicPageData::pathStops([topicLink('a'), topicLink('b'), topicLink('c')], 'Here', null, [topicLink('x'), topicLink('y'), topicLink('z')]);

    expect(array_map(fn (PathStop $s) => $s->kind.':'.$s->name, $stops))
        ->toBe(['first:a', 'first:b', 'here:Here', 'then:x', 'then:y']);
});

it('spreads the stops evenly between 12% and 88%', function () {
    $stops = TopicPageData::pathStops([topicLink('a')], 'Here', null, [topicLink('x')]);

    expect(array_map(fn (PathStop $s) => $s->x, $stops))->toBe([12.0, 50.0, 88.0]);
});

it('links every stop but this topic, which is not a link', function () {
    $stops = TopicPageData::pathStops([topicLink('a')], 'Here', 'ro', []);

    expect($stops[0]->url)->toBe('/topic/a');
    expect($stops[1]->url)->toBeNull();
    expect($stops[1]->lang)->toBe('ro');
});

// Lesson order

it('orders lessons by language first, then primary topic, then newest', function () {
    $lessons = [
        lessonEntry('ro old primary', 'ro-RO', true, '2026-01-01'),
        lessonEntry('en secondary', 'en-GB', false, '2026-05-01'),
        lessonEntry('en primary old', 'en-GB', true, '2026-02-01'),
        lessonEntry('en primary new', 'en-GB', true, '2026-04-01'),
        lessonEntry('de primary', 'de-DE', true, '2026-06-01'),
    ];

    $titles = array_map(fn (LessonEntry $l) => $l->title, TopicPageData::orderLessons($lessons, ['en-GB', 'ro-RO']));

    expect($titles)->toBe(['en primary new', 'en primary old', 'en secondary', 'ro old primary', 'de primary']);
});

it('keeps the given order for lessons that tie', function () {
    $lessons = [lessonEntry('first', 'en-GB'), lessonEntry('second', 'en-GB')];

    $titles = array_map(fn (LessonEntry $l) => $l->title, TopicPageData::orderLessons($lessons, ['en-GB']));

    expect($titles)->toBe(['first', 'second']);
});

// Videos

it('writes the kicker stages in words and the filed-under ages as numbers', function () {
    expect(TopicPageData::stageKicker(Stage::Primary, Stage::LowerSecondary))->toBe('Primary to lower secondary');
    expect(TopicPageData::stageKicker(Stage::Primary, Stage::Primary))->toBe('Primary');
    expect(TopicPageData::stageAges(Stage::Primary, Stage::LowerSecondary))->toBe('Ages 7 to 14');
    expect(TopicPageData::stageAges(Stage::EarlyPrimary, Stage::EarlyPrimary))->toBe('Ages 4 to 7');
});

it('falls back to words for stages that are not tied to an age', function () {
    expect(TopicPageData::stageAges(Stage::UpperSecondary, Stage::Undergraduate))->toBe('Upper secondary to undergraduate');
    expect(TopicPageData::stageAges(Stage::Graduate, Stage::Graduate))->toBe('Graduate');
});

it('takes the age bands from the language files, so a country can change them', function () {
    app()->setLocale('ro');

    expect(TopicPageData::stageAges(Stage::Primary, Stage::LowerSecondary))->toBe('Vârsta 7 – 14 ani');
});

// The logo
