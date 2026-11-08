<?php

use App\Actions\Locales\ReplaceContentLocales;
use App\Models\User;
use App\Models\UserContentLocale;
use Database\Factories\UserFactory;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LocaleSeeder;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->seed([LocaleSeeder::class, CountrySeeder::class]);

    $this->user = UserFactory::new()->create([
        'first_name' => 'Ana',
        'last_name' => 'Popescu',
        'username' => 'ana.popescu',
        'bio' => 'I teach maths.',
        'country_code' => 'RO',
        'avatar_preset' => 4,
        'interface_locale' => 'en-GB',
    ]);
    app(ReplaceContentLocales::class)->execute($this->user, ['ro-RO', 'en-GB']);
});

function contentLocalesFor(User $user): array
{
    return UserContentLocale::where('user_id', $user->id)->orderBy('position')->pluck('locale_code')->all();
}

// Access

it('sends guests to sign in', function () {
    $this->get('/profile')->assertRedirect('/login');
});

it('shows the signed-in user\'s profile, with the language switcher and a profile link', function () {
    $this->actingAs($this->user)->get('/profile')
        ->assertOk()
        ->assertSee('Profile')
        ->assertSeeLivewire(App\Livewire\LanguageSwitcher::class)
        ->assertSee(route('profile'), false);
});

it('prefills the form from the user', function () {
    $this->actingAs($this->user);

    Volt::test('profile')
        ->assertSet('first_name', 'Ana')
        ->assertSet('last_name', 'Popescu')
        ->assertSet('username', 'ana.popescu')
        ->assertSet('bio', 'I teach maths.')
        ->assertSet('country_code', 'RO')
        ->assertSet('avatar_preset', 4)
        ->assertSet('content_locales', ['ro-RO', 'en-GB']);
});

it('offers eight avatars to choose from', function () {
    $this->actingAs($this->user);

    $component = Volt::test('profile');

    foreach (range(1, 8) as $number) {
        $component->assertSeeHtml('aria-label="Avatar '.$number.'"');
    }
});

// Details

it('saves the profile details', function () {
    $this->actingAs($this->user);

    Volt::test('profile')
        ->set('first_name', 'Anca')
        ->set('last_name', 'Ionescu')
        ->set('username', 'anca.ionescu')
        ->set('bio', 'Maths in Cluj.')
        ->set('country_code', 'AT')
        ->set('avatar_preset', 7)
        ->call('saveProfile')
        ->assertHasNoErrors()
        ->assertDispatched('profile-saved');

    $user = $this->user->fresh();
    expect($user->name)->toBe('Anca Ionescu');
    expect($user->username)->toBe('anca.ionescu');
    expect($user->bio)->toBe('Maths in Cluj.');
    expect($user->country_code)->toBe('AT');
    expect($user->avatar_preset)->toBe(7);
});

it('shows the saved, normalised username back to the user', function () {
    $this->actingAs($this->user);

    Volt::test('profile')
        ->set('username', '  Ana.Maths ')
        ->call('saveProfile')
        ->assertSet('username', 'ana.maths');
});

it('shows validation errors and saves nothing', function () {
    $this->actingAs($this->user);

    Volt::test('profile')
        ->set('username', 'x')
        ->set('first_name', '')
        ->call('saveProfile')
        ->assertHasErrors(['username', 'first_name'])
        ->assertNotDispatched('profile-saved');

    expect($this->user->fresh()->username)->toBe('ana.popescu');
});

it('rejects a username that is already taken', function () {
    UserFactory::new()->create(['username' => 'taken']);
    $this->actingAs($this->user);

    Volt::test('profile')->set('username', 'taken')->call('saveProfile')->assertHasErrors(['username']);
});

// Interface language

it('changes the interface language and reloads the page in it', function () {
    $this->actingAs($this->user);

    Volt::test('profile')
        ->call('setInterfaceLocale', 'ro-RO')
        ->assertHasNoErrors()
        ->assertRedirect(route('profile'));

    expect($this->user->fresh()->interface_locale)->toBe('ro-RO');

    // The next page load is in Romanian.
    $this->actingAs($this->user->fresh())->get('/profile')
        ->assertSee('Limbile în care citesc și predau')
        ->assertSee('Salvează profilul');
});

it('only offers the languages the interface is translated into', function () {
    $this->actingAs($this->user);

    $html = Volt::test('profile')->html();

    expect(substr_count($html, 'name="interface_locale"'))->toBe(3);
});

it('refuses an interface language that is not translated', function () {
    $this->actingAs($this->user);

    Volt::test('profile')->call('setInterfaceLocale', 'fr-FR')->assertHasErrors(['interface_locale']);

    expect($this->user->fresh()->interface_locale)->toBe('en-GB');
});

// Languages I read and teach

it('adds a language at the end of the list', function () {
    $this->actingAs($this->user);

    Volt::test('profile')
        ->set('add_locale', 'de-DE')
        ->call('addContentLocale')
        ->assertHasNoErrors()
        ->assertSet('content_locales', ['ro-RO', 'en-GB', 'de-DE'])
        ->assertSet('add_locale', '');

    expect(contentLocalesFor($this->user))->toBe(['ro-RO', 'en-GB', 'de-DE']);
});

it('does not offer a language the user already has', function () {
    $this->actingAs($this->user);

    preg_match_all('/<option[^>]*value="([^"]*)"/s', Volt::test('profile')->html(), $matches);
    $offered = $matches[1];

    expect($offered)->toContain('de-DE', 'es-ES');
    expect($offered)->not->toContain('ro-RO');
    expect($offered)->not->toContain('en-GB');
});

it('refuses to add nothing', function () {
    $this->actingAs($this->user);

    Volt::test('profile')->set('add_locale', '')->call('addContentLocale')->assertHasErrors();

    expect(contentLocalesFor($this->user))->toBe(['ro-RO', 'en-GB']);
});

it('removes a language', function () {
    $this->actingAs($this->user);

    Volt::test('profile')
        ->call('removeContentLocale', 'ro-RO')
        ->assertSet('content_locales', ['en-GB']);

    expect(contentLocalesFor($this->user))->toBe(['en-GB']);
});

it('will not remove the last language, and says why', function () {
    app(ReplaceContentLocales::class)->execute($this->user, ['ro-RO']);
    $this->actingAs($this->user);

    Volt::test('profile')
        ->call('removeContentLocale', 'ro-RO')
        ->assertHasErrors(['locales'])
        ->assertSee('At least one language must remain.')
        ->assertSet('content_locales', ['ro-RO']);

    expect(contentLocalesFor($this->user))->toBe(['ro-RO']);
});

it('moves a language to a new position', function () {
    app(ReplaceContentLocales::class)->execute($this->user, ['ro-RO', 'en-GB', 'de-DE']);
    $this->actingAs($this->user);

    Volt::test('profile')
        ->call('moveContentLocale', 'de-DE', 0)
        ->assertSet('content_locales', ['de-DE', 'ro-RO', 'en-GB'])
        ->call('moveContentLocale', 'de-DE', 2)
        ->assertSet('content_locales', ['ro-RO', 'en-GB', 'de-DE'])
        ->call('moveContentLocale', 'ro-RO', 1)
        ->assertSet('content_locales', ['en-GB', 'ro-RO', 'de-DE']);

    expect(contentLocalesFor($this->user))->toBe(['en-GB', 'ro-RO', 'de-DE']);
});

it('keeps a move inside the list when the position is out of range', function () {
    $this->actingAs($this->user);

    Volt::test('profile')
        ->call('moveContentLocale', 'ro-RO', 99)
        ->assertSet('content_locales', ['en-GB', 'ro-RO'])
        ->call('moveContentLocale', 'ro-RO', -5)
        ->assertSet('content_locales', ['ro-RO', 'en-GB']);
});

it('labels the languages in their own language, with the English name beside', function () {
    $this->actingAs($this->user);

    Volt::test('profile')
        ->assertSeeHtml('<span lang="ro" class="font-medium">Română</span>')
        ->assertSee('Romanian')
        ->assertSee('Topics are shown in the first of these that has a name.');
});

it('only ever changes the signed-in user\'s own languages', function () {
    $other = UserFactory::new()->create();
    app(ReplaceContentLocales::class)->execute($other, ['es-ES']);
    $this->actingAs($this->user);

    Volt::test('profile')->call('removeContentLocale', 'ro-RO');

    expect(contentLocalesFor($other))->toBe(['es-ES']);
});

it('shows a flag beside each language, in the interface list and in the content list', function () {
    $this->actingAs($this->user);

    $html = Volt::test('profile')->html();

    // 3 interface languages, 2 content languages, plus the user's country (RO).
    expect(substr_count($html, 'svg/flags.svg#flag-'))->toBe(6);
    expect($html)->toContain('aria-label="Romanian flag"')->toContain('aria-label="Romania flag"');
});

it('shows the flag of the chosen country beside the country picker as it changes', function () {
    $this->actingAs($this->user);

    $component = Volt::test('profile')->set('country_code', 'AT');
    expect($component->html())->toContain('aria-label="Austria flag"');

    $component->set('country_code', '');
    expect($component->html())->not->toContain('aria-label="Austria flag"')->not->toContain('aria-label="Romania flag"');
});
