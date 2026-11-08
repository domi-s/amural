<?php

use App\Models\Concept;
use App\Models\User;
use App\Models\UserContentLocale;
use App\Support\LocaleResolver;
use Database\Factories\ConceptFactory;
use Database\Factories\ConceptLabelFactory;
use Database\Factories\UserFactory;
use Database\Seeders\LocaleSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(LocaleSeeder::class);
});

afterEach(function () {
    Model::preventLazyLoading(false);
});

/**
 * @param  array{query?: array<string, string>, accept?: string, session?: array<string, string>, user?: User}  $options
 */
function makeResolver(array $options = []): LocaleResolver
{
    $server = isset($options['accept']) ? ['HTTP_ACCEPT_LANGUAGE' => $options['accept']] : [];
    $request = Request::create('/topic/c_TEST', 'GET', $options['query'] ?? [], [], [], $server);

    $session = new Store('test', new ArraySessionHandler(120));
    $session->put($options['session'] ?? []);
    $request->setLaravelSession($session);

    if (isset($options['user'])) {
        $request->setUserResolver(fn () => $options['user']);
    }

    return new LocaleResolver($request);
}

function userReading(array $codes, string $interfaceLocale = 'en-GB'): User
{
    $user = UserFactory::new()->create(['interface_locale' => $interfaceLocale]);

    foreach ($codes as $position => $code) {
        UserContentLocale::create(['user_id' => $user->id, 'locale_code' => $code, 'position' => $position]);
    }

    return $user;
}

// Rule 3: ?lang > user content locales in order > session > Accept-Language > en-GB > any.

it('falls back to en-GB when nothing says otherwise', function () {
    expect(makeResolver()->contentChain()[0])->toBe('en-GB');
});

it('puts Accept-Language matches first for a guest with no other preference', function () {
    $chain = makeResolver(['accept' => 'ro-RO,ro;q=0.9,en;q=0.8'])->contentChain();

    expect(array_slice($chain, 0, 2))->toBe(['ro-RO', 'en-GB']);
});

it('matches a bare Accept-Language tag to the first locale in that language', function () {
    expect(makeResolver(['accept' => 'es'])->contentChain()[0])->toBe('es-ES');
});

it('ignores Accept-Language tags that match no locale', function () {
    expect(makeResolver(['accept' => 'ja,zh;q=0.8'])->contentChain()[0])->toBe('en-GB');
});

it('prefers the guest session over Accept-Language', function () {
    $chain = makeResolver([
        'accept' => 'es',
        'session' => ['content_locale' => 'ro-RO'],
    ])->contentChain();

    expect(array_slice($chain, 0, 2))->toBe(['ro-RO', 'es-ES']);
});

it('prefers a signed-in user\'s content locales, in order, over the session and Accept-Language', function () {
    $user = userReading(['ro-RO', 'de-DE', 'en-GB']);

    $chain = makeResolver([
        'user' => $user,
        'accept' => 'es',
        'session' => ['content_locale' => 'fr-FR'],
    ])->contentChain();

    expect(array_slice($chain, 0, 4))->toBe(['ro-RO', 'de-DE', 'en-GB', 'es-ES']);
    // The session is for guests only: fr-FR must come after the explicit choices.
    expect(array_search('fr-FR', $chain))->toBeGreaterThan(3);
});

it('puts a valid ?lang= first, ahead of everything else', function () {
    $user = userReading(['ro-RO']);

    $chain = makeResolver(['user' => $user, 'query' => ['lang' => 'el-GR'], 'accept' => 'es'])->contentChain();

    expect(array_slice($chain, 0, 3))->toBe(['el-GR', 'ro-RO', 'es-ES']);
});

it('ignores an invalid ?lang=', function () {
    $chain = makeResolver(['query' => ['lang' => 'xx-XX'], 'accept' => 'es'])->contentChain();

    expect($chain[0])->toBe('es-ES');
    expect($chain)->not->toContain('xx-XX');
});

it('ends with every locale exactly once, launch locales before the rest', function () {
    $chain = makeResolver(['accept' => 'de'])->contentChain();

    expect($chain)->toHaveCount(26);
    expect(array_unique($chain))->toHaveCount(26);

    // After the explicit choices (de-DE, en-GB) the launch locales come before the others.
    $rest = array_values(array_diff($chain, ['de-DE', 'en-GB']));
    expect(array_slice($rest, 0, 2))->toBe(['ro-RO', 'es-ES']);
});

// The interface locale: user > session (guests) > Accept-Language among interface locales > en-GB.

it('uses the signed-in user\'s interface locale', function () {
    $user = userReading(['en-GB'], interfaceLocale: 'ro-RO');

    expect(makeResolver(['user' => $user, 'accept' => 'es'])->interfaceLocale())->toBe('ro-RO');
});

it('uses the session for a guest, then Accept-Language, then en-GB', function () {
    expect(makeResolver(['session' => ['interface_locale' => 'es-ES'], 'accept' => 'ro'])->interfaceLocale())->toBe('es-ES');
    expect(makeResolver(['accept' => 'ro-RO,en;q=0.5'])->interfaceLocale())->toBe('ro-RO');
    expect(makeResolver()->interfaceLocale())->toBe('en-GB');
});

it('only picks interface locales for the interface, even if the browser asks for another', function () {
    expect(makeResolver(['accept' => 'fr-FR,fr;q=0.9,es;q=0.8'])->interfaceLocale())->toBe('es-ES');
    expect(makeResolver(['accept' => 'fr-FR'])->interfaceLocale())->toBe('en-GB');
    expect(makeResolver(['session' => ['interface_locale' => 'fr-FR']])->interfaceLocale())->toBe('en-GB');
});

// $concept->label(): first label in the chain, plus isFallback.

function conceptNamed(array $names): Concept
{
    $concept = ConceptFactory::new()->create();

    foreach ($names as $locale => $pref) {
        ConceptLabelFactory::new()->create(['concept_id' => $concept->id, 'locale_code' => $locale, 'pref' => $pref]);
    }

    return $concept->load('labels');
}

it('returns the first-choice label and no fallback flag when it exists', function () {
    $concept = conceptNamed(['en-GB' => 'Fractions', 'ro-RO' => 'Fracții']);

    $resolved = $concept->label(makeResolver(['accept' => 'ro-RO']));

    expect($resolved->label->pref)->toBe('Fracții');
    expect($resolved->isFallback)->toBeFalse();
    expect($resolved->requestedLocale)->toBe('ro-RO');
});

it('flags a fallback when the first choice has no name', function () {
    $concept = conceptNamed(['en-GB' => 'Fractions']);

    $resolved = $concept->label(makeResolver(['accept' => 'ro-RO']));

    expect($resolved->label->pref)->toBe('Fractions');
    expect($resolved->isFallback)->toBeTrue();
    expect($resolved->requestedLocale)->toBe('ro-RO');
    expect($resolved->shownLocale())->toBe('en-GB');
});

it('follows the user\'s order of languages when choosing a label', function () {
    $concept = conceptNamed(['en-GB' => 'Fractions', 'de-DE' => 'Brüche']);
    $user = userReading(['ro-RO', 'de-DE', 'en-GB']);

    $resolved = $concept->label(makeResolver(['user' => $user]));

    expect($resolved->label->pref)->toBe('Brüche');
    expect($resolved->isFallback)->toBeTrue();
});

it('uses a ?lang= override and treats it as the first choice', function () {
    $concept = conceptNamed(['en-GB' => 'Fractions', 'es-ES' => 'Fracciones']);

    $resolved = $concept->label(makeResolver(['query' => ['lang' => 'es-ES']]));

    expect($resolved->label->pref)->toBe('Fracciones');
    expect($resolved->isFallback)->toBeFalse();
});

it('falls back to any available label, launch locales first, when nothing in the chain matches', function () {
    // en-GB (the default) and the visitor's choice are missing; only Greek and Spanish exist.
    $concept = conceptNamed(['el-GR' => 'Κλάσματα', 'es-ES' => 'Fracciones']);

    $resolved = $concept->label(makeResolver(['accept' => 'ro-RO']));

    expect($resolved->label->pref)->toBe('Fracciones'); // es-ES is a launch locale, el-GR is not
    expect($resolved->isFallback)->toBeTrue();
});

it('returns no label, and no fallback flag, for a concept with no names', function () {
    $concept = ConceptFactory::new()->create()->load('labels');

    $resolved = $concept->label(makeResolver());

    expect($resolved->label)->toBeNull();
    expect($resolved->isFallback)->toBeFalse();
});

it('uses the request\'s resolver when none is given', function () {
    $concept = conceptNamed(['en-GB' => 'Fractions']);

    expect($concept->label()->label->pref)->toBe('Fractions');
});

it('never lazy-loads or queries per concept when labels are eager-loaded', function () {
    foreach (range(1, 5) as $i) {
        conceptNamed(['en-GB' => "Concept $i", 'ro-RO' => "Concept ro $i"]);
    }

    $concepts = Concept::with('labels')->get();
    $resolver = makeResolver(['accept' => 'ro-RO']);

    Model::preventLazyLoading();
    $resolver->contentChain(); // the first call loads the locale list once

    DB::enableQueryLog();
    $names = $concepts->map(fn (Concept $c) => $c->label($resolver)->label->pref)->all();
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($names)->toHaveCount(5);
    expect($queries)->toBe([]);
});
