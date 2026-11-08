<?php

namespace App\Enums;

enum ArtifactType: string
{
    case Youtube = 'youtube';
    case VideoUrl = 'video_url';
    case Image = 'image';
    case File = 'file';
    case Worksheet = 'worksheet';
    case Link = 'link';
}
