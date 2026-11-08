<?php

namespace App\Enums;

enum EdgeType: string
{
    case Broader = 'broader';
    case Prerequisite = 'prerequisite';
    case SpecialCase = 'special_case';
    case Related = 'related';
}
