<?php

namespace App\Enums;

enum ConceptStatus: string
{
    case MachineProposed = 'machine_proposed';
    case Draft = 'draft';
    case Reviewed = 'reviewed';
    case Deprecated = 'deprecated';
}
