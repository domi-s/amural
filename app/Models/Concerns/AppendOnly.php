<?php

namespace App\Models\Concerns;

use App\Exceptions\AppendOnlyViolation;

/**
 * Rows of append-only tables are never updated or deleted by application code.
 * Undo and correction are new rows, not edits.
 */
trait AppendOnly
{
    public static function bootAppendOnly(): void
    {
        static::updating(function () {
            throw AppendOnlyViolation::forModel(static::class, 'update');
        });

        static::deleting(function () {
            throw AppendOnlyViolation::forModel(static::class, 'delete');
        });
    }
}
