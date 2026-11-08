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
}
