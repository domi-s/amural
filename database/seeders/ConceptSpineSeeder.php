<?php

namespace Database\Seeders;

use App\Actions\Concepts\UpdateConceptLabel;
use App\Enums\ConceptKind;
use App\Enums\ConceptStatus;
use App\Enums\LabelStatus;
use App\Enums\Stage;
use App\Models\Concept;
use App\Models\ConceptLabel;
use Illuminate\Database\Seeder;

class ConceptSpineSeeder extends Seeder
{
    /**
     * Ten top-level mathematics concepts: [en-GB, es-ES, ro-RO]. The es-ES and ro-RO names
     * are draft, to be checked by native speakers.
     */
    private const SPINE = [
        ['Number', 'Números', 'Numere'],
        ['Algebra', 'Álgebra', 'Algebră'],
        ['Geometry', 'Geometría', 'Geometrie'],
        ['Measurement', 'Medida', 'Măsurări'],
        ['Statistics', 'Estadística', 'Statistică'],
        ['Probability', 'Probabilidad', 'Probabilități'],
        ['Functions', 'Funciones', 'Funcții'],
        ['Calculus', 'Cálculo', 'Analiză matematică'],
        ['Discrete mathematics', 'Matemática discreta', 'Matematică discretă'],
        ['Linear algebra', 'Álgebra lineal', 'Algebră liniară'],
    ];

    private const LOCALES = ['en-GB', 'es-ES', 'ro-RO'];

    public function run(): void
    {
        foreach (self::SPINE as $names) {
            $concept = $this->conceptNamed($names[0]) ?? Concept::query()->create([
                'kind' => ConceptKind::Concept,
                'stage_from' => Stage::EarlyPrimary,
                'stage_to' => Stage::Graduate,
                'status' => ConceptStatus::Draft,
            ]);

            foreach (self::LOCALES as $index => $locale) {
                app(UpdateConceptLabel::class)->execute(
                    $concept,
                    $locale,
                    ['pref' => $names[$index]],
                    author: null,
                    status: LabelStatus::Draft,
                    note: 'Seeded demo spine',
                );
            }
        }
    }

    /**
     * Finds an existing spine concept by its en-GB name, so re-running the seeder adds nothing.
     */
    private function conceptNamed(string $englishName): ?Concept
    {
        return ConceptLabel::query()
            ->where('locale_code', 'en-GB')
            ->where('pref', $englishName)
            ->first()
            ?->concept;
    }
}
