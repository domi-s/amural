<?php

namespace App\Support;

use RuntimeException;

/**
 * Builds public/svg/flags.svg: every flag from resources/svg/flags as a <symbol>, so pages
 * reference a flag with <use> from one cached file instead of repeating about 4.6 KB of
 * markup per flag. The flags in resources/svg/flags are the source of truth.
 */
class FlagSprite
{
    public const PATH = 'svg/flags.svg';

    public function __construct(private readonly SvgLibrary $svg) {}

    public function build(): string
    {
        $symbols = [];

        foreach ($this->svg->names('flags') as $country) {
            $symbols[] = $this->symbol($country, $this->svg->flag($country));
        }

        return '<svg xmlns="http://www.w3.org/2000/svg">'."\n".implode("\n", $symbols)."\n".'</svg>'."\n";
    }

    public function path(): string
    {
        return public_path(self::PATH);
    }

    public function write(): void
    {
        file_put_contents($this->path(), $this->build());
    }

    public function isUpToDate(): bool
    {
        return is_file($this->path()) && file_get_contents($this->path()) === $this->build();
    }

    private function symbol(string $country, string $svg): string
    {
        if (preg_match('/^<svg [^>]*viewBox="([^"]+)"[^>]*>(.*)<\/svg>$/s', $svg, $m) !== 1) {
            throw new RuntimeException("The flag {$country} is not a plain <svg> with a viewBox.");
        }

        return '<symbol id="flag-'.$country.'" viewBox="'.$m[1].'">'.$m[2].'</symbol>';
    }
}
