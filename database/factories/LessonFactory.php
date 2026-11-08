<?php

namespace Database\Factories;

use App\Enums\LessonStatus;
use App\Models\Lesson;
use App\Models\Locale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'owner_id' => User::factory(),
            'locale_code' => Locale::query()->value('code') ?? LocaleFactory::new()->create()->code,
            'title' => $title,
            'slug' => str($title)->slug()->value(),
            'licence' => 'CC-BY-SA-4.0',
            'status' => LessonStatus::Draft,
            'head_revision_id' => null,
            'forked_from_revision_id' => null,
            'translated_from_revision_id' => null,
        ];
    }
}
