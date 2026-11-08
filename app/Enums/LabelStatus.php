<?php

namespace App\Enums;

enum LabelStatus: string
{
    case Machine = 'machine';
    case Draft = 'draft';
    case Reviewed = 'reviewed';
}
