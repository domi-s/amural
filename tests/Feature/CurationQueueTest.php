<?php

use App\Actions\Concepts\UpdateConceptLabel;
use App\Enums\LabelStatus;
use App\Models\ConceptLabel;
use Database\Factories\ConceptFactory;
use Database\Factories\UserFactory;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LocaleSeeder;

beforeEach(function () {
    $this->seed([LocaleSeeder::class, CountrySeeder::class]);
    $this->curator = UserFactory::new()->curator()->create();
});

function queued(string $locale, string $name, LabelStatus $status, ?string $updated = null): ConceptLabel
{
    $label = app(UpdateConceptLabel::class)->execute(ConceptFactory::new()->create(), $locale, ['pref' => $name], status: $status);

    if ($updated) {
        $label->forceFill(['updated_at' => $updated])->saveQuietly();
    }

    return $label;
}

it('is for curators only', function () {
    $this->get('/curate')->assertRedirect('/login');
    $this->actingAs(UserFactory::new()->create())->get('/curate')->assertForbidden();
    $this->actingAs($this->curator)->get('/curate')->assertOk();
});

it('lists names that are machine or draft, oldest first, and leaves out reviewed ones', function () {
    queued('en-GB', 'Newer draft', LabelStatus::Draft, '2026-02-01');
    queued('ro-RO', 'Older machine', LabelStatus::Machine, '2026-01-01');
    queued('en-GB', 'Done already', LabelStatus::Reviewed);

    $this->actingAs($this->curator)->get('/curate')
        ->assertSeeInOrder(['Older machine', 'Newer draft'])
        ->assertDontSee('Done already');
});

it('filters by language and links each name to its editor', function () {
    $ro = queued('ro-RO', 'Fracții', LabelStatus::Draft);
    queued('en-GB', 'Fractions', LabelStatus::Draft);

    $this->actingAs($this->curator)->get('/curate?locale=ro-RO')
        ->assertSee('Fracții')
        ->assertDontSee('Fractions')
        ->assertSee("/topic/{$ro->concept->public_id}/edit/ro-RO", false);
});

it('ignores a language that has nothing waiting', function () {
    queued('en-GB', 'Fractions', LabelStatus::Draft);

    $this->actingAs($this->curator)->get('/curate?locale=xx-XX')->assertOk()->assertSee('Fractions');
});

it('says when nothing is waiting, and shows the queue link only to curators', function () {
    $this->actingAs($this->curator)->get('/curate')->assertSee('Nothing is waiting to be checked.')->assertSee('href="'.url('/curate').'"', false);

    $this->actingAs(UserFactory::new()->create())->get('/topics')->assertDontSee('/curate', false);
});

it('pages through a long queue', function () {
    foreach (range(1, 27) as $n) {
        queued('en-GB', "Name {$n}", LabelStatus::Draft, now()->subDays(100 - $n)->toDateString());
    }

    $this->actingAs($this->curator)->get('/curate')->assertSee('Name 25')->assertDontSee('Name 26');
    $this->actingAs($this->curator)->get('/curate?page=2')->assertSee('Name 26');
});
