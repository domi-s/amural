<?php

namespace Database\Factories;

use App\Enums\BlockType;
use App\Models\Block;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Block>
 */
class BlockFactory extends Factory
{
    protected $model = Block::class;

    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'type' => BlockType::Markdown,
            'derived_from' => [],
        ];
    }
}
