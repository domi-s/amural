<?php

use App\Enums\ArtifactType;
use App\Models\Artifact;
use App\Models\Concept;
use App\Models\Locale;
use App\Support\ArtifactEntry;
use App\Support\LessonEntry;
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
