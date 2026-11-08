<?php

namespace Database\Factories;

use App\Models\Locale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Locale>
 */
class LocaleFactory extends Factory
{
    protected $model = Locale::class;

    public function definition(): array
    {
        return [
            'code' => 'en-GB',
            'language' => 'en',
            'flag_country' => 'GB',
            'native_name' => 'English',
            'english_name' => 'English',
            'script' => 'Latn',
            'is_interface' => true,
            'is_launch' => true,
            'sort' => 0,
        ];
    }
}
