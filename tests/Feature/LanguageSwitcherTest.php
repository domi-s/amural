<?php

use App\Actions\Locales\ReplaceContentLocales;
use App\Livewire\LanguageSwitcher;
use App\Models\UserContentLocale;
use Database\Factories\UserFactory;
use Database\Seeders\LocaleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(LocaleSeeder::class);
});

function readerOf(array $codes, string $interfaceLocale = 'en-GB')
{
    $user = UserFactory::new()->create(['interface_locale' => $interfaceLocale]);
    app(ReplaceContentLocales::class)->execute($user, $codes);

    return $user;
}

it('shows English as the current language to a guest with no preference', function () {
    Livewire::test(LanguageSwitcher::class)
        ->assertSeeHtml('aria-label="Language: English"');
});

it('shows a signed-in user\'s first content language as current', function () {
    $user = readerOf(['ro-RO', 'en-GB']);

    Livewire::actingAs($user)->test(LanguageSwitcher::class)
        ->assertSeeHtml('aria-label="Language: Română"');
});

it('lists all 26 languages with the launch languages first', function () {
    $component = Livewire::test(LanguageSwitcher::class);

    // Launch languages in sort order, then the rest.
    $component->assertSeeInOrder(['English', 'Română', 'Español', 'Български', 'Hrvatski', 'Íslenska']);

    // All 26 are listed; the one already showing needs no request, so 25 can be chosen.
    expect(substr_count($component->html(), 'wire:click="select('))->toBe(25);
});

it('labels each language in its own language, with the English name beside it', function () {
    Livewire::test(LanguageSwitcher::class)
        ->assertSeeHtml('<span lang="ro" class="font-medium">')
        ->assertSeeHtml('<span lang="bg" class="font-medium">')
        ->assertSee('Romanian')
        ->assertSee('Bulgarian');
});

it('does not repeat the English name when it is the same as the native name', function () {
    $html = Livewire::test(LanguageSwitcher::class)->html();

    expect($html)->not->toContain('<span class="text-xs opacity-70">English</span>');
    expect($html)->toContain('<span class="text-xs opacity-70">Romanian</span>');
});

it('sends no request for the language that is already showing', function () {
    $html = Livewire::test(LanguageSwitcher::class)->html();

    expect($html)->not->toContain("select('en-GB')");
    expect($html)->toContain("select('ro-RO')");
});

it('changes the interface too when the language is one the interface is translated into', function () {
    Livewire::test(LanguageSwitcher::class)->call('select', 'ro-RO');

    $this->get('/')
        ->assertSee('Un spațiu comun de lecții și resurse pentru profesorii și elevii din Europa.')
        ->assertSee('<html lang="ro"', false);
});

it('changes the interface language of a signed-in user too, and keeps it for next time', function () {
    $user = readerOf(['en-GB']);

    Livewire::actingAs($user)->test(LanguageSwitcher::class)->call('select', 'es-ES');

    expect($user->fresh()->interface_locale)->toBe('es-ES');
    $this->actingAs($user->fresh())->get('/')->assertSee('Un espacio común de lecciones y recursos');
});

it('leaves the interface alone for a language it is not translated into', function () {
    Livewire::test(LanguageSwitcher::class)->call('select', 'de-DE');

    expect(session('content_locale'))->toBe('de-DE');
    expect(session('interface_locale'))->toBeNull();
    $this->get('/')->assertSee('<html lang="en"', false);
});

it('reloads the whole page instead of navigating, so the new language applies everywhere', function () {
    $component = Livewire::test(LanguageSwitcher::class)->call('select', 'ro-RO');

    $component->assertRedirect('/');
    expect($component->effects)->not->toHaveKey('redirectUsingNavigate');
});

it('marks the current language', function () {
    $user = readerOf(['es-ES']);

    $html = Livewire::actingAs($user)->test(LanguageSwitcher::class)->html();

    expect(substr_count($html, 'aria-current="true"'))->toBe(1);
});

it('prepends the chosen language to a signed-in user\'s languages', function () {
    $user = readerOf(['ro-RO', 'en-GB']);

    Livewire::actingAs($user)->test(LanguageSwitcher::class)
        ->call('select', 'de-DE')
        ->assertHasNoErrors();

    expect(UserContentLocale::where('user_id', $user->id)->orderBy('position')->pluck('locale_code')->all())
        ->toBe(['de-DE', 'ro-RO', 'en-GB']);
});

it('stores the chosen language in the session for a guest', function () {
    Livewire::test(LanguageSwitcher::class)->call('select', 'ro-RO');

    expect(session('content_locale'))->toBe('ro-RO');
});

it('returns to the page the visitor was on, without a ?lang= that would override the choice', function () {
    session()->put('_previous.url', 'http://localhost/topic/c_ABCD1234/fractions?lang=es-ES&show=all');

    Livewire::test(LanguageSwitcher::class)
        ->call('select', 'ro-RO')
        ->assertRedirect('/topic/c_ABCD1234/fractions?show=all');
});

it('only ever redirects within the site', function () {
    session()->put('_previous.url', 'https://evil.example/phish?lang=es-ES');

    Livewire::test(LanguageSwitcher::class)
        ->call('select', 'ro-RO')
        ->assertRedirect('/phish');
});

it('redirects to the home page when there is no previous page', function () {
    Livewire::test(LanguageSwitcher::class)
        ->call('select', 'ro-RO')
        ->assertRedirect('/');
});

it('rejects an unknown language without changing anything', function () {
    $user = readerOf(['ro-RO']);

    Livewire::actingAs($user)->test(LanguageSwitcher::class)
        ->call('select', 'xx-XX')
        ->assertHasErrors(['locale']);

    expect(UserContentLocale::where('user_id', $user->id)->pluck('locale_code')->all())->toBe(['ro-RO']);
});

// It is in the header on every page: the landing page, the auth pages and the app.

it('is on the landing page', function () {
    $this->get('/')->assertOk()->assertSeeLivewire(LanguageSwitcher::class);
});

it('is on the sign-in and registration pages', function () {
    $this->get('/login')->assertOk()->assertSeeLivewire(LanguageSwitcher::class);
    $this->get('/register')->assertOk()->assertSeeLivewire(LanguageSwitcher::class);
});

it('is in the app for a signed-in user', function () {
    $this->actingAs(UserFactory::new()->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertSeeLivewire(LanguageSwitcher::class);
});

it('is labelled in the interface language', function () {
    $this->get('/', ['Accept-Language' => 'ro'])->assertSee('Limbă:', false);
    $this->get('/', ['Accept-Language' => 'es'])->assertSee('Idioma:', false);
});
