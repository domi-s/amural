<?php

use App\Actions\Locales\ReplaceContentLocales;
use App\Actions\Locales\SwitchContentLocale;
use App\Actions\Locales\UpdateInterfaceLocale;
use App\Actions\Users\CreateUser;
use App\Models\User;
use App\Models\UserContentLocale;
use App\Support\LocaleResolver;
use Database\Factories\UserFactory;
use Database\Seeders\LocaleSeeder;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(LocaleSeeder::class);
});

function contentLocalesOf(User $user): array
{
    return UserContentLocale::where('user_id', $user->id)->orderBy('position')->pluck('locale_code')->all();
}

function userWithLanguages(array $codes): User
{
    $user = UserFactory::new()->create();
    app(ReplaceContentLocales::class)->execute($user, $codes);

    return $user;
}

// ReplaceContentLocales: add, remove and reorder in one write; at least one must remain.

it('sets the content languages in order, with contiguous positions', function () {
    $user = userWithLanguages(['ro-RO', 'en-GB']);

    expect(contentLocalesOf($user))->toBe(['ro-RO', 'en-GB']);
    expect(UserContentLocale::where('user_id', $user->id)->orderBy('position')->pluck('position')->all())->toBe([0, 1]);
});

it('reorders, adds and removes languages', function () {
    $user = userWithLanguages(['ro-RO', 'en-GB']);

    app(ReplaceContentLocales::class)->execute($user, ['en-GB', 'de-DE']);

    expect(contentLocalesOf($user))->toBe(['en-GB', 'de-DE']);
    expect(UserContentLocale::where('user_id', $user->id)->count())->toBe(2);
});

it('does not touch another user\'s languages', function () {
    $other = userWithLanguages(['es-ES']);
    $user = userWithLanguages(['ro-RO']);

    app(ReplaceContentLocales::class)->execute($user, ['en-GB']);

    expect(contentLocalesOf($other))->toBe(['es-ES']);
});

it('requires at least one content language', function () {
    $user = userWithLanguages(['ro-RO']);

    expect(fn () => app(ReplaceContentLocales::class)->execute($user, []))->toThrow(ValidationException::class);

    expect(contentLocalesOf($user))->toBe(['ro-RO']);
});

it('rejects a repeated or unknown language and changes nothing', function () {
    $user = userWithLanguages(['ro-RO', 'en-GB']);

    expect(fn () => app(ReplaceContentLocales::class)->execute($user, ['ro-RO', 'ro-RO']))->toThrow(ValidationException::class);
    expect(fn () => app(ReplaceContentLocales::class)->execute($user, ['ro-RO', 'xx-XX']))->toThrow(ValidationException::class);

    expect(contentLocalesOf($user))->toBe(['ro-RO', 'en-GB']);
});

// UpdateInterfaceLocale: only interface locales.

it('changes the interface language to one the interface is translated into', function () {
    $user = UserFactory::new()->create();

    app(UpdateInterfaceLocale::class)->execute($user, 'ro-RO');

    expect($user->fresh()->interface_locale)->toBe('ro-RO');
});

it('rejects an interface language that is not translated, or unknown', function () {
    $user = UserFactory::new()->create();

    expect(fn () => app(UpdateInterfaceLocale::class)->execute($user, 'fr-FR'))->toThrow(ValidationException::class);
    expect(fn () => app(UpdateInterfaceLocale::class)->execute($user, 'xx-XX'))->toThrow(ValidationException::class);

    expect($user->fresh()->interface_locale)->toBe('en-GB');
});

// SwitchContentLocale: prepend for a user, session for a guest.

it('prepends the chosen language to a user\'s content languages', function () {
    $user = userWithLanguages(['ro-RO', 'en-GB']);

    app(SwitchContentLocale::class)->execute('de-DE', $user);

    expect(contentLocalesOf($user))->toBe(['de-DE', 'ro-RO', 'en-GB']);
});

it('moves a language the user already has to the front without duplicating it', function () {
    $user = userWithLanguages(['ro-RO', 'en-GB', 'de-DE']);

    app(SwitchContentLocale::class)->execute('de-DE', $user);

    expect(contentLocalesOf($user))->toBe(['de-DE', 'ro-RO', 'en-GB']);
});

it('stores the choice in the session for a guest and writes nothing to the database', function () {
    $session = new Store('test', new ArraySessionHandler(120));

    app(SwitchContentLocale::class)->execute('ro-RO', null, $session);

    expect($session->get(LocaleResolver::SESSION_CONTENT))->toBe('ro-RO');
    expect(UserContentLocale::count())->toBe(0);
});

it('rejects switching to an unknown language', function () {
    $user = userWithLanguages(['ro-RO']);
    $session = new Store('test', new ArraySessionHandler(120));

    expect(fn () => app(SwitchContentLocale::class)->execute('xx-XX', $user))->toThrow(ValidationException::class);
    expect(fn () => app(SwitchContentLocale::class)->execute('xx-XX', null, $session))->toThrow(ValidationException::class);

    expect(contentLocalesOf($user))->toBe(['ro-RO']);
    expect($session->has(LocaleResolver::SESSION_CONTENT))->toBeFalse();
});

// CreateUser: every user has an interface language and at least one content language.

it('creates a user with an interface language and content languages', function () {
    $user = app(CreateUser::class)->execute(
        ['first_name' => 'Ana', 'last_name' => 'Popescu', 'email' => 'ana@example.com', 'password' => 'hashed'],
        'ro-RO',
        ['ro-RO', 'en-GB'],
    );

    expect($user->fresh()->interface_locale)->toBe('ro-RO');
    expect(contentLocalesOf($user))->toBe(['ro-RO', 'en-GB']);
});

it('creates no user at all if the languages are invalid', function () {
    $create = fn (string $interface, array $content) => app(CreateUser::class)->execute(
        ['first_name' => 'Ana', 'last_name' => 'Popescu', 'email' => 'ana@example.com', 'password' => 'hashed'],
        $interface,
        $content,
    );

    expect(fn () => $create('ro-RO', []))->toThrow(ValidationException::class);
    expect(fn () => $create('fr-FR', ['ro-RO']))->toThrow(ValidationException::class);

    expect(User::count())->toBe(0);
    expect(UserContentLocale::count())->toBe(0);
});
