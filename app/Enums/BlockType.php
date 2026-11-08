<?php

namespace App\Enums;

enum BlockType: string
{
    case Heading = 'heading';
    case Markdown = 'markdown';
    case Embed = 'embed';
    case Image = 'image';
    case Exercise = 'exercise';
}
