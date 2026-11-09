<?php

namespace App\Support;

use App\Models\Artifact;
use App\Models\Locale;

/**
 * One video, file or link on a topic page. `locale` is null for a language-neutral artifact.
 */
final readonly class ArtifactEntry
{
    public function __construct(
        public Artifact $artifact,
        public ?Locale $locale,
        public ?string $host,
    ) {}

    /**
     * The artifact's URL, but only if it is a plain http(s) link, so a stored "javascript:"
     * address can never become a clickable link.
     */
    public function safeUrl(): ?string
    {
        $url = $this->artifact->url;

        return is_string($url) && preg_match('#^https?://#i', $url) === 1 ? $url : null;
    }

    public function isYoutube(): bool
    {
        return $this->artifact->youtube_id !== null && $this->artifact->youtube_id !== '';
    }
}
