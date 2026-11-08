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

    public function label(): string
    {
        return match ($this) {
            self::EarlyPrimary => __('Early primary'),
            self::Primary => __('Primary'),
            self::LowerSecondary => __('Lower secondary'),
            self::UpperSecondary => __('Upper secondary'),
            self::Undergraduate => __('Undergraduate'),
            self::Graduate => __('Graduate'),
        };
    }

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
