<?php

namespace App\Actions\Concepts;

use App\Enums\EdgeStatus;
use App\Enums\EdgeStrength;
use App\Enums\EdgeType;
use App\Exceptions\InvalidConceptEdgeException;
use App\Exceptions\PrerequisiteCycleException;
use App\Models\Concept;
use App\Models\ConceptEdge;
use Illuminate\Support\Facades\DB;

class CreateConceptEdge
{
    /**
     * Direction is fixed (see the v0 spec, section 2.3):
     *  - broader:       from = narrower, to = parent
     *  - prerequisite:  from = needed first, to = what it enables
     *  - special_case:  from = special case, to = general
     *  - related:       stored once with the lower id first, treated as undirected
     *
     * @param  array{strength?: ?EdgeStrength, confidence?: ?float, evidence?: list<array<string, mixed>>, status?: EdgeStatus, created_by?: ?int}  $attributes
     */
    public function execute(Concept $from, Concept $to, EdgeType $type, array $attributes = []): ConceptEdge
    {
        if ($from->is($to)) {
            throw new InvalidConceptEdgeException("A concept cannot be its own {$type->value}.");
        }

        if ($type === EdgeType::Related && $from->id > $to->id) {
            [$from, $to] = [$to, $from];
        }

        return DB::transaction(function () use ($from, $to, $type, $attributes) {
            if ($type === EdgeType::Prerequisite && $this->leadsTo($to, $from)) {
                throw PrerequisiteCycleException::wouldCreateCycle($from->id, $to->id);
            }

            return ConceptEdge::create([
                'from_concept_id' => $from->id,
                'to_concept_id' => $to->id,
                'type' => $type,
                'strength' => $attributes['strength'] ?? null,
                'confidence' => $attributes['confidence'] ?? null,
                'evidence' => $attributes['evidence'] ?? [],
                'status' => $attributes['status'] ?? EdgeStatus::MachineProposed,
                'created_by' => $attributes['created_by'] ?? null,
            ]);
        });
    }

    /**
     * Breadth-first search along live prerequisite edges: can $start reach $target?
     */
    private function leadsTo(Concept $start, Concept $target): bool
    {
        $seen = [$start->id => true];
        $frontier = [$start->id];

        while ($frontier !== []) {
            if (isset($seen[$target->id])) {
                return true;
            }

            $next = ConceptEdge::query()
                ->where('type', EdgeType::Prerequisite->value)
                ->where('status', '!=', EdgeStatus::Rejected->value)
                ->whereIn('from_concept_id', $frontier)
                ->pluck('to_concept_id')
                ->unique()
                ->reject(fn ($id) => isset($seen[$id]))
                ->values()
                ->all();

            foreach ($next as $id) {
                $seen[$id] = true;
            }

            $frontier = $next;
        }

        return isset($seen[$target->id]);
    }
}
