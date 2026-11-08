<?php

namespace Database\Factories;

use App\Enums\ConceptKind;
use App\Enums\ConceptStatus;
use App\Enums\Stage;
use App\Enums\Subject;
use App\Models\Concept;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Concept>
 */
class ConceptFactory extends Factory
{
    protected $model = Concept::class;

    public function definition(): array
    {
        return [
            'subject' => Subject::Mathematics,
            'kind' => ConceptKind::Concept,
            'stage_from' => Stage::Primary,
            'stage_to' => Stage::LowerSecondary,
            'status' => ConceptStatus::Draft,
            'created_by' => null,
        ];
    }
}
