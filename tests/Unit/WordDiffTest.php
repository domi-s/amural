<?php

use App\Support\WordDiff;

it('keeps identical text as one unchanged segment', function () {
    expect(WordDiff::between('Parts of a whole', 'Parts of a whole'))->toBe([['same', 'Parts of a whole']]);
});

it('marks a replaced word as removed and added', function () {
    expect(WordDiff::between('Parts of a whole', 'Parts of the whole'))->toBe([
        ['same', 'Parts of '],
        ['removed', 'a'],
        ['added', 'the'],
        ['same', ' whole'],
    ]);
});

it('marks words that only appear in one text', function () {
    expect(WordDiff::between('', 'New name'))->toBe([['added', 'New name']])
        ->and(WordDiff::between('Old name', ''))->toBe([['removed', 'Old name']]);
});

it('compares words with accents and other scripts', function () {
    expect(WordDiff::between('Fracții simple', 'Fracții zecimale'))->toBe([
        ['same', 'Fracții '],
        ['removed', 'simple'],
        ['added', 'zecimale'],
    ]);
});

it('can put the new text back together from the segments', function () {
    $segments = WordDiff::between('one two three four', 'one three five four six');
    $new = collect($segments)->reject(fn ($s) => $s[0] === 'removed')->pluck(1)->implode('');

    expect($new)->toBe('one three five four six');
});
