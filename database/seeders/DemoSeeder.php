<?php

namespace Database\Seeders;

use App\Enums\ArtifactType;
use App\Enums\BlockType;
use App\Enums\ConceptStatus;
use App\Enums\LessonStatus;
use App\Enums\UserRole;
use App\Models\Artifact;
use App\Models\Block;
use App\Models\BlockVersion;
use App\Models\Concept;
use App\Models\Lesson;
use App\Models\Revision;
use App\Models\User;
use App\Models\UserContentLocale;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Development users, one lesson per launch language, and one YouTube artifact.
 * Password for every demo account: "password". Never run this in production.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->user('curator', 'Ana', 'Popescu', 'en-GB', 'GB', UserRole::Curator, ['en-GB']);
        $teacherEn = $this->user('teacher.en', 'Tom', 'Clarke', 'en-GB', 'GB', UserRole::Teacher, ['en-GB']);
        $teacherEs = $this->user('teacher.es', 'Lucía', 'Martín', 'es-ES', 'ES', UserRole::Teacher, ['es-ES', 'en-GB']);
        $teacherRo = $this->user('teacher.ro', 'Andrei', 'Ionescu', 'ro-RO', 'RO', UserRole::Teacher, ['ro-RO', 'en-GB']);

        $concept = Concept::query()->where('status', ConceptStatus::Draft)->orderBy('id')->firstOrFail();

        $this->demoLesson($teacherEn, 'en-GB', 'Adding fractions with different denominators', $concept);
        $this->demoLesson($teacherEs, 'es-ES', 'Sumar fracciones con distinto denominador', $concept);
        $this->demoLesson($teacherRo, 'ro-RO', 'Adunarea fracțiilor cu numitori diferiți', $concept);

        $video = Artifact::query()->firstOrCreate(
            ['youtube_id' => 'dQw4w9WgXcQ'],
            [
                'owner_id' => $teacherEn->id,
                'type' => ArtifactType::Youtube,
                'locale_code' => 'en-GB',
                'title' => 'Adding fractions with different denominators',
                'description' => 'Demo video',
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'licence' => 'CC-BY-SA-4.0',
            ],
        );

        $video->concepts()->syncWithoutDetaching([$concept->id => ['position' => 0, 'attached_by' => $teacherEn->id]]);
    }

    /**
     * @param  list<string>  $contentLocales
     */
    private function user(
        string $username,
        string $first,
        string $last,
        string $interface,
        string $country,
        UserRole $role,
        array $contentLocales,
    ): User {
        $user = User::query()->updateOrCreate(['username' => $username], [
            'first_name' => $first,
            'last_name' => $last,
            'email' => "{$username}@amural.test",
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'interface_locale' => $interface,
            'country_code' => $country,
            'avatar_preset' => random_int(1, 8),
            'role' => $role,
        ]);

        UserContentLocale::query()->where('user_id', $user->id)->delete();

        foreach ($contentLocales as $position => $locale) {
            UserContentLocale::query()->create([
                'user_id' => $user->id,
                'locale_code' => $locale,
                'position' => $position,
            ]);
        }

        return $user;
    }

    /**
     * A two-block lesson with one saved revision. The full SaveRevision action arrives
     * with the lessons milestone; this writes the same rows directly.
     */
    private function demoLesson(User $owner, string $locale, string $title, Concept $concept): void
    {
        if (Lesson::query()->where('owner_id', $owner->id)->where('title', $title)->exists()) {
            return;
        }

        DB::transaction(function () use ($owner, $locale, $title, $concept) {
            $lesson = Lesson::query()->create([
                'owner_id' => $owner->id,
                'locale_code' => $locale,
                'title' => $title,
                'slug' => str($title)->slug()->value(),
                'licence' => 'CC-BY-SA-4.0',
                'status' => LessonStatus::Published,
            ]);

            $lesson->concepts()->attach($concept->id, ['is_primary' => true]);

            $versionIds = [];
            $blocks = [
                [BlockType::Heading, ['text' => $title]],
                [BlockType::Markdown, ['text' => 'Demo lesson content.']],
            ];

            foreach ($blocks as [$type, $content]) {
                $block = Block::query()->create([
                    'lesson_id' => $lesson->id,
                    'type' => $type,
                    'derived_from' => [],
                ]);

                $version = BlockVersion::query()->create([
                    'block_id' => $block->id,
                    'content' => $content,
                    'content_hash' => hash('sha256', json_encode($content)),
                    'author_id' => $owner->id,
                    'parent_version_id' => null,
                ]);

                $versionIds[] = $version->id;
            }

            $revision = Revision::query()->create([
                'lesson_id' => $lesson->id,
                'parent_id' => null,
                'merge_parent_id' => null,
                'block_version_ids' => $versionIds,
                'title' => $title,
                'message' => 'Initial version',
                'licence' => 'CC-BY-SA-4.0',
                'author_id' => $owner->id,
            ]);

            $lesson->update(['head_revision_id' => $revision->id]);
        });
    }
}
