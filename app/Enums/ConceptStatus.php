<?php

namespace App\Enums;

enum ConceptStatus: string
{
    case MachineProposed = 'machine_proposed';
    case Draft = 'draft';
    case Reviewed = 'reviewed';
    case Deprecated = 'deprecated';

    public function label(): string
    {
        return match ($this) {
            self::MachineProposed => __('Machine proposed'),
            self::Draft => __('Draft'),
            self::Reviewed => __('Reviewed'),
            self::Deprecated => __('Deprecated'),
        };
    }
}
