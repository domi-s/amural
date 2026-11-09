<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserContentLocale;
use Database\Factories\UserFactory;
use Database\Seeders\LocaleSeeder;
use Illuminate\Database\QueryException;

it('builds the full name in the database from first and last name', function () {
    $user = UserFactory::new()->create(['first_name' => 'Ana', 'last_name' => 'Popescu']);

    expect($user->refresh()->name)->toBe('Ana Popescu');
});

it('gives users a u_ public id', function () {
    expect(UserFactory::new()->create()->public_id)->toMatch('/^u_[0-9A-HJKMNP-TV-Z]{8}$/');
});

it('fills in a username, interface locale, avatar and role when they are not given', function () {
    $this->seed(LocaleSeeder::class);

    $user = User::create([
        'first_name' => 'Lucía',
        'last_name' => 'Martín',
        'email' => 'lucia@example.com',
        'password' => 'secret-password',
    ])->refresh();

    expect($user->username)->toMatch('/^lucia\.martin\d+$/');
    expect($user->interface_locale)->toBe('en-GB');
    expect($user->avatar_preset)->toBeBetween(1, 8);
    expect($user->role)->toBe(UserRole::Teacher);
});

it('never writes the generated name column', function () {
    expect((new User)->getFillable())->not->toContain('name');
});

it('keeps content locales in order of preference', function () {
    $this->seed(LocaleSeeder::class);
    $user = UserFactory::new()->create();

    UserContentLocale::create(['user_id' => $user->id, 'locale_code' => 'en-GB', 'position' => 1]);
    UserContentLocale::create(['user_id' => $user->id, 'locale_code' => 'ro-RO', 'position' => 0]);

    expect($user->contentLocales->pluck('locale_code')->all())->toBe(['ro-RO', 'en-GB']);
});

it('allows a locale only once per user', function () {
    $this->seed(LocaleSeeder::class);
    $user = UserFactory::new()->create();

    UserContentLocale::create(['user_id' => $user->id, 'locale_code' => 'ro-RO', 'position' => 0]);

    expect(fn () => UserContentLocale::create(['user_id' => $user->id, 'locale_code' => 'ro-RO', 'position' => 1]))
        ->toThrow(QueryException::class);
});
