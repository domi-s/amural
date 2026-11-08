<?php

namespace Database\Factories;

use App\Models\Block;
use App\Models\BlockVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlockVersion>
 */
class BlockVersionFactory extends Factory
{
    protected $model = BlockVersion::class;

    public function definition(): array
    {
        $content = ['text' => fake()->sentence()];

        return [
            'block_id' => Block::factory(),
            'content' => $content,
            'content_hash' => hash('sha256', json_encode($content)),
            'author_id' => null,
            'parent_version_id' => null,
        ];
    }
}
