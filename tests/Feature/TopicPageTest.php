<?php

use App\Actions\Concepts\CreateConceptEdge;
use App\Actions\Concepts\UpdateConceptLabel;
use App\Enums\ConceptKind;
use App\Enums\EdgeStatus;
use App\Enums\EdgeStrength;
use App\Enums\EdgeType;
use App\Enums\LabelStatus;
use App\Enums\Stage;
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
    $response->assertDontSeeLivewire(App\Livewire\LanguageSwitcher::class);
    // No flags on this page: language is a quiet setting in the margin.
    expect($html)->not->toContain('flag"');
});

// The margin: reading in

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
