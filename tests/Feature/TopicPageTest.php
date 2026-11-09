<?php

use App\Actions\Concepts\CreateConceptEdge;
use App\Actions\Concepts\UpdateConceptLabel;
use App\Enums\ConceptKind;
use App\Enums\ConceptStatus;
use App\Enums\EdgeStatus;
use App\Enums\EdgeStrength;
use App\Enums\EdgeType;
use App\Enums\LabelStatus;
use App\Enums\Stage;
use App\Livewire\LanguageSwitcher;
use App\Models\Concept;
use App\Models\ConceptEdge;
use Database\Factories\ConceptFactory;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LocaleSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

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

// The title block

it('is readable by anyone, without signing in', function () {
    $topic = pageTopic(['en-GB' => 'Adding fractions']);

    $this->get(topicUrl($topic, 'adding-fractions'))->assertOk()->assertSee('Adding fractions');
});

it('opens with a kicker, the title, and the definition under it', function () {
    $topic = pageTopic(
        ['en-GB' => 'Adding fractions'],
        ['kind' => ConceptKind::Procedure, 'stage_from' => Stage::Primary, 'stage_to' => Stage::LowerSecondary],
        definition: 'Combine parts of a whole.',
    );

    $this->get(topicUrl($topic, 'adding-fractions'))
        ->assertOk()
        ->assertSeeInOrder(['A procedure · Primary to lower secondary', '<h1', 'Adding', 'ombine parts of a whole.'], false);
});

it('circles the longest word of five or more letters that is not the first', function () {
    $topic = pageTopic(['en-GB' => 'Adding unlike fractions']);

    $html = $this->get(topicUrl($topic, 'adding-unlike-fractions'))->content();

    expect($html)->toMatch('/<span class="relative inline-block"><svg[^>]*aria-hidden="true".*?<\/svg>\s*fractions<\/span>/s');
    expect(substr_count($html, '<span class="relative inline-block">'))->toBe(1);
});

it('does not circle anything in a title with no such word', function () {
    $topic = pageTopic(['en-GB' => 'Pi']);

    $this->get(topicUrl($topic, 'pi'))->assertDontSee('<span class="relative inline-block">', false);
});

it('starts the definition with a drop cap in Latin script only', function () {
    $latin = pageTopic(['en-GB' => 'Fractions'], definition: 'Numbers that name a part of a whole.');
    $greek = pageTopic(['el-GR' => 'Κλάσματα'], definition: 'Αριθμοί που δείχνουν μέρος του όλου.');

    $this->get(topicUrl($latin, 'fractions'))->assertSee('text-[92px]', false)->assertSee('umbers that name a part of a whole.');
    $this->get(topicUrl($greek, 'klasmata', '?lang=el-GR'))->assertDontSee('text-[92px]', false)->assertSee('Αριθμοί που δείχνουν μέρος του όλου.');
});

it('ends the definition with the other names', function () {
    $topic = pageTopic(['en-GB' => 'Adding fractions'], definition: 'Combine parts.', alt: ['fraction addition', 'summing fractions']);

    $this->get(topicUrl($topic, 'adding-fractions'))
        ->assertSeeHtml('Also known as <em>fraction addition</em>, <em>summing fractions</em>.');
});

it('says there is no definition yet, with a link to write one only when the editor exists', function () {
    $topic = pageTopic(['en-GB' => 'Decimals']);

    $this->get(topicUrl($topic, 'decimals'))->assertSee('No definition yet.')->assertDontSee('Write one');

    Route::get('/topic/{concept}/edit/{locale}', fn () => 'edit')->name('topic.edit');
    Route::getRoutes()->refreshNameLookups();

    $this->get(topicUrl($topic, 'decimals'))->assertSee('Write one')->assertSee("/topic/{$topic->public_id}/edit/en-GB", false);
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

it('has one main heading, a skip link and a header without a language switcher or rules', function () {
    $topic = pageTopic(['en-GB' => 'Fractions']);

    $response = $this->get(topicUrl($topic, 'fractions'));
    $html = $response->content();

    expect(substr_count($html, '<h1'))->toBe(1);
    expect($html)->toContain('href="#content"')->not->toContain('aria-label="Breadcrumb"')->not->toContain('border-b border-marker');
    $response->assertDontSeeLivewire(LanguageSwitcher::class);
    // No flags on this page: language is a quiet setting in the margin.
    expect($html)->not->toContain('flag"');
});

// The margin: reading in

it('lists only the languages the topic has a name in, in the visitor\'s order, with the current one pressed', function () {
    $topic = pageTopic(['en-GB' => 'Fractions', 'ro-RO' => 'Fracții', 'de-DE' => 'Brüche']);

    $html = $this->get(topicUrl($topic, 'fractii', '?lang=ro-RO'))->content();
    $margin = substr($html, strpos($html, 'id="reading-in"'), 2500);

    expect($margin)->toMatch('/aria-pressed="true"[^>]*>\s*Română|Română.*aria-pressed/s');
    expect(strpos($margin, 'Română'))->toBeLessThan(strpos($margin, 'English'));
    expect($margin)->not->toContain('Español')->not->toContain('Polski');
    expect(substr_count($margin, 'aria-pressed="true"'))->toBe(1);
    expect(substr_count($margin, '<button'))->toBe(3);
});

it('makes each language a button that sends ?lang= to the right slug', function () {
    $topic = pageTopic(['en-GB' => 'Fractions', 'ro-RO' => 'Fracții']);

    $html = $this->get(topicUrl($topic, 'fractions'))->content();

    expect($html)->toContain('formaction="'.url("/topic/{$topic->public_id}/fractii").'"')
        ->toContain('name="lang"')
        ->toContain('value="ro-RO"')
        ->toContain('lang="ro"');
});

it('keeps a draft or machine name marked as needing checking, and nothing else', function () {
    $topic = pageTopic([
        'en-GB' => 'Fractions',
        'es-ES' => ['Fracciones', LabelStatus::Draft],
        'ro-RO' => ['Fracții', LabelStatus::Machine],
    ]);

    $html = $this->get(topicUrl($topic, 'fractions'))->content();

    expect(substr_count($html, '(needs checking)'))->toBe(2);
});

it('says when the name is not yet in the visitor\'s language, and offers a translation only when the editor exists', function () {
    $topic = pageTopic(['en-GB' => 'Fractions']);

    $this->get(topicUrl($topic, 'fractions', '?lang=ro-RO'))->assertSee('Not in Română yet.')->assertDontSee('+ translate it');

    Route::get('/topic/{concept}/edit/{locale}', fn () => 'edit')->name('topic.edit');
    Route::getRoutes()->refreshNameLookups();

    $this->get(topicUrl($topic, 'fractions', '?lang=ro-RO'))->assertSee('+ translate it')->assertSee("/topic/{$topic->public_id}/edit/ro-RO", false);
});

it('does not say that when the name is in the first-choice language', function () {
    $topic = pageTopic(['en-GB' => 'Fractions', 'ro-RO' => 'Fracții']);

    $this->get(topicUrl($topic, 'fractii', '?lang=ro-RO'))->assertDontSee('Not in');
});

// The margin: filed under

it('files the topic under its subject, its chain of broader topics and the ages it is for', function () {
    $number = pageTopic(['en-GB' => 'Number']);
    $fractions = pageTopic(['en-GB' => 'Fractions']);
    $adding = pageTopic(['en-GB' => 'Adding fractions'], ['stage_from' => Stage::Primary, 'stage_to' => Stage::LowerSecondary]);

    pageEdge($fractions, $number, EdgeType::Broader);
    pageEdge($adding, $fractions, EdgeType::Broader);

    $html = $this->get(topicUrl($adding, 'adding-fractions'))->content();

    expect($html)->toContain('Filed under')->toContain('Mathematics')->toContain('Ages 7 to 14');
    expect($html)->toMatch('/Mathematics.*>Number<\/a>,\s*<a[^>]*>Fractions<\/a>.*Ages 7 to 14/s');
    // The first parent is the "issue" in the header.
    expect($html)->toMatch('/Issue:\s*<a[^>]*>Fractions<\/a>/s');
});

it('stops following broader topics round a loop', function () {
    $a = pageTopic(['en-GB' => 'Topic A']);
    $b = pageTopic(['en-GB' => 'Topic B']);
    pageEdge($a, $b, EdgeType::Broader);
    pageEdge($b, $a, EdgeType::Broader);

    $this->get(topicUrl($a, 'topic-a'))->assertOk();
});

it('shows stages without ages in words', function () {
    $topic = pageTopic(['en-GB' => 'Topology'], ['stage_from' => Stage::Undergraduate, 'stage_to' => Stage::Graduate]);

    $this->get(topicUrl($topic, 'topology'))->assertSee('Undergraduate to graduate');
});

// The path

it('draws the path from prerequisites through this topic to what it leads to, required first', function () {
    $topic = pageTopic(['en-GB' => 'Adding fractions']);
    $equivalent = pageTopic(['en-GB' => 'Equivalent fractions']);
    $helpful = pageTopic(['en-GB' => 'Adding whole numbers']);
    $zebra = pageTopic(['en-GB' => 'Zebra prerequisite']);
    $next = pageTopic(['en-GB' => 'Subtracting fractions']);

    pageEdge($helpful, $topic, EdgeType::Prerequisite, strength: EdgeStrength::Helpful);
    pageEdge($zebra, $topic, EdgeType::Prerequisite, strength: EdgeStrength::Required);
    pageEdge($equivalent, $topic, EdgeType::Prerequisite, strength: EdgeStrength::Required);
    pageEdge($topic, $next, EdgeType::Prerequisite);

    $html = $this->get(topicUrl($topic, 'adding-fractions'))->content();

    // At most two on each side: the two required ones, then this topic, then what it leads to.
    expect($html)->toMatch('/First.*Equivalent fractions.*First.*Zebra prerequisite.*You are here.*Adding fractions.*Then.*Subtracting fractions/s');
    expect($html)->not->toContain('Adding whole numbers</a>');
    expect($html)->toContain('<ol class="relative');
    expect($html)->toContain('aria-current="location"');
});

it('hides the path when the topic has no prerequisites and leads nowhere', function () {
    $topic = pageTopic(['en-GB' => 'Counting']);

    $this->get(topicUrl($topic, 'counting'))->assertDontSee('You are here')->assertDontSee('Next door');
});

it('puts related, special-case and narrower topics in one sentence, at most five', function () {
    $topic = pageTopic(['en-GB' => 'Quadrilaterals']);
    $square = pageTopic(['en-GB' => 'Square']);
    $rectangle = pageTopic(['en-GB' => 'Rectangle']);
    $polygon = pageTopic(['en-GB' => 'Polygon']);
    $perimeter = pageTopic(['en-GB' => 'Perimeter']);

    pageEdge($square, $topic, EdgeType::Broader);
    pageEdge($rectangle, $topic, EdgeType::Broader);
    pageEdge($topic, $polygon, EdgeType::SpecialCase);
    pageEdge($square, $topic, EdgeType::SpecialCase);
    pageEdge($topic, $perimeter, EdgeType::Related);

    $html = $this->get(topicUrl($topic, 'quadrilaterals'))->content();

    expect($html)->toMatch('/Next door:.*Perimeter.*Polygon.*Square.*and\s*<a[^>]*>Rectangle<\/a>\./s');
    // Square is both narrower and a special case, and is listed once.
    expect(substr_count($html, '>Square</a>'))->toBe(1);
    expect($html)->not->toContain('Before this')->not->toContain('Show proposed links');

    foreach (range(1, 8) as $i) {
        pageEdge($topic, pageTopic(['en-GB' => "Extra {$i}"]), EdgeType::Related);
    }

    $html = $this->get(topicUrl($topic, 'quadrilaterals'))->content();
    $sentence = substr($html, strpos($html, 'Next door:'), strpos($html, '</p>', strpos($html, 'Next door:')) - strpos($html, 'Next door:'));

    expect(substr_count($sentence, '<a '))->toBe(5);
});

it('links to each topic at its canonical URL in the visitor\'s language', function () {
    $topic = pageTopic(['en-GB' => 'Fractions', 'ro-RO' => 'Fracții']);
    $child = pageTopic(['en-GB' => 'Adding fractions', 'ro-RO' => 'Adunarea fracțiilor']);
    pageEdge($child, $topic, EdgeType::Broader);

    $this->get(topicUrl($topic, 'fractii', '?lang=ro-RO'))
        ->assertSee(url("/topic/{$child->public_id}/adunarea-fractiilor"), false)
        ->assertSee('Adunarea fracțiilor');
});

it('shows only reviewed links, with no way to ask for the proposed ones', function () {
    $topic = pageTopic(['en-GB' => 'Fractions']);
    $reviewed = pageTopic(['en-GB' => 'Reviewed neighbour']);
    $proposed = pageTopic(['en-GB' => 'Proposed neighbour']);
    $rejected = pageTopic(['en-GB' => 'Rejected neighbour']);

    pageEdge($reviewed, $topic, EdgeType::Broader);
    pageEdge($proposed, $topic, EdgeType::Broader, EdgeStatus::MachineProposed);
    pageEdge($rejected, $topic, EdgeType::Broader, EdgeStatus::Rejected);

    foreach (['', '?proposed=1'] as $query) {
        $this->get(topicUrl($topic, 'fractions', $query))
            ->assertSee('Reviewed neighbour')
            ->assertDontSee('Proposed neighbour')
            ->assertDontSee('Rejected neighbour')
            ->assertDontSee('proposed links');
    }
});

it('leaves out neighbours that have been deprecated', function () {
    $topic = pageTopic(['en-GB' => 'Fractions']);
    $gone = pageTopic(['en-GB' => 'Retired neighbour'], ['status' => ConceptStatus::Deprecated]);
    pageEdge($gone, $topic, EdgeType::Broader);

    $this->get(topicUrl($topic, 'fractions'))->assertDontSee('Retired neighbour');
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
