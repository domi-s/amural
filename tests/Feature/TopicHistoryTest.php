<?php

use App\Actions\Concepts\UpdateConceptLabel;
use App\Models\ConceptLabelRevision;
use Database\Factories\ConceptFactory;
use Database\Factories\UserFactory;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LocaleSeeder;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->seed([LocaleSeeder::class, CountrySeeder::class]);

    $this->author = UserFactory::new()->create(['first_name' => 'Ana', 'last_name' => 'Popescu']);
    $this->concept = ConceptFactory::new()->create();
    $update = app(UpdateConceptLabel::class);
    $update->execute($this->concept, 'en-GB', ['pref' => 'Fractions', 'definition' => 'Parts of a whole.'], $this->author, note: 'First version');
    $update->execute($this->concept, 'en-GB', ['pref' => 'Fractions', 'definition' => 'Parts of the whole.'], $this->author, note: 'Fixed the article');
});

it('is readable by anyone, newest revision first, with who and why', function () {
    $this->get("/topic/{$this->concept->public_id}/history?locale=en-GB")
        ->assertOk()
        ->assertSeeInOrder(['Fixed the article', 'First version'])
        ->assertSee('Ana Popescu')
        ->assertSee('Current version');
});

it('shows what changed since the revision before it, word by word', function () {
    $this->get("/topic/{$this->concept->public_id}/history?locale=en-GB")
        ->assertSee('<del', false)
        ->assertSee('<ins', false)
        ->assertSeeInOrder(['<del', '>a</del>', '<ins', '>the</ins>'], false);
});

it('offers to restore only to people who are signed in', function () {
    $this->get("/topic/{$this->concept->public_id}/history?locale=en-GB")->assertDontSee('Restore this version');
    $this->actingAs($this->author)->get("/topic/{$this->concept->public_id}/history?locale=en-GB")->assertSee('Restore this version');
});

it('restores an old version as a new revision and keeps the history', function () {
    $first = ConceptLabelRevision::orderBy('id')->first();

    Volt::actingAs($this->author)
        ->test('topic-history', ['concept' => $this->concept])
        ->call('restore', $first->id);

    $label = $this->concept->labels()->first();
    expect($label->definition)->toBe('Parts of a whole.')
        ->and(ConceptLabelRevision::count())->toBe(3)
        ->and(ConceptLabelRevision::latest('id')->first()->note)->toContain('Restored the version of');
});

it('does not restore a revision of another topic', function () {
    $other = ConceptFactory::new()->create();
    app(UpdateConceptLabel::class)->execute($other, 'en-GB', ['pref' => 'Triangles']);
    $foreign = ConceptLabelRevision::latest('id')->first();

    Volt::actingAs($this->author)
        ->test('topic-history', ['concept' => $this->concept])
        ->call('restore', $foreign->id)
        ->assertNotFound();
});

it('does not let a guest restore anything', function () {
    Volt::test('topic-history', ['concept' => $this->concept])
        ->call('restore', ConceptLabelRevision::orderBy('id')->value('id'))
        ->assertForbidden();
});

it('says so when the topic has no name yet', function () {
    $empty = ConceptFactory::new()->create();

    $this->get("/topic/{$empty->public_id}/history")->assertOk()->assertSee('no name in any language yet');
});

it('links the topic page to its history', function () {
    $this->get("/topic/{$this->concept->public_id}/fractions")->assertSee("/topic/{$this->concept->public_id}/history", false);
});
