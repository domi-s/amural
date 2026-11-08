<?php

namespace App\Enums;

enum Stage: string
{
    case EarlyPrimary = 'early_primary';
    case Primary = 'primary';
    case LowerSecondary = 'lower_secondary';
    case UpperSecondary = 'upper_secondary';
    case Undergraduate = 'undergraduate';
    case Graduate = 'graduate';

    /** Position along the school path, used to check stage_from <= stage_to. */
    public function order(): int
    {
        return match ($this) {
            self::EarlyPrimary => 0,
            self::Primary => 1,
            self::LowerSecondary => 2,
            self::UpperSecondary => 3,
            self::Undergraduate => 4,
            self::Graduate => 5,
        };
    }
}
