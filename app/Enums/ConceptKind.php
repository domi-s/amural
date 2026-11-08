<?php

namespace App\Enums;

enum ConceptKind: string
{
    case Definition = 'definition';
    case Concept = 'concept';
    case Procedure = 'procedure';
    case Result = 'result';
    case Representation = 'representation';
    case Application = 'application';

    public function label(): string
    {
        return match ($this) {
            self::Definition => __('Definition'),
            self::Concept => __('Concept'),
            self::Procedure => __('Procedure'),
            self::Result => __('Result'),
            self::Representation => __('Representation'),
            self::Application => __('Application'),
        };
    }
}
