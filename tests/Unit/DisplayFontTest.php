<?php

use App\Support\DisplayFont;

it('uses the display font for plain Latin text', function (string $text) {
    expect(DisplayFont::supports($text))->toBeTrue();
    expect(DisplayFont::classes($text))->toBe('font-display italic');
})->with([
    'English' => 'Adding fractions with different denominators',
    'Romanian without diacritics' => 'Numere',
    'digits and punctuation' => "Pythagoras' theorem (part 2): right-angled triangles, 3-4-5",
    'a letter the font draws well' => 'Voilà, à la mode',
]);

it('uses Inter for text with letters the display font draws wrongly or not at all', function (string $text) {
    expect(DisplayFont::supports($text))->toBeFalse();
    expect(DisplayFont::classes($text))->toBe('font-semibold');
})->with([
    'Romanian ă' => 'Algebră',
    'Romanian ș and ț' => 'Fracții și procente',
    'Romanian î' => 'Înmulțirea',
    'Spanish accents' => 'Números',
    'Spanish ñ' => 'Años',
    'German umlaut' => 'Brüche',
    'French é' => 'Géométrie',
    'Czech' => 'Čísla',
    'Greek' => 'Γεωμετρία',
    'Bulgarian' => 'Геометрия',
    'a single bad letter at the end' => 'Fractions é',
]);

it('treats an empty string as drawable', function () {
    expect(DisplayFont::supports(''))->toBeTrue();
});
