<?php

namespace App\Actions\Concepts;

use App\Enums\LabelStatus;
use App\Models\Concept;
use App\Models\ConceptLabel;
use App\Models\ConceptLabelRevision;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Writes a concept's name and definition in one locale. Every real change becomes one
 * append-only revision; a no-op writes nothing.
 *
 * Unless the caller sets the status itself (imports and seeders), an edit is a draft:
 * only a curator who marks it reviewed makes it reviewed, so a teacher's edit to a
 * reviewed name puts it back in the queue.
 */
class UpdateConceptLabel
{
    /**
     * @param  array{pref: string, alt?: array<int, string>, definition?: ?string}  $data
     */
    public function execute(
        Concept $concept,
        string $localeCode,
        array $data,
        ?User $author = null,
        ?LabelStatus $status = null,
        ?string $note = null,
        bool $markReviewed = false,
    ): ConceptLabel {
        $data = $this->validated($data);
        $status ??= $markReviewed && $author?->isCurator() ? LabelStatus::Reviewed : LabelStatus::Draft;

        return DB::transaction(function () use ($concept, $localeCode, $data, $author, $status, $note) {
            $label = ConceptLabel::query()
                ->where('concept_id', $concept->id)
                ->where('locale_code', $localeCode)
                ->lockForUpdate()
                ->first();

            if ($label && $this->unchanged($label, $data, $status)) {
                return $label;
            }

            $label ??= new ConceptLabel([
                'concept_id' => $concept->id,
                'locale_code' => $localeCode,
            ]);

            $label->fill([
                'pref' => $data['pref'],
                'alt' => $data['alt'],
                'definition' => $data['definition'],
                'slug' => Str::slug($data['pref']),
                'status' => $status,
                'search_text' => $this->searchText($data),
            ]);
            $label->save();

            $revision = ConceptLabelRevision::create([
                'concept_label_id' => $label->id,
                'pref' => $data['pref'],
                'alt' => $data['alt'],
                'definition' => $data['definition'],
                'status' => $status,
                'author_id' => $author?->id,
                'note' => $note,
            ]);

            $label->update(['current_revision_id' => $revision->id]);

            return $label->refresh();
        });
    }

    /**
     * @param  array{pref: string, alt?: array<int, string>, definition?: ?string}  $data
     * @return array{pref: string, alt: array<int, string>, definition: ?string}
     */
    private function validated(array $data): array
    {
        $validated = Validator::make($data, [
            'pref' => ['required', 'string', 'max:200'],
            'alt' => ['array', 'max:10'],
            'alt.*' => ['string', 'max:200'],
            'definition' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        // Alternative names are unique case-insensitively and never repeat the preferred name.
        $pref = trim($validated['pref']);
        $seen = [mb_strtolower($pref) => true];
        $alt = [];

        foreach ($validated['alt'] ?? [] as $name) {
            $name = trim($name);
            $key = mb_strtolower($name);

            if ($key === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $alt[] = $name;
        }

        $definition = trim((string) ($validated['definition'] ?? ''));

        return [
            'pref' => $pref,
            'alt' => $alt,
            'definition' => $definition === '' ? null : $definition,
        ];
    }

    /**
     * @param  array{pref: string, alt: array<int, string>, definition: ?string}  $data
     */
    private function unchanged(ConceptLabel $label, array $data, LabelStatus $status): bool
    {
        return $label->pref === $data['pref']
            && $label->alt === $data['alt']
            && $label->definition === $data['definition']
            && $label->status === $status;
    }

    /**
     * Accent- and case-insensitive text for search: accents are stripped, so a query
     * without diacritics still finds a name with them.
     *
     * @param  array{pref: string, alt: array<int, string>, definition: ?string}  $data
     */
    private function searchText(array $data): string
    {
        $parts = array_filter([$data['pref'], ...$data['alt'], $data['definition']]);

        return mb_strtolower(Str::ascii(implode(' ', $parts)));
    }
}
