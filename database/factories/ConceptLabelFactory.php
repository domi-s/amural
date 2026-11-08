<?php

namespace Database\Factories;

use App\Enums\LabelStatus;
use App\Models\Concept;
use App\Models\ConceptLabel;
use App\Models\Locale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConceptLabel>
 */
class ConceptLabelFactory extends Factory
{
    protected $model = ConceptLabel::class;

    public function definition(): array
    {
        $pref = fake()->unique()->words(3, true);

        return [
            'concept_id' => Concept::factory(),
            'locale_code' => Locale::query()->value('code') ?? LocaleFactory::new()->create()->code,
            'pref' => $pref,
            'alt' => [],
            'definition' => null,
            'slug' => str($pref)->slug()->value(),
            'status' => LabelStatus::Draft,
            'current_revision_id' => null,
            'search_text' => mb_strtolower($pref),
        ];
    }
}
