<?php

use App\Actions\Concepts\UpdateConcept;
use App\Actions\Concepts\UpdateConceptLabel;
use App\Enums\ConceptKind;
use App\Enums\ConceptStatus;
use App\Enums\LabelStatus;
use App\Enums\Stage;
use App\Models\Concept;
use App\Models\ConceptLabel;
use App\Models\ConceptLabelRevision;
use Database\Factories\ConceptFactory;
use Database\Factories\UserFactory;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LocaleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->seed([LocaleSeeder::class, CountrySeeder::class]);

    $this->concept = ConceptFactory::new()->create();
    app(UpdateConceptLabel::class)->execute($this->concept, 'en-GB', ['pref' => 'Fractions', 'definition' => 'Parts of a whole.']);
    $this->teacher = UserFactory::new()->create();
});

function editorUrl(Concept $concept, string $locale): string
{
    return "/topic/{$concept->public_id}/edit/{$locale}";
}

it('sends guests to sign in', function () {
    $this->get(editorUrl($this->concept, 'ro-RO'))->assertRedirect('/login');
});

it('shows the language row and the reference language beside the form', function () {
    $this->actingAs($this->teacher)->get(editorUrl($this->concept, 'ro-RO'))
        ->assertOk()
        ->assertSee('Parts of a whole.')
        ->assertSee('1 of 26 languages · 0 reviewed')
        ->assertSee('Needs checking');
});

it('is not found for a language that does not exist', function () {
    $this->actingAs($this->teacher)->get(editorUrl($this->concept, 'xx-XX'))->assertNotFound();
});

it('adds a name in a new language and records it in the history', function () {
    Volt::actingAs($this->teacher)
        ->test('topic-editor', ['concept' => $this->concept, 'locale' => 'ro-RO'])
        ->set('pref', 'Fracții')
        ->set('newAlt', 'numere raționale')
        ->set('note', 'First Romanian name')
        ->call('save')
        ->assertSet('result', 'saved');

    $label = ConceptLabel::where('concept_id', $this->concept->id)->where('locale_code', 'ro-RO')->firstOrFail();
    expect($label->alt)->toBe(['numere raționale'])
        ->and($label->status)->toBe(LabelStatus::Draft);

    $revision = ConceptLabelRevision::where('concept_label_id', $label->id)->sole();
    expect($revision->note)->toBe('First Romanian name')
        ->and($revision->author_id)->toBe($this->teacher->id);
});

it('says so when nothing changed and writes no revision', function () {
    $before = ConceptLabelRevision::count();

    Volt::actingAs($this->teacher)
        ->test('topic-editor', ['concept' => $this->concept, 'locale' => 'en-GB'])
        ->call('save')
        ->assertSet('result', 'unchanged');

    expect(ConceptLabelRevision::count())->toBe($before);
});

it('shows the validation message for a missing name', function () {
    Volt::actingAs($this->teacher)
        ->test('topic-editor', ['concept' => $this->concept, 'locale' => 'ro-RO'])
        ->call('save')
        ->assertHasErrors('pref');
});

it('keeps other names as a list that can be added to and removed from', function () {
    Volt::actingAs($this->teacher)
        ->test('topic-editor', ['concept' => $this->concept, 'locale' => 'ro-RO'])
        ->set('newAlt', 'one')->call('addAlt')
        ->set('newAlt', 'two')->call('addAlt')
        ->set('newAlt', 'one')->call('addAlt')
        ->assertSet('alt', ['one', 'two'])
        ->call('removeAlt', 0)
        ->assertSet('alt', ['two']);
});

it('offers "mark as reviewed" to curators only, and a teacher cannot review', function () {
    $this->actingAs($this->teacher)->get(editorUrl($this->concept, 'en-GB'))->assertDontSee('Mark as reviewed');
    $this->actingAs(UserFactory::new()->curator()->create())->get(editorUrl($this->concept, 'en-GB'))->assertSee('Mark as reviewed');

    Volt::actingAs($this->teacher)
        ->test('topic-editor', ['concept' => $this->concept, 'locale' => 'en-GB'])
        ->set('pref', 'Fractions!')->set('markReviewed', true)->call('save');

    expect($this->concept->labels()->where('locale_code', 'en-GB')->value('status'))->toBe(LabelStatus::Draft);
});

it('lets a curator review a name', function () {
    Volt::actingAs(UserFactory::new()->curator()->create())
        ->test('topic-editor', ['concept' => $this->concept, 'locale' => 'en-GB'])
        ->set('markReviewed', true)
        ->call('save');

    expect($this->concept->labels()->where('locale_code', 'en-GB')->value('status'))->toBe(LabelStatus::Reviewed);
});

it('links the topic page to the editor', function () {
    $this->actingAs($this->teacher)->get("/topic/{$this->concept->public_id}/fractions")->assertSee('/edit/', false);
});

// What the topic is: kind, stages and status, for curators on the default language

it('shows the topic details to curators on the default language only', function () {
    $curator = UserFactory::new()->curator()->create();

    $this->actingAs($curator)->get(editorUrl($this->concept, 'en-GB'))->assertSee('About the topic');
    $this->actingAs($curator)->get(editorUrl($this->concept, 'ro-RO'))->assertDontSee('About the topic');
    $this->actingAs($this->teacher)->get(editorUrl($this->concept, 'en-GB'))->assertDontSee('About the topic');
});

it('lets a curator change the kind, the stages and the status', function () {
    Volt::actingAs(UserFactory::new()->curator()->create())
        ->test('topic-editor', ['concept' => $this->concept, 'locale' => 'en-GB'])
        ->set('kind', 'procedure')
        ->set('stageFrom', 'primary')
        ->set('stageTo', 'lower_secondary')
        ->set('conceptStatus', 'reviewed')
        ->call('saveDetails')
        ->assertSet('detailsSaved', true);

    $concept = $this->concept->fresh();
    expect($concept->kind)->toBe(ConceptKind::Procedure)
        ->and($concept->stage_from)->toBe(Stage::Primary)
        ->and($concept->stage_to)->toBe(Stage::LowerSecondary)
        ->and($concept->status)->toBe(ConceptStatus::Reviewed);
});

it('refuses stages in the wrong order, and a status a curator cannot set here', function () {
    $editor = Volt::actingAs(UserFactory::new()->curator()->create())
        ->test('topic-editor', ['concept' => $this->concept, 'locale' => 'en-GB']);

    $editor->set('stageFrom', 'graduate')->set('stageTo', 'primary')->call('saveDetails')->assertHasErrors('stage_to');
    $editor->set('stageFrom', 'primary')->set('stageTo', 'graduate')->set('conceptStatus', 'deprecated')->call('saveDetails')->assertHasErrors('status');
});

it('does not let a teacher change the topic details', function () {
    expect(fn () => app(UpdateConcept::class)->execute($this->concept, $this->teacher, [
        'kind' => 'procedure', 'stage_from' => 'primary', 'stage_to' => 'graduate', 'status' => 'reviewed',
    ]))->toThrow(AuthorizationException::class);
});
