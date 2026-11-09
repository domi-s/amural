<?php

namespace App\Actions\Concepts;

use App\Enums\ConceptKind;
use App\Enums\ConceptStatus;
use App\Enums\Stage;
use App\Models\Concept;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Changes what a topic is, as opposed to what it is called: its kind, the stages it is for
 * and its review status. Only curators do this. Retiring a topic is a separate, later step.
 */
class UpdateConcept
{
    /**
     * @param  array{kind: string, stage_from: string, stage_to: string, status: string}  $data
     */
    public function execute(Concept $concept, User $author, array $data): Concept
    {
        if (! $author->isCurator()) {
            throw new AuthorizationException('Only curators can change a topic.');
        }

        $data = validator($data, [
            'kind' => ['required', Rule::enum(ConceptKind::class)],
            'stage_from' => ['required', Rule::enum(Stage::class)],
            'stage_to' => ['required', Rule::enum(Stage::class)],
            'status' => ['required', Rule::enum(ConceptStatus::class)->except(ConceptStatus::Deprecated)],
        ])->validate();

        if (Stage::from($data['stage_from'])->order() > Stage::from($data['stage_to'])->order()) {
            throw ValidationException::withMessages(['stage_to' => __('The last stage cannot come before the first.')]);
        }

        $concept->update($data);

        return $concept;
    }
}
