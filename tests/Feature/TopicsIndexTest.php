<?php

use App\Actions\Concepts\CreateConceptEdge;
use App\Actions\Concepts\UpdateConceptLabel;
use App\Enums\ConceptStatus;
use App\Enums\EdgeStatus;
use App\Enums\EdgeType;
use App\Enums\LabelStatus;
use App\Enums\LessonStatus;
use App\Models\Concept;
use Database\Factories\ConceptFactory;
use Database\Factories\LessonFactory;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LocaleSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed([LocaleSeeder::class, CountrySeeder::class]);
});

afterEach(function () {
    Model::preventLazyLoading(false);
});

/**
 * @param  array<string, string>  $names  locale => name
 * @param  array<string, mixed>  $attributes
 */
function browseTopic(array $names, array $attributes = []): Concept
{
    $concept = ConceptFactory::new()->create(array_merge(['status' => ConceptStatus::Draft], $attributes));

    foreach ($names as $locale => $pref) {
        app(UpdateConceptLabel::class)->execute($concept, $locale, ['pref' => $pref], null, LabelStatus::Reviewed);
    }

    return $concept;
}

function browseParent(Concept $child, Concept $parent, EdgeStatus $status = EdgeStatus::Reviewed): void
{
    app(CreateConceptEdge::class)->execute($child, $parent, EdgeType::Broader, ['status' => $status]);
}

it('is public and linked from the header of every public page', function () {
    $this->get('/topics')->assertOk()->assertSee('Topics')->assertSeeLivewire(App\Livewire\LanguageSwitcher::class);

    $topic = browseTopic(['en-GB' => 'Number']);
    $this->get("/topic/{$topic->public_id}/number")->assertSee(route('topics.index'), false);
});

it('lists top-level topics as cards linking to their pages', function () {
    $number = browseTopic(['en-GB' => 'Number']);
    $algebra = browseTopic(['en-GB' => 'Algebra']);

    $this->get('/topics')
        ->assertOk()
        ->assertSee('Number')
        ->assertSee('Algebra')
        ->assertSee("/topic/{$number->public_id}/number", false)
        ->assertSee("/topic/{$algebra->public_id}/algebra", false)
        // The subject icon is decorative here: the topic's name is right beside it.
        ->assertSeeHtml('size-10 shrink-0')
        ->assertSeeHtml('aria-hidden="true"');
});

it('keeps topics in alphabetical order in the visitor\'s language', function () {
    browseTopic(['en-GB' => 'Zeta', 'ro-RO' => 'Alfa']);
    browseTopic(['en-GB' => 'Beta', 'ro-RO' => 'Zulu']);

    $this->get('/topics')->assertSeeInOrder(['Beta', 'Zeta']);
    $this->get('/topics', ['Accept-Language' => 'ro'])->assertSeeInOrder(['Alfa', 'Zulu']);
});

it('shows a topic under its parent, not as a card of its own', function () {
    $fractions = browseTopic(['en-GB' => 'Fractions']);
    $adding = browseTopic(['en-GB' => 'Adding fractions']);
    browseParent($adding, $fractions);

    $html = $this->get('/topics')->content();

    // One card (Fractions), with Adding fractions inside it.
    expect(substr_count($html, '<article'))->toBe(1);
    expect($html)->toContain('Adding fractions')->toContain('<details');
});

it('lets a card expand to the narrower topics inside it', function () {
    $fractions = browseTopic(['en-GB' => 'Fractions']);
    foreach (['Equivalent fractions', 'Adding fractions'] as $name) {
        browseParent(browseTopic(['en-GB' => $name]), $fractions);
    }

    $html = $this->get('/topics')->content();

    expect($html)->not->toMatch('/<details[^>]*\sopen/');
    expect($html)->toContain('Inside this');
    // Sorted by name, with the count beside the label.
    $this->get('/topics')->assertSeeInOrder(['Inside this', '2', 'Adding fractions', 'Equivalent fractions']);
});

it('has no expander for a topic with nothing inside it', function () {
    browseTopic(['en-GB' => 'Lonely topic']);

    $this->get('/topics')->assertSee('Lonely topic')->assertDontSee('<details', false);
});

it('treats a topic whose only parent link is still proposed as top level', function () {
    $parent = browseTopic(['en-GB' => 'Parent topic']);
    $child = browseTopic(['en-GB' => 'Proposed child']);
    browseParent($child, $parent, EdgeStatus::MachineProposed);

    $html = $this->get('/topics')->content();

    expect(substr_count($html, '<article'))->toBe(2);
});

it('leaves out machine-proposed and deprecated topics, which would drown the page', function () {
    browseTopic(['en-GB' => 'Reviewed topic'], ['status' => ConceptStatus::Reviewed]);
    browseTopic(['en-GB' => 'Imported topic'], ['status' => ConceptStatus::MachineProposed]);
    browseTopic(['en-GB' => 'Retired topic'], ['status' => ConceptStatus::Deprecated]);

    $this->get('/topics')->assertSee('Reviewed topic')->assertDontSee('Imported topic')->assertDontSee('Retired topic');
});

it('leaves a deprecated child out of its parent', function () {
    $parent = browseTopic(['en-GB' => 'Parent']);
    browseParent(browseTopic(['en-GB' => 'Live child']), $parent);
    browseParent(browseTopic(['en-GB' => 'Retired child'], ['status' => ConceptStatus::Deprecated]), $parent);

    $this->get('/topics')->assertSee('Live child')->assertDontSee('Retired child');
});

it('counts topics, the share that are reviewed, and published lessons', function () {
    browseTopic(['en-GB' => 'One'], ['status' => ConceptStatus::Reviewed]);
    browseTopic(['en-GB' => 'Two'], ['status' => ConceptStatus::Reviewed]);
    browseTopic(['en-GB' => 'Three'], ['status' => ConceptStatus::Draft]);
    browseTopic(['en-GB' => 'Four'], ['status' => ConceptStatus::MachineProposed]);
    browseTopic(['en-GB' => 'Gone'], ['status' => ConceptStatus::Deprecated]);

    LessonFactory::new()->create(['status' => LessonStatus::Published]);
    LessonFactory::new()->create(['status' => LessonStatus::Published]);
    LessonFactory::new()->create(['status' => LessonStatus::Draft]);

    // 4 live topics (the deprecated one is not counted), 2 of them reviewed: 50%; 2 published lessons.
    $this->get('/topics')->assertSeeInOrder(['Topics', '4', 'Reviewed', '50%', 'Lessons', '2']);
});

it('shows zero without dividing by zero when there are no topics', function () {
    $this->get('/topics')->assertOk()->assertSee('0%')->assertSee('No topics yet.');
});

it('writes card titles in the display font only when it can draw them', function () {
    browseTopic(['en-GB' => 'Geometry']);
    browseTopic(['ro-RO' => 'Algebră']);

    $html = $this->get('/topics')->content();

    expect($html)->toMatch('/class="font-display italic[^"]*"[^>]*>Geometry</');
    expect($html)->toMatch('/class="font-semibold[^"]*"[^>]*>Algebră</');
});

it('marks a card title in another language than the page', function () {
    browseTopic(['ro-RO' => 'Numere']);

    $this->get('/topics')->assertSeeHtml('lang="ro"');
});

it('makes the same number of queries however many topics there are', function () {
    $addParents = function (int $from, int $to): void {
        foreach (range($from, $to) as $i) {
            $parent = browseTopic(['en-GB' => "Parent {$i}", 'ro-RO' => "Părinte {$i}"]);

            foreach (range(1, 3) as $j) {
                browseParent(browseTopic(['en-GB' => "Child {$i}-{$j}"]), $parent);
            }
        }
    };

    $measure = function (): int {
        Model::preventLazyLoading();
        DB::flushQueryLog();
        DB::enableQueryLog();

        test()->get('/topics', ['Accept-Language' => 'ro'])->assertOk();

        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();
        Model::preventLazyLoading(false);

        return $queries;
    };

    $addParents(1, 2);
    $few = $measure();

    $addParents(3, 20);
    $many = $measure();

    expect($many)->toBe($few);
});
