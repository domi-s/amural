<?php

use App\Actions\Concepts\UpdateConceptLabel;
use App\Actions\Locales\ReplaceContentLocales;
use App\Enums\ConceptStatus;
use App\Enums\LabelStatus;
use App\Models\Concept;
use App\Models\ConceptReplacement;
use Database\Factories\ConceptFactory;
use Database\Factories\UserFactory;
use Database\Seeders\LocaleSeeder;

beforeEach(function () {
    $this->seed(LocaleSeeder::class);
});

/**
 * A concept with a name in each given locale: ['en-GB' => 'Fractions', 'ro-RO' => 'Fracții'].
 *
 * @param  array<string, string>  $names
 */
function urlTopic(array $names = [], array $attributes = []): Concept
{
    $concept = ConceptFactory::new()->create($attributes);

    foreach ($names as $locale => $pref) {
        app(UpdateConceptLabel::class)->execute($concept, $locale, ['pref' => $pref], null, LabelStatus::Reviewed);
    }

    return $concept;
}

function replaced(Concept $old, Concept ...$by): void
{
    $old->update(['status' => ConceptStatus::Deprecated]);

    foreach ($by as $new) {
        ConceptReplacement::create(['concept_id' => $old->id, 'replaced_by_id' => $new->id, 'reason' => 'merged']);
    }
}

// Rule 4: a missing or wrong slug redirects (301) to the canonical URL.

it('shows a topic at its canonical URL', function () {
    $topic = urlTopic(['en-GB' => 'Adding fractions']);

    $this->get("/topic/{$topic->public_id}/adding-fractions")->assertOk()->assertSee('Adding fractions');
});

it('redirects a missing slug to the canonical URL with a 301', function () {
    $topic = urlTopic(['en-GB' => 'Adding fractions']);

    $this->get("/topic/{$topic->public_id}")
        ->assertStatus(301)
        ->assertRedirect(url("/topic/{$topic->public_id}/adding-fractions"));
});

it('redirects a wrong slug to the canonical URL with a 301', function () {
    $topic = urlTopic(['en-GB' => 'Adding fractions']);

    $this->get("/topic/{$topic->public_id}/old-name")
        ->assertStatus(301)
        ->assertRedirect(url("/topic/{$topic->public_id}/adding-fractions"));
});

it('takes the canonical slug from the language the visitor reads', function () {
    $topic = urlTopic(['en-GB' => 'Adding fractions', 'ro-RO' => 'Adunarea fracțiilor', 'es-ES' => 'Suma de fracciones']);

    $this->get("/topic/{$topic->public_id}", ['Accept-Language' => 'ro-RO,ro;q=0.9'])
        ->assertRedirect(url("/topic/{$topic->public_id}/adunarea-fractiilor"));

    $this->get("/topic/{$topic->public_id}", ['Accept-Language' => 'es'])
        ->assertRedirect(url("/topic/{$topic->public_id}/suma-de-fracciones"));
});

it('does not redirect an English slug for a Romanian reader, it moves it to the Romanian one', function () {
    $topic = urlTopic(['en-GB' => 'Adding fractions', 'ro-RO' => 'Adunarea fracțiilor']);

    $this->get("/topic/{$topic->public_id}/adding-fractions", ['Accept-Language' => 'ro'])
        ->assertStatus(301)
        ->assertRedirect(url("/topic/{$topic->public_id}/adunarea-fractiilor"));
});

it('uses ?lang= to choose the slug, and keeps the query on the redirect', function () {
    $topic = urlTopic(['en-GB' => 'Adding fractions', 'es-ES' => 'Suma de fracciones']);

    $this->get("/topic/{$topic->public_id}?lang=es-ES&proposed=1")
        ->assertStatus(301)
        ->assertRedirect(url("/topic/{$topic->public_id}/suma-de-fracciones?lang=es-ES&proposed=1"));
});

it('transliterates the slug', function () {
    $topic = urlTopic(['en-GB' => 'Adding fractions', 'de-DE' => 'Brüche addieren', 'el-GR' => 'Γεωμετρία']);
    $user = UserFactory::new()->create();
    app(ReplaceContentLocales::class)->execute($user, ['el-GR', 'de-DE']);

    $this->actingAs($user)->get("/topic/{$topic->public_id}")
        ->assertRedirect(url("/topic/{$topic->public_id}/geometria"));
});

it('falls back to another language\'s slug when the first choice has no name', function () {
    $topic = urlTopic(['en-GB' => 'Adding fractions']);

    $this->get("/topic/{$topic->public_id}", ['Accept-Language' => 'ro'])
        ->assertRedirect(url("/topic/{$topic->public_id}/adding-fractions"));
});

it('shows a topic with no name in any language, and sends any slug to the plain URL', function () {
    $topic = urlTopic();

    $this->get("/topic/{$topic->public_id}")->assertOk();
    $this->get("/topic/{$topic->public_id}/anything")->assertStatus(301)->assertRedirect(url("/topic/{$topic->public_id}"));
});

it('returns 404 for a topic that does not exist', function () {
    $this->get('/topic/c_NOPE0000/nothing')->assertNotFound();
});

// Rule 4: a deprecated topic redirects (301) to its replacement.

it('redirects a deprecated topic to its replacement, in the visitor\'s language', function () {
    $old = urlTopic(['en-GB' => 'Old fractions']);
    $new = urlTopic(['en-GB' => 'Fractions', 'ro-RO' => 'Fracții']);
    replaced($old, $new);

    $this->get("/topic/{$old->public_id}/old-fractions")
        ->assertStatus(301)
        ->assertRedirect(url("/topic/{$new->public_id}/fractions"));

    $this->get("/topic/{$old->public_id}", ['Accept-Language' => 'ro'])
        ->assertRedirect(url("/topic/{$new->public_id}/fractii"));
});

it('keeps the query when redirecting to a replacement', function () {
    $old = urlTopic(['en-GB' => 'Old']);
    $new = urlTopic(['en-GB' => 'New', 'es-ES' => 'Nuevo']);
    replaced($old, $new);

    $this->get("/topic/{$old->public_id}/old?lang=es-ES")
        ->assertRedirect(url("/topic/{$new->public_id}/nuevo?lang=es-ES"));
});

it('follows a chain of replacements to the live topic', function () {
    $first = urlTopic(['en-GB' => 'First']);
    $second = urlTopic(['en-GB' => 'Second']);
    $third = urlTopic(['en-GB' => 'Third']);
    replaced($first, $second);
    replaced($second, $third);

    $this->get("/topic/{$first->public_id}/first")->assertRedirect(url("/topic/{$third->public_id}/third"));
});

it('lists the replacements on a page when there are several', function () {
    $old = urlTopic(['en-GB' => 'Combined']);
    $a = urlTopic(['en-GB' => 'Part A']);
    $b = urlTopic(['en-GB' => 'Part B']);
    replaced($old, $a, $b);

    $this->get("/topic/{$old->public_id}/combined")
        ->assertOk()
        ->assertSee('This topic has been replaced')
        ->assertSee('Part A')
        ->assertSee('Part B')
        ->assertSee(url("/topic/{$a->public_id}/part-a"), false)
        ->assertSee(url("/topic/{$b->public_id}/part-b"), false);
});

it('lists only live replacements when one of several was itself replaced', function () {
    $old = urlTopic(['en-GB' => 'Combined']);
    $live = urlTopic(['en-GB' => 'Live part']);
    $gone = urlTopic(['en-GB' => 'Gone part']);
    $final = urlTopic(['en-GB' => 'Final part']);
    replaced($gone, $final);
    replaced($old, $live, $gone);

    $this->get("/topic/{$old->public_id}/combined")
        ->assertOk()
        ->assertSee('Live part')
        ->assertSee('Final part')
        ->assertDontSee('Gone part');
});

it('shows a deprecated topic with a notice when nothing replaces it', function () {
    $old = urlTopic(['en-GB' => 'Retired'], ['status' => ConceptStatus::Deprecated]);

    $this->get("/topic/{$old->public_id}/retired")->assertOk()->assertSee('This topic is deprecated.');
});

it('does not loop when topics replace each other', function () {
    $a = urlTopic(['en-GB' => 'A topic']);
    $b = urlTopic(['en-GB' => 'B topic']);
    replaced($a, $b);
    replaced($b, $a);

    $this->get("/topic/{$a->public_id}/a-topic")->assertOk()->assertSee('This topic is deprecated.');
});
