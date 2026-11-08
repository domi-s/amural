<?php

namespace App\Exceptions;

use LogicException;

class AppendOnlyViolation extends LogicException
{
    public static function forModel(string $model, string $operation): self
    {
        return new self("{$model} is append-only; {$operation} is not allowed.");
    }
}
