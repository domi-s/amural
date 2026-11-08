<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $this->seed(\Database\Seeders\LocaleSeeder::class);

        $response = Volt::test('auth.register')
            ->set('first_name', 'Test')
            ->set('last_name', 'User')
            ->set('email', 'test@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register');

        $response
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_new_users_start_in_english_when_the_browser_gives_no_preference(): void
    {
        $this->seed(\Database\Seeders\LocaleSeeder::class);

        Volt::test('auth.register')
            ->set('first_name', 'Test')
            ->set('last_name', 'User')
            ->set('email', 'test@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertHasNoErrors();

        $user = \App\Models\User::where('email', 'test@example.com')->firstOrFail();

        $this->assertSame('en-GB', $user->interface_locale);
        $this->assertSame(['en-GB'], $user->contentLocales()->pluck('locale_code')->all());
    }

    public function test_new_users_start_in_the_language_their_browser_asks_for(): void
    {
        $this->seed(\Database\Seeders\LocaleSeeder::class);

        // The Livewire test harness builds its own request for the component call, so hand the
        // resolver the request a Romanian browser would send.
        $request = \Illuminate\Http\Request::create('/register', 'GET', [], [], [], [
            'HTTP_ACCEPT_LANGUAGE' => 'ro-RO,ro;q=0.9,en;q=0.8',
        ]);
        $this->app->instance(\App\Support\LocaleResolver::class, new \App\Support\LocaleResolver($request));

        Volt::test('auth.register')
            ->set('first_name', 'Ana')
            ->set('last_name', 'Popescu')
            ->set('email', 'ana@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertHasNoErrors();

        $user = \App\Models\User::where('email', 'ana@example.com')->firstOrFail();

        $this->assertSame('ro-RO', $user->interface_locale);
        $this->assertSame(['ro-RO'], $user->contentLocales()->pluck('locale_code')->all());
    }
}
