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

    /** The kind with its article, for the kicker above a topic's title ("A procedure"). */
    public function withArticle(): string
    {
        return match ($this) {
            self::Definition => __('A definition'),
            self::Concept => __('A concept'),
            self::Procedure => __('A procedure'),
            self::Result => __('A result'),
            self::Representation => __('A representation'),
            self::Application => __('An application'),
        };
    }
}
