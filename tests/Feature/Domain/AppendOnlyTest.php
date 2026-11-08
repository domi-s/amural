<?php

use App\Exceptions\AppendOnlyViolation;
use Database\Factories\ConceptLabelFactory;
use Database\Factories\ConceptLabelRevisionFactory;
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

it('still allows the mutable cache tables to change', function () {
    $owner = UserFactory::new()->create();
    $label = ConceptLabelFactory::new()->create(['locale_code' => 'en-GB']);

    $label->update(['pref' => 'Renamed']);

    expect($label->fresh()->pref)->toBe('Renamed');
    expect($owner->exists)->toBeTrue();
});
