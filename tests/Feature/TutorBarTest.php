<?php

use App\Support\TutorBar;

it('has three modes, each with a hint and three things to try', function () {
    $modes = TutorBar::modes();

    expect(array_map(fn ($m) => $m->key, $modes))->toBe(['lesson', 'practice', 'guide']);

    foreach ($modes as $mode) {
        expect($mode->label)->not->toBe('')
            ->and($mode->placeholder)->not->toBe('')
            ->and($mode->hint)->not->toBe('')
            ->and($mode->examples)->toHaveCount(3);
    }
});

it('promises hints, not answers, in guide mode', function () {
    $guide = collect(TutorBar::modes())->firstWhere('key', TutorBar::GUIDE);

    expect($guide->hint)->toContain('Hints, not answers');
});

it('asks one of four questions, the same one all day', function () {
    $headlines = array_map(fn (int $seed) => TutorBar::headline('Maria', $seed), [0, 1, 2, 3]);

    expect(array_unique($headlines))->toHaveCount(4);
    expect(TutorBar::headline('Maria', 4))->toBe(TutorBar::headline('Maria', 0));
    expect(TutorBar::headline('Maria', 0))->toBe('What would you like to learn today, Maria?');
});

it('leaves the name out for a guest or an empty name', function () {
    expect(TutorBar::headline(null, 0))->toBe('What would you like to learn today?');
    expect(TutorBar::headline('  ', 2))->toBe('Stuck on something?');
});

it('keeps a name with accents and apostrophes as it is', function () {
    expect(TutorBar::headline('Ștefan', 1))->toBe('What are we working on, Ștefan?');
});
