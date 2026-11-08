<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Revision>
 */
class RevisionFactory extends Factory
{
    protected $model = Revision::class;

    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'parent_id' => null,
            'merge_parent_id' => null,
            'block_version_ids' => [],
            'title' => fake()->sentence(3),
            'message' => fake()->sentence(),
            'licence' => 'CC-BY-SA-4.0',
            'author_id' => User::factory(),
        ];
    }
}
