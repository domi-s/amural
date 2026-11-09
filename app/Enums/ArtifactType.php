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

    public function label(): string
    {
        return match ($this) {
            self::Youtube, self::VideoUrl => __('Video'),
            self::Image => __('Image'),
            self::File => __('File'),
            self::Worksheet => __('Worksheet'),
            self::Link => __('Link'),
        };
    }

    /** The name of the interface icon in resources/svg/icons. */
    public function icon(): string
    {
        return match ($this) {
            self::Youtube, self::VideoUrl => 'play',
            self::Image => 'image',
            self::File, self::Worksheet => 'document',
            self::Link => 'globe',
        };
    }
}
