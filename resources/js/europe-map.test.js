import assert from 'node:assert/strict';
import { test } from 'node:test';

import { captionFor, countryFromElement, localesFor, speaks, unavailableCount } from './europe-map.js';

const byLanguage = {
    de: [{ code: 'de-DE' }],
    fr: [{ code: 'fr-FR' }],
    it: [{ code: 'it-IT' }],
    nl: [{ code: 'nl-NL' }],
    en: [{ code: 'en-GB' }],
    nb: [{ code: 'nb-NO' }],
    no: [{ code: 'nb-NO' }],
};

const element = (id, dataset) => ({ id, dataset });

test('describes a country from its map element', () => {
    const country = countryFromElement(
        element('BE', { name: 'Belgium', local: 'België · Belgique · Belgien', langs: 'nl fr de' }),
    );

    assert.deepEqual(country, {
        code: 'BE',
        name: 'Belgium',
        local: 'België · Belgique · Belgien',
        languages: ['nl', 'fr', 'de'],
    });
});

test('copes with a country that has no languages or local name', () => {
    const country = countryFromElement(element('XX', {}));

    assert.deepEqual(country, { code: 'XX', name: 'XX', local: '', languages: [] });
});

test('offers the languages we have, in the map order', () => {
    const country = { languages: ['nl', 'fr', 'de'] };

    assert.deepEqual(localesFor(country, byLanguage).map((l) => l.code), ['nl-NL', 'fr-FR', 'de-DE']);
});

test('skips languages we do not have and says how many', () => {
    const switzerland = { languages: ['de', 'fr', 'it', 'rm'] };

    assert.deepEqual(localesFor(switzerland, byLanguage).map((l) => l.code), ['de-DE', 'fr-FR', 'it-IT']);
    assert.equal(unavailableCount(switzerland, byLanguage), 1);
});

test('offers a language once even if two map codes lead to the same locale', () => {
    const country = { languages: ['no', 'nb'] };

    assert.deepEqual(localesFor(country, byLanguage).map((l) => l.code), ['nb-NO']);
});

test('offers nothing for a country whose languages we do not have', () => {
    const country = { languages: ['rm', 'lb'] };

    assert.deepEqual(localesFor(country, byLanguage), []);
    assert.equal(unavailableCount(country, byLanguage), 2);
});

test('knows whether a country speaks a language', () => {
    assert.equal(speaks({ languages: ['nl', 'fr', 'de'] }, 'fr'), true);
    assert.equal(speaks({ languages: ['nl', 'fr', 'de'] }, 'ro'), false);
});

test('names the country under the pointer, else the chosen one, else says what to do', () => {
    const labels = { choose: 'Choose a country on the map' };
    const romania = { name: 'Romania', local: 'România' };
    const malta = { name: 'Malta', local: 'Malta' };

    assert.equal(captionFor(null, null, labels), 'Choose a country on the map');
    assert.equal(captionFor(null, romania, labels), 'Romania · România');
    assert.equal(captionFor(malta, romania, labels), 'Malta');
});
