<?php

use App\Actions\Users\UpdateProfile;
use Database\Factories\UserFactory;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LocaleSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed([LocaleSeeder::class, CountrySeeder::class]);
    $this->user = UserFactory::new()->create(['first_name' => 'Ana', 'last_name' => 'Popescu', 'username' => 'ana.popescu']);
});

function profileData(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Ana',
        'last_name' => 'Popescu',
        'username' => 'ana.popescu',
        'bio' => 'I teach maths.',
        'country_code' => 'RO',
        'avatar_preset' => 3,
    ], $overrides);
}

function updateProfile($user, array $overrides = [])
{
    return app(UpdateProfile::class)->execute($user, profileData($overrides));
}

it('updates the profile details', function () {
    $user = updateProfile($this->user, [
        'first_name' => 'Anca',
        'last_name' => 'Ionescu',
        'username' => 'anca.ionescu',
        'bio' => 'Maths in Cluj.',
        'country_code' => 'RO',
        'avatar_preset' => 5,
    ]);

    expect($user->first_name)->toBe('Anca');
    expect($user->username)->toBe('anca.ionescu');
    expect($user->bio)->toBe('Maths in Cluj.');
    expect($user->country_code)->toBe('RO');
    expect($user->avatar_preset)->toBe(5);
});

it('keeps the generated full name in step with the first and last name', function () {
    $user = updateProfile($this->user, ['first_name' => 'Anca', 'last_name' => 'Ionescu']);

    expect($user->name)->toBe('Anca Ionescu');
});

it('lowercases and trims the username and the text fields', function () {
    $user = updateProfile($this->user, [
        'first_name' => '  Anca ',
        'username' => '  Anca.Ionescu ',
        'bio' => "  Hello  \n",
    ]);

    expect($user->first_name)->toBe('Anca');
    expect($user->username)->toBe('anca.ionescu');
    expect($user->bio)->toBe('Hello');
});

it('stores a blank bio or country as null', function () {
    $user = updateProfile($this->user, ['bio' => '   ', 'country_code' => '']);

    expect($user->bio)->toBeNull();
    expect($user->country_code)->toBeNull();
});

it('lets a user keep their own username', function () {
    expect(updateProfile($this->user, ['username' => 'ana.popescu'])->username)->toBe('ana.popescu');
});

it('rejects a username another user has, ignoring case', function () {
    UserFactory::new()->create(['username' => 'taken.name']);

    expect(fn () => updateProfile($this->user, ['username' => 'Taken.Name']))->toThrow(ValidationException::class);
});

it('rejects badly formed usernames', function (string $username) {
    expect(fn () => updateProfile($this->user, ['username' => $username]))->toThrow(ValidationException::class);
})->with([
    'too short' => 'ab',
    'too long' => str_repeat('a', 31),
    'has a space' => 'ana popescu',
    'leading dot' => '.ana',
    'trailing hyphen' => 'ana-',
    'double separator' => 'ana..popescu',
    'accents' => 'anăpopescu',
    'slash' => 'ana/popescu',
    'empty' => '',
]);

it('requires a first and last name', function () {
    expect(fn () => updateProfile($this->user, ['first_name' => '']))->toThrow(ValidationException::class);
    expect(fn () => updateProfile($this->user, ['last_name' => '  ']))->toThrow(ValidationException::class);
});

it('limits the bio to 1,000 characters', function () {
    expect(updateProfile($this->user, ['bio' => str_repeat('a', 1000)])->bio)->toHaveLength(1000);
    expect(fn () => updateProfile($this->user, ['bio' => str_repeat('a', 1001)]))->toThrow(ValidationException::class);
});

it('only accepts a known country', function () {
    expect(fn () => updateProfile($this->user, ['country_code' => 'XX']))->toThrow(ValidationException::class);
});

it('only accepts an avatar from 1 to 8', function (mixed $preset, bool $valid) {
    if ($valid) {
        expect(updateProfile($this->user, ['avatar_preset' => $preset])->avatar_preset)->toBe((int) $preset);
    } else {
        expect(fn () => updateProfile($this->user, ['avatar_preset' => $preset]))->toThrow(ValidationException::class);
    }
})->with([
    'lowest' => [1, true],
    'highest' => [8, true],
    'as a string' => ['4', true],
    'zero' => [0, false],
    'nine' => [9, false],
    'not a number' => ['abc', false],
]);

it('changes nothing when the input is invalid', function () {
    expect(fn () => updateProfile($this->user, ['first_name' => 'Changed', 'username' => 'a']))->toThrow(ValidationException::class);

    expect($this->user->fresh()->first_name)->toBe('Ana');
});
