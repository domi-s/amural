<?php

namespace App\Enums;

enum EdgeStatus: string
{
    case MachineProposed = 'machine_proposed';
    case Reviewed = 'reviewed';
    case Rejected = 'rejected';
}
