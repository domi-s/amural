<?php

function langStrings(string $language): array
{
    $path = lang_path("{$language}.json");

    expect(is_file($path))->toBeTrue();

    $strings = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

    expect($strings)->toBeArray();

    return $strings;
}

function placeholdersIn(string $text): array
{
    preg_match_all('/:[a-zA-Z_]+/', $text, $matches);
    // A plural string may repeat a placeholder in each form (Romanian has three), so each counts once.
    $found = array_values(array_unique($matches[0]));
    sort($found);

    return $found;
}

it('has an interface language file for every interface locale', function () {
    foreach (['en', 'es', 'ro'] as $language) {
        langStrings($language);
    }
});

it('translates every English string into Spanish and Romanian, and nothing else', function () {
    $english = array_keys(langStrings('en'));

    foreach (['es', 'ro'] as $language) {
        $keys = array_keys(langStrings($language));

        expect(array_values(array_diff($english, $keys)))->toBe([]); // strings missing from the translation
        expect(array_values(array_diff($keys, $english)))->toBe([]); // strings that are not in en.json
    }
});

it('never leaves a translation empty', function () {
    foreach (['es', 'ro'] as $language) {
        foreach (langStrings($language) as $key => $translation) {
            expect(trim($translation))->not->toBe('');
        }
    }
});

it('keeps the same :placeholders in every translation', function () {
    foreach (['es', 'ro'] as $language) {
        foreach (langStrings($language) as $key => $translation) {
            expect(placeholdersIn($translation))->toBe(placeholdersIn($key));
        }
    }
});

it('lists every Spanish and Romanian string in the native-check file', function () {
    $list = file_get_contents(lang_path('NATIVE_CHECK.md'));

    foreach (['es' => 'Spanish (es)', 'ro' => 'Romanian (ro)'] as $language => $heading) {
        expect($list)->toContain("## {$heading}");

        foreach (array_keys(langStrings($language)) as $key) {
            expect($list)->toContain("`{$key}`");
        }
    }
});
