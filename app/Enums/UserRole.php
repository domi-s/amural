<?php

namespace App\Enums;

enum UserRole: string
{
    case Teacher = 'teacher';
    case Curator = 'curator';
    case Admin = 'admin';
}
