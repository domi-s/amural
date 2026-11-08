<?php

use App\Enums\ArtifactType;
use App\Enums\UserRole;
use App\Models\Artifact;
use App\Models\Lesson;
use App\Models\Revision;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

it('seeds a curator, three teachers, a lesson per launch language and a YouTube artifact', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::where('role', UserRole::Curator)->count())->toBe(1);
    expect(User::where('role', UserRole::Teacher)->count())->toBe(3);

    expect(Lesson::orderBy('locale_code')->pluck('locale_code')->all())->toBe(['en-GB', 'es-ES', 'ro-RO']);
    expect(Artifact::where('type', ArtifactType::Youtube)->count())->toBe(1);
});

it('gives every demo lesson a primary topic, two blocks and a head revision', function () {
    $this->seed(DatabaseSeeder::class);

    Lesson::with('headRevision', 'concepts')->get()->each(function (Lesson $lesson) {
        expect($lesson->headRevision)->toBeInstanceOf(Revision::class);
        expect($lesson->headRevision->block_version_ids)->toHaveCount(2);
        expect($lesson->concepts->where('pivot.is_primary', true))->toHaveCount(1);
    });
});

it('gives each demo teacher the content languages they teach in order', function () {
    $this->seed(DatabaseSeeder::class);

    $spanish = User::where('username', 'teacher.es')->firstOrFail();

    expect($spanish->interface_locale)->toBe('es-ES');
    expect($spanish->contentLocales->pluck('locale_code')->all())->toBe(['es-ES', 'en-GB']);
});

it('can be run twice without duplicating users, lessons or artifacts', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe(4);
    expect(Lesson::count())->toBe(3);
    expect(Revision::count())->toBe(3);
    expect(Artifact::count())->toBe(1);
});
