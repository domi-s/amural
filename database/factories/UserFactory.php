<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Locale;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $first = fake()->firstName();
        $last = fake()->lastName();

        return [
            'username' => Str::lower(Str::ascii($first.'.'.$last)).fake()->unique()->numberBetween(1, 99999),
            'first_name' => $first,
            'last_name' => $last,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'interface_locale' => $this->ensureLocale('en-GB'),
            'country_code' => null,
            'avatar_preset' => fake()->numberBetween(1, 8),
            'role' => UserRole::Teacher,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function curator(): static
    {
        return $this->state(fn () => ['role' => UserRole::Curator]);
    }

    /**
     * Users reference a locale by foreign key, so make sure it exists first.
     */
    private function ensureLocale(string $code): string
    {
        Locale::query()->firstOrCreate(['code' => $code], [
            'language' => 'en',
            'flag_country' => 'GB',
            'native_name' => 'English',
            'english_name' => 'English',
            'script' => 'Latn',
            'is_interface' => true,
            'is_launch' => true,
            'sort' => 0,
        ]);

        return $code;
    }
}
