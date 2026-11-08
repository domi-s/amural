<?php

namespace App\Exceptions;

use RuntimeException;

class PrerequisiteCycleException extends RuntimeException
{
    public static function wouldCreateCycle(int $fromId, int $toId): self
    {
        return new self("Adding this prerequisite would create a cycle: concept {$toId} already leads back to concept {$fromId}.");
    }
}
