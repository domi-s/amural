<?php

use App\Enums\LabelStatus;
use App\Models\Concept;
use App\Models\ConceptLabel;
use App\Models\ConceptLabelRevision;
use Database\Seeders\ConceptSpineSeeder;
use Database\Seeders\LocaleSeeder;

beforeEach(function () {
    $this->seed(LocaleSeeder::class);
});

it('seeds ten top-level concepts named in English, Spanish and Romanian', function () {
    $this->seed(ConceptSpineSeeder::class);

    expect(Concept::count())->toBe(10);
    expect(ConceptLabel::count())->toBe(30);
    expect(ConceptLabel::where('status', '!=', LabelStatus::Draft->value)->count())->toBe(0);
    expect(ConceptLabel::where('locale_code', 'ro-RO')->where('pref', 'Algebră liniară')->exists())->toBeTrue();
});

it('records one revision per seeded label and points the label at it', function () {
    $this->seed(ConceptSpineSeeder::class);

    expect(ConceptLabelRevision::count())->toBe(30);

    $label = ConceptLabel::where('locale_code', 'es-ES')->where('pref', 'Álgebra')->firstOrFail();
    expect($label->current_revision_id)->not->toBeNull();
    expect($label->revisions()->count())->toBe(1);
});

it('keeps accent-free search text and a slug for each label', function () {
    $this->seed(ConceptSpineSeeder::class);

    $label = ConceptLabel::where('locale_code', 'ro-RO')->where('pref', 'Algebră')->firstOrFail();

    expect($label->slug)->toBe('algebra');
    expect($label->search_text)->toBe('algebra');
});

it('can be seeded twice without duplicating concepts, labels or revisions', function () {
    $this->seed(ConceptSpineSeeder::class);
    $this->seed(ConceptSpineSeeder::class);

    expect(Concept::count())->toBe(10);
    expect(ConceptLabel::count())->toBe(30);
    expect(ConceptLabelRevision::count())->toBe(30);
});
