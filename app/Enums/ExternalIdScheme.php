<?php

namespace App\Enums;

enum ExternalIdScheme: string
{
    case LearningCommons = 'learning_commons';
    case Wikidata = 'wikidata';
    case Msc2020 = 'msc2020';
}
