<?php

use App\Actions\Concepts\CreateConceptEdge;
use App\Actions\Concepts\UpdateConceptLabel;
use App\Actions\Locales\ReplaceContentLocales;
use App\Enums\ConceptKind;
use App\Enums\ConceptStatus;
use App\Enums\EdgeStatus;
use App\Enums\EdgeStrength;
use App\Enums\EdgeType;
use App\Enums\LabelStatus;
use App\Enums\Stage;
use App\Models\Concept;
use App\Models\ConceptEdge;
use Database\Factories\ConceptFactory;
use Database\Factories\UserFactory;
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
 * @param  array<string, string|array{0: string, 1: LabelStatus}>  $names  locale => name, or [name, status]
 * @param  array<string, mixed>  $attributes
 */
function pageTopic(array $names, array $attributes = [], ?string $definition = null, array $alt = []): Concept
{
    $concept = ConceptFactory::new()->create($attributes);

    foreach ($names as $locale => $name) {
        [$pref, $status] = is_array($name) ? $name : [$name, LabelStatus::Reviewed];

        app(UpdateConceptLabel::class)->execute(
            $concept,
            $locale,
            ['pref' => $pref, 'alt' => $locale === 'en-GB' ? $alt : [], 'definition' => $definition],
            null,
            $status,
        );
    }

    return $concept;
}

function pageEdge(Concept $from, Concept $to, EdgeType $type, EdgeStatus $status = EdgeStatus::Reviewed, ?EdgeStrength $strength = null): ConceptEdge
{
    return app(CreateConceptEdge::class)->execute($from, $to, $type, ['status' => $status, 'strength' => $strength]);
}

function topicUrl(Concept $concept, string $slug, string $query = ''): string
{
    return "/topic/{$concept->public_id}/{$slug}{$query}";
}

// The header

it('is readable by anyone, without signing in', function () {
    $topic = pageTopic(['en-GB' => 'Adding fractions']);

    $this->get(topicUrl($topic, 'adding-fractions'))->assertOk()->assertSee('Adding fractions');
});

it('shows the name, other names, kind, stage and subject', function () {
    $topic = pageTopic(
        ['en-GB' => 'Adding fractions'],
        ['kind' => ConceptKind::Procedure, 'stage_from' => Stage::Primary, 'stage_to' => Stage::LowerSecondary],
        alt: ['fraction addition', 'summing fractions'],
    );

    $this->get(topicUrl($topic, 'adding-fractions'))
        ->assertOk()
        ->assertSeeInOrder(['<h1', 'Adding fractions', 'Also called', 'fraction addition, summing fractions'], false)
        ->assertSee('Procedure')
        ->assertSee('Primary – Lower secondary')
        ->assertSee('Mathematics')
        ->assertSeeHtml('aria-label="Mathematics"');
});

it('shows a single stage once', function () {
    $topic = pageTopic(['en-GB' => 'Counting'], ['stage_from' => Stage::EarlyPrimary, 'stage_to' => Stage::EarlyPrimary]);

    $this->get(topicUrl($topic, 'counting'))->assertSee('Early primary')->assertDontSee('Early primary –');
});

it('shows the definition, or says there is none yet', function () {
    $with = pageTopic(['en-GB' => 'Fractions'], definition: 'A number that names part of a whole.');
    $without = pageTopic(['en-GB' => 'Decimals']);

    $this->get(topicUrl($with, 'fractions'))->assertSee('A number that names part of a whole.');
    $this->get(topicUrl($without, 'decimals'))->assertSee('No definition yet.');
});

it('writes headings in Latin script in the display font and Greek and Cyrillic ones in the body font', function () {
    $latin = pageTopic(['en-GB' => 'Geometry']);
    $greek = pageTopic(['el-GR' => 'Γεωμετρία']);
    $bulgarian = pageTopic(['bg-BG' => 'Геометрия']);

    $heading = function (string $url): string {
        preg_match('/<h1[^>]*>/', test()->get($url)->content(), $match);

        return $match[0];
    };

    expect($heading(topicUrl($latin, 'geometry')))->toContain('font-display')->toContain('italic');
    expect($heading(topicUrl($greek, 'geometria', '?lang=el-GR')))->not->toContain('font-display')->toContain('font-semibold');
    expect($heading(topicUrl($bulgarian, 'geometriia', '?lang=bg-BG')))->not->toContain('font-display');
});

it('keeps a heading out of the display font when it contains letters that font cannot draw', function () {
    $spanish = pageTopic(['es-ES' => 'Números']);
    $romanian = pageTopic(['ro-RO' => 'Algebră']);
    $plainRomanian = pageTopic(['ro-RO' => 'Numere']);

    $heading = function (string $url): string {
        preg_match('/<h1[^>]*>/', test()->get($url)->content(), $match);

        return $match[0];
    };

    expect($heading(topicUrl($spanish, 'numeros', '?lang=es-ES')))->not->toContain('font-display');
    expect($heading(topicUrl($romanian, 'algebra', '?lang=ro-RO')))->not->toContain('font-display');
    expect($heading(topicUrl($plainRomanian, 'numere', '?lang=ro-RO')))->toContain('font-display');
});

it('marks text that is in a different language from the page with a lang attribute', function () {
    $topic = pageTopic(['en-GB' => 'Fractions', 'ro-RO' => 'Fracții']);

    $this->get(topicUrl($topic, 'fractii', '?lang=ro-RO'))->assertSeeHtml('lang="ro"');
    $this->get(topicUrl($topic, 'fractions'))->assertDontSeeHtml('<h1 lang=');
});

it('has one main heading, a skip link, and labelled navigation', function () {
    $topic = pageTopic(['en-GB' => 'Fractions']);

    $html = $this->get(topicUrl($topic, 'fractions'))->content();

    expect(substr_count($html, '<h1'))->toBe(1);
    expect($html)->toContain('href="#content"')->toContain('aria-label="Breadcrumb"');
});

it('has the language switcher in its header', function () {
    $topic = pageTopic(['en-GB' => 'Fractions']);

    $this->get(topicUrl($topic, 'fractions'))->assertSeeLivewire(App\Livewire\LanguageSwitcher::class);
});

// Fallback

it('says when the name is not yet in the visitor\'s language', function () {
    $topic = pageTopic(['en-GB' => 'Fractions']);

    $this->get(topicUrl($topic, 'fractions', '?lang=ro-RO'))
        ->assertSee('Not yet in Română · shown in English');
});

it('does not say that when the name is in the first-choice language', function () {
    $topic = pageTopic(['en-GB' => 'Fractions', 'ro-RO' => 'Fracții']);

    $this->get(topicUrl($topic, 'fractii', '?lang=ro-RO'))->assertDontSee('Not yet in');
});

// Breadcrumb

it('shows a breadcrumb from the first parent at each level, and other parents as also under', function () {
    $number = pageTopic(['en-GB' => 'Number']);
    $fractions = pageTopic(['en-GB' => 'Fractions']);
    $adding = pageTopic(['en-GB' => 'Adding fractions']);
    $algebra = pageTopic(['en-GB' => 'Algebra']);

    pageEdge($fractions, $number, EdgeType::Broader);
    pageEdge($adding, $fractions, EdgeType::Broader);
    pageEdge($adding, $algebra, EdgeType::Broader);

    $html = $this->get(topicUrl($adding, 'adding-fractions'))->content();

    expect($html)->toMatch('/Number<\/a>.*Fractions<\/a>.*aria-current="page"/s');
    expect($html)->toContain('Also under');
    expect($html)->toMatch('/Also under:.*Algebra/s');
});

it('stops a breadcrumb that loops', function () {
    $a = pageTopic(['en-GB' => 'Topic A']);
    $b = pageTopic(['en-GB' => 'Topic B']);
    pageEdge($a, $b, EdgeType::Broader);
    pageEdge($b, $a, EdgeType::Broader);

    $this->get(topicUrl($a, 'topic-a'))->assertOk();
});

// Structure

it('lists prerequisites with the required ones first, and what this topic leads to', function () {
    $topic = pageTopic(['en-GB' => 'Adding fractions']);
    $equivalent = pageTopic(['en-GB' => 'Equivalent fractions']);
    $helpfulOne = pageTopic(['en-GB' => 'Adding whole numbers']);
    $zebra = pageTopic(['en-GB' => 'Zebra prerequisite']);
    $next = pageTopic(['en-GB' => 'Subtracting fractions']);

    pageEdge($helpfulOne, $topic, EdgeType::Prerequisite, strength: EdgeStrength::Helpful);
    pageEdge($zebra, $topic, EdgeType::Prerequisite, strength: EdgeStrength::Required);
    pageEdge($equivalent, $topic, EdgeType::Prerequisite, strength: EdgeStrength::Required);
    pageEdge($topic, $next, EdgeType::Prerequisite);

    $this->get(topicUrl($topic, 'adding-fractions'))
        ->assertSeeInOrder(['Before this', 'Equivalent fractions', 'Zebra prerequisite', 'Adding whole numbers', 'Leads to', 'Subtracting fractions'])
        ->assertSee('helpful');
});

it('lists narrower topics inside, and related and special-case topics together', function () {
    $topic = pageTopic(['en-GB' => 'Quadrilaterals']);
    $square = pageTopic(['en-GB' => 'Square']);
    $rectangle = pageTopic(['en-GB' => 'Rectangle']);
    $polygon = pageTopic(['en-GB' => 'Polygon']);
    $perimeter = pageTopic(['en-GB' => 'Perimeter']);

    pageEdge($square, $topic, EdgeType::Broader);
    pageEdge($rectangle, $topic, EdgeType::Broader);
    pageEdge($topic, $polygon, EdgeType::SpecialCase);   // quadrilaterals are a special case of polygons
    pageEdge($square, $topic, EdgeType::SpecialCase);    // a square is a special case of quadrilaterals
    pageEdge($topic, $perimeter, EdgeType::Related);

    $html = $this->get(topicUrl($topic, 'quadrilaterals'))->content();

    expect($html)->toMatch('/Inside this.*Rectangle.*Square/s');
    expect($html)->toMatch('/Related.*Perimeter.*Polygon.*Square/s');
    // A topic that is both inside and a special case is listed once in each list, not twice in one.
    expect(substr_count($html, '>Square</a>'))->toBe(2);
});

it('links to each topic at its canonical URL in the visitor\'s language', function () {
    $topic = pageTopic(['en-GB' => 'Fractions', 'ro-RO' => 'Fracții']);
    $child = pageTopic(['en-GB' => 'Adding fractions', 'ro-RO' => 'Adunarea fracțiilor']);
    pageEdge($child, $topic, EdgeType::Broader);

    $this->get(topicUrl($topic, 'fractii', '?lang=ro-RO'))
        ->assertSee(url("/topic/{$child->public_id}/adunarea-fractiilor"), false)
        ->assertSee('Adunarea fracțiilor');
});

it('shows only reviewed links unless proposed ones are asked for', function () {
    $topic = pageTopic(['en-GB' => 'Fractions']);
    $reviewed = pageTopic(['en-GB' => 'Reviewed neighbour']);
    $proposed = pageTopic(['en-GB' => 'Proposed neighbour']);
    $rejected = pageTopic(['en-GB' => 'Rejected neighbour']);

    pageEdge($reviewed, $topic, EdgeType::Broader);
    pageEdge($proposed, $topic, EdgeType::Broader, EdgeStatus::MachineProposed);
    pageEdge($rejected, $topic, EdgeType::Broader, EdgeStatus::Rejected);

    $this->get(topicUrl($topic, 'fractions'))
        ->assertSee('Reviewed neighbour')
        ->assertDontSee('Proposed neighbour')
        ->assertDontSee('Rejected neighbour')
        ->assertSee('Show proposed links')
        ->assertSee('?proposed=1', false);

    $this->get(topicUrl($topic, 'fractions', '?proposed=1'))
        ->assertSee('Reviewed neighbour')
        ->assertSee('Proposed neighbour')
        ->assertDontSee('Rejected neighbour')
        ->assertSee('Hide proposed links')
        ->assertSeeHtml('border-dashed border-marker');
});

it('leaves out neighbours that have been deprecated', function () {
    $topic = pageTopic(['en-GB' => 'Fractions']);
    $gone = pageTopic(['en-GB' => 'Retired neighbour'], ['status' => ConceptStatus::Deprecated]);
    pageEdge($gone, $topic, EdgeType::Broader);

    $this->get(topicUrl($topic, 'fractions'))->assertDontSee('Retired neighbour');
});

// The language row

it('shows the launch languages first, then the visitor\'s own, with the rest behind more languages', function () {
    $topic = pageTopic(['en-GB' => 'Fractions']);
    $user = UserFactory::new()->create();
    app(ReplaceContentLocales::class)->execute($user, ['de-DE', 'en-GB']);

    $html = $this->actingAs($user)->get(topicUrl($topic, 'fractions'))->content();

    $row = substr($html, strpos($html, 'language-row-heading'));
    $moreAt = strpos($row, 'x-show="more"');

    // Launch languages (English, Romanian, Spanish), then the user's German, then the rest.
    expect(strpos($row, 'aria-label="English flag"'))->toBeLessThan(strpos($row, 'aria-label="Romanian flag"'));
    expect(strpos($row, 'aria-label="Spanish flag"'))->toBeLessThan(strpos($row, 'aria-label="German flag"'));
    expect(strpos($row, 'aria-label="German flag"'))->toBeLessThan($moreAt);
    expect(strpos($row, 'aria-label="Bulgarian flag"'))->toBeGreaterThan($moreAt);
    expect($row)->toContain('More languages');
});

it('shows reviewed, needs-checking and missing languages differently, and counts them', function () {
    $topic = pageTopic([
        'en-GB' => 'Fractions',
        'es-ES' => ['Fracciones', LabelStatus::Draft],
        'ro-RO' => ['Fracții', LabelStatus::Machine],
    ]);

    $html = $this->get(topicUrl($topic, 'fractions'))->content();

    expect($html)->toContain('3 of 26 languages · 1 reviewed');
    // Draft and machine names need checking; the other 23 languages have no name yet.
    expect(substr_count($html, 'title="needs checking"'))->toBe(2);
    expect(substr_count($html, 'title="no name yet — add one"'))->toBeGreaterThan(20);
});

it('links each chip to the topic in that language with the right slug', function () {
    $topic = pageTopic(['en-GB' => 'Fractions', 'ro-RO' => 'Fracții']);

    $this->get(topicUrl($topic, 'fractions'))
        ->assertSee("/topic/{$topic->public_id}/fractii?lang=ro-RO", false)
        ->assertSee("/topic/{$topic->public_id}/fractions?lang=en-GB", false)
        // A language with no name keeps the current slug and just asks for that language.
        ->assertSee("/topic/{$topic->public_id}/fractions?lang=de-DE", false);
});

it('marks the language being shown', function () {
    $topic = pageTopic(['en-GB' => 'Fractions', 'ro-RO' => 'Fracții']);

    $html = $this->get(topicUrl($topic, 'fractii', '?lang=ro-RO'))->content();

    // Only the language row (the header switcher has its own marker).
    $row = substr($html, strpos($html, 'language-row-heading'));

    expect(substr_count($row, 'aria-current="true"'))->toBe(1);
    expect($row)->toMatch('/href="[^"]*lang=ro-RO"[^>]*aria-current="true"/s');
});

it('keeps the proposed toggle when moving between languages', function () {
    $topic = pageTopic(['en-GB' => 'Fractions', 'ro-RO' => 'Fracții']);

    $this->get(topicUrl($topic, 'fractions', '?proposed=1'))
        ->assertSee("fractii?lang=ro-RO&amp;proposed=1", false);
});

// Queries: a fixed number however many neighbours the topic has.

function queriesForTopicWith(int $neighbours): int
{
    $topic = pageTopic(['en-GB' => "Hub {$neighbours}", 'ro-RO' => "Nod {$neighbours}", 'es-ES' => "Centro {$neighbours}"]);

    foreach (range(1, $neighbours) as $i) {
        $other = pageTopic(['en-GB' => "Neighbour {$neighbours}-{$i}", 'ro-RO' => "Vecin {$neighbours}-{$i}"]);

        match ($i % 4) {
            0 => pageEdge($other, $topic, EdgeType::Broader),
            1 => pageEdge($other, $topic, EdgeType::Prerequisite, strength: EdgeStrength::Required),
            2 => pageEdge($topic, $other, EdgeType::Prerequisite),
            default => pageEdge($topic, $other, EdgeType::Related),
        };
    }

    Model::preventLazyLoading();
    DB::flushQueryLog();
    DB::enableQueryLog();

    test()->get("/topic/{$topic->public_id}/hub-{$neighbours}", ['Accept-Language' => 'ro'])->assertRedirect();
    test()->get("/topic/{$topic->public_id}/nod-{$neighbours}", ['Accept-Language' => 'ro'])->assertOk();

    $count = count(DB::getQueryLog());
    DB::disableQueryLog();
    Model::preventLazyLoading(false);

    return $count;
}

it('makes the same number of queries however many topics it links to, and never lazy-loads', function () {
    $few = queriesForTopicWith(2);
    $many = queriesForTopicWith(16);

    expect($many)->toBe($few);
});
