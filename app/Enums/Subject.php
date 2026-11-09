<?php

namespace App\Enums;

enum Subject: string
{
    case Mathematics = 'mathematics';

    public function label(): string
    {
        return match ($this) {
            self::Mathematics => __('Mathematics'),
        };
    }

    /** The name of the hand-drawn subject icon in resources/svg/subjects. */
    public function icon(): string
    {
        return match ($this) {
            self::Mathematics => 'maths',
        };
    }
}
