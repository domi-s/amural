<?php

use App\Actions\Concepts\CreateConceptEdge;
use App\Enums\EdgeStatus;
use App\Enums\EdgeStrength;
use App\Enums\EdgeType;
use App\Exceptions\InvalidConceptEdgeException;
use App\Exceptions\PrerequisiteCycleException;
use App\Models\ConceptEdge;
use Database\Factories\ConceptFactory;

// Rule 6: prerequisite edges cannot form a cycle; a concept cannot be its own parent.

it('rejects a concept being its own parent', function () {
    $concept = ConceptFactory::new()->create();

    expect(fn () => app(CreateConceptEdge::class)->execute($concept, $concept, EdgeType::Broader))
        ->toThrow(InvalidConceptEdgeException::class);

    expect(ConceptEdge::count())->toBe(0);
});

it('rejects a prerequisite that would close a two-step cycle', function () {
    $fractions = ConceptFactory::new()->create();
    $adding = ConceptFactory::new()->create();

    // Equivalent fractions -> adding fractions (from = needed first)
    app(CreateConceptEdge::class)->execute($fractions, $adding, EdgeType::Prerequisite, [
        'strength' => EdgeStrength::Required,
        'status' => EdgeStatus::Reviewed,
    ]);

    expect(fn () => app(CreateConceptEdge::class)->execute($adding, $fractions, EdgeType::Prerequisite, [
        'strength' => EdgeStrength::Required,
        'status' => EdgeStatus::Reviewed,
    ]))->toThrow(PrerequisiteCycleException::class, 'cycle');

    expect(ConceptEdge::where('type', EdgeType::Prerequisite)->count())->toBe(1);
});

it('rejects a prerequisite that would close a longer cycle', function () {
    $a = ConceptFactory::new()->create();
    $b = ConceptFactory::new()->create();
    $c = ConceptFactory::new()->create();

    app(CreateConceptEdge::class)->execute($a, $b, EdgeType::Prerequisite);
    app(CreateConceptEdge::class)->execute($b, $c, EdgeType::Prerequisite);

    expect(fn () => app(CreateConceptEdge::class)->execute($c, $a, EdgeType::Prerequisite))
        ->toThrow(PrerequisiteCycleException::class);
});

it('accepts prerequisites that do not form a cycle', function () {
    $a = ConceptFactory::new()->create();
    $b = ConceptFactory::new()->create();
    $c = ConceptFactory::new()->create();

    app(CreateConceptEdge::class)->execute($a, $b, EdgeType::Prerequisite);
    app(CreateConceptEdge::class)->execute($a, $c, EdgeType::Prerequisite);

    expect(ConceptEdge::where('type', EdgeType::Prerequisite)->count())->toBe(2);
});

it('stores related edges once with the lower id first', function () {
    $first = ConceptFactory::new()->create();
    $second = ConceptFactory::new()->create();

    $edge = app(CreateConceptEdge::class)->execute($second, $first, EdgeType::Related);

    expect($edge->from_concept_id)->toBe($first->id)
        ->and($edge->to_concept_id)->toBe($second->id);
});
