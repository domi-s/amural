<?php

use App\Exceptions\AppendOnlyViolation;
use Database\Factories\BlockFactory;
use Database\Factories\BlockVersionFactory;
use Database\Factories\ConceptFactory;
use Database\Factories\ConceptLabelFactory;
use Database\Factories\ConceptLabelRevisionFactory;
use Database\Factories\LessonFactory;
use Database\Factories\RevisionFactory;
use Database\Factories\UserFactory;

// Rule 1: append-only tables reject updates and deletes.

it('rejects updates to a concept label revision', function () {
    $revision = ConceptLabelRevisionFactory::new()->create();

    expect(fn () => $revision->update(['pref' => 'Changed']))
        ->toThrow(AppendOnlyViolation::class);
});

it('rejects deletes of a concept label revision', function () {
    $revision = ConceptLabelRevisionFactory::new()->create();

    expect(fn () => $revision->delete())
        ->toThrow(AppendOnlyViolation::class);
});

it('rejects updates and deletes of block versions', function () {
    $version = BlockVersionFactory::new()->create();

    expect(fn () => $version->update(['content_hash' => 'x']))->toThrow(AppendOnlyViolation::class);
    expect(fn () => $version->delete())->toThrow(AppendOnlyViolation::class);
});

it('rejects updates and deletes of revisions', function () {
    $revision = RevisionFactory::new()->create();

    expect(fn () => $revision->update(['message' => 'Changed']))->toThrow(AppendOnlyViolation::class);
    expect(fn () => $revision->delete())->toThrow(AppendOnlyViolation::class);
});

it('still allows the mutable cache tables to change', function () {
    $owner = UserFactory::new()->create();
    $label = ConceptLabelFactory::new()->create(['locale_code' => 'en-GB']);

    $label->update(['pref' => 'Renamed']);

    expect($label->fresh()->pref)->toBe('Renamed');
    expect($owner->exists)->toBeTrue();
});

it('gives every model a prefixed public id', function () {
    expect(ConceptFactory::new()->create()->public_id)->toMatch('/^c_[0-9A-HJKMNP-TV-Z]{8}$/');
    expect(LessonFactory::new()->create()->public_id)->toStartWith('l_');
    expect(BlockFactory::new()->create()->public_id)->toStartWith('b_');
    expect(RevisionFactory::new()->create()->public_id)->toStartWith('r_');
});
