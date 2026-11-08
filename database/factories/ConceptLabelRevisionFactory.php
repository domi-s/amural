<?php

namespace Database\Factories;

use App\Enums\LabelStatus;
use App\Models\ConceptLabel;
use App\Models\ConceptLabelRevision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConceptLabelRevision>
 */
class ConceptLabelRevisionFactory extends Factory
{
    protected $model = ConceptLabelRevision::class;

    public function definition(): array
    {
        return [
            'concept_label_id' => ConceptLabel::factory(),
            'pref' => fake()->words(2, true),
            'alt' => [],
            'definition' => null,
            'status' => LabelStatus::Draft,
            'author_id' => null,
            'note' => null,
        ];
    }
}
