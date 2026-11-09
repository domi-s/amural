<?php

use App\Actions\Concepts\UpdateConceptLabel;
use App\Enums\LabelStatus;
use App\Enums\UserRole;
use App\Models\ConceptLabelRevision;
use App\Models\User;
use Database\Factories\ConceptFactory;
use Database\Factories\UserFactory;

// Rule 2: one revision per real change and none for a no-op; a teacher's edit to a reviewed label makes it a draft.

function label(array $data, $concept, ?User $author = null, bool $markReviewed = false)
{
    return app(UpdateConceptLabel::class)->execute($concept, 'en-GB', $data, $author, markReviewed: $markReviewed);
}

beforeEach(function () {
    UserFactory::new()->create();
    $this->concept = ConceptFactory::new()->create();
});

it('creates one revision for a new label and one for each real change', function () {
    label(['pref' => 'Fractions'], $this->concept);
    label(['pref' => 'Fractions', 'definition' => 'Parts of a whole.'], $this->concept);
    label(['pref' => 'Fractions', 'definition' => 'Parts of a whole.', 'alt' => ['Rational numbers']], $this->concept);

    expect(ConceptLabelRevision::count())->toBe(3);
});

it('creates no revision when nothing changed', function () {
    label(['pref' => 'Fractions', 'alt' => ['Rationals']], $this->concept);
    $before = ConceptLabelRevision::count();

    label(['pref' => '  Fractions ', 'alt' => ['Rationals', 'rationals', 'FRACTIONS'], 'definition' => ' '], $this->concept);

    expect(ConceptLabelRevision::count())->toBe($before);
});

it('points the label at its newest revision', function () {
    label(['pref' => 'Fractions'], $this->concept);
    $label = label(['pref' => 'Fractions and ratios'], $this->concept);

    expect($label->current_revision_id)->toBe(ConceptLabelRevision::latest('id')->first()->id);
});

it('lets a curator mark an edit as reviewed', function () {
    $curator = UserFactory::new()->curator()->create();

    expect(label(['pref' => 'Fractions'], $this->concept, $curator, markReviewed: true)->status)->toBe(LabelStatus::Reviewed);
});

it('makes an edit by a teacher a draft, even when they ask for review', function () {
    $teacher = UserFactory::new()->create(['role' => UserRole::Teacher]);

    expect(label(['pref' => 'Fractions'], $this->concept, $teacher, markReviewed: true)->status)->toBe(LabelStatus::Draft);
});

it('sets a reviewed label back to draft when a teacher edits it', function () {
    $curator = UserFactory::new()->curator()->create();
    $teacher = UserFactory::new()->create();
    label(['pref' => 'Fractions'], $this->concept, $curator, markReviewed: true);

    $label = label(['pref' => 'Fractions, simply'], $this->concept, $teacher);

    expect($label->status)->toBe(LabelStatus::Draft);
});

it('keeps a status the caller sets itself, as the importer does', function () {
    $label = app(UpdateConceptLabel::class)->execute($this->concept, 'en-GB', ['pref' => 'Fractions'], status: LabelStatus::Machine);

    expect($label->status)->toBe(LabelStatus::Machine);
});
