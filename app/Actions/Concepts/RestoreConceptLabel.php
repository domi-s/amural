<?php

namespace App\Actions\Concepts;

use App\Models\ConceptLabel;
use App\Models\ConceptLabelRevision;
use App\Models\User;

/**
 * Puts an old version of a name back as a new revision. History is never rewritten.
 */
class RestoreConceptLabel
{
    public function __construct(private UpdateConceptLabel $update) {}

    public function execute(ConceptLabelRevision $revision, User $author): ConceptLabel
    {
        $label = $revision->label;

        return $this->update->execute(
            $label->concept,
            $label->locale_code,
            ['pref' => $revision->pref, 'alt' => $revision->alt, 'definition' => $revision->definition],
            $author,
            note: __('Restored the version of :date', ['date' => $revision->created_at->toDateString()]),
        );
    }
}
