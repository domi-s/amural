<?php

use Database\Factories\UserFactory;
use Database\Seeders\LocaleSeeder;

const ENGLISH_LINE = "A shared commons of lessons and resources for Europe's teachers and learners.";
const SPANISH_LINE = 'Un espacio común de lecciones y recursos para los docentes y estudiantes de Europa.';
const ROMANIAN_LINE = 'Un spațiu comun de lecții și resurse pentru profesorii și elevii din Europa.';

beforeEach(function () {
    $this->seed(LocaleSeeder::class);
});

it('shows the interface in English by default', function () {
    $this->get('/')->assertOk()->assertSee(ENGLISH_LINE)->assertSee('<html lang="en"', false);
});

it('follows a guest\'s Accept-Language header', function () {
    $this->get('/', ['Accept-Language' => 'ro-RO,ro;q=0.9,en;q=0.8'])
        ->assertOk()
        ->assertSee(ROMANIAN_LINE)
        ->assertSee('<html lang="ro"', false);

    $this->get('/', ['Accept-Language' => 'es'])->assertSee(SPANISH_LINE);
});

it('keeps English for a browser language the interface is not translated into', function () {
    $this->get('/', ['Accept-Language' => 'fr-FR,fr;q=0.9'])->assertSee(ENGLISH_LINE);
});

it('uses a guest\'s session choice of interface language over the browser', function () {
    $this->withSession(['interface_locale' => 'es-ES'])
        ->get('/', ['Accept-Language' => 'ro'])
        ->assertSee(SPANISH_LINE);
});

it('uses a signed-in user\'s interface language over the browser', function () {
    $user = UserFactory::new()->create(['interface_locale' => 'ro-RO']);

    $this->actingAs($user)
        ->get('/', ['Accept-Language' => 'es'])
        ->assertSee(ROMANIAN_LINE);
});

it('remembers a valid ?lang= in the session as the content language', function () {
    $this->get('/?lang=ro-RO')->assertSessionHas('content_locale', 'ro-RO');
});

it('ignores an invalid ?lang=', function () {
    $this->get('/?lang=xx-XX')->assertSessionMissing('content_locale');
});

it('does not change the interface language when only the content language is chosen', function () {
    $this->get('/?lang=ro-RO')->assertSee(ENGLISH_LINE);
});
