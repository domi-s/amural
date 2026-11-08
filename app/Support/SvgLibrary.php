<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Reads the hand-drawn SVG sets from resources/svg: interface icons, subject icons and flags.
 * Names are checked, so a template can never reach outside those folders.
 */
class SvgLibrary
{
    /** @var array<string, string> */
    private array $cache = [];

    public function icon(string $name): string
    {
        return $this->read('icons', $name);
    }

    public function subject(string $name): string
    {
        return $this->read('subjects', $name);
    }

    public function flag(string $country): string
    {
        return $this->read('flags', strtoupper($country));
    }

    public function hasFlag(string $country): bool
    {
        return preg_match('/^[A-Za-z]{2}$/', $country) === 1
            && is_file($this->path('flags', strtoupper($country)));
    }

    /**
     * The names available in a group, sorted: "icons", "subjects" or "flags".
     *
     * @return list<string>
     */
    public function names(string $group): array
    {
        $names = array_map(
            fn (string $file) => basename($file, '.svg'),
            glob($this->path($group, '*')) ?: [],
        );

        sort($names);

        return $names;
    }

    private function read(string $group, string $name): string
    {
        if (preg_match('/^[A-Za-z0-9-]+$/', $name) !== 1 || ! is_file($this->path($group, $name))) {
            throw new InvalidArgumentException("There is no {$group} SVG called [{$name}].");
        }

        return $this->cache["{$group}/{$name}"] ??= trim((string) file_get_contents($this->path($group, $name)));
    }

    private function path(string $group, string $name): string
    {
        return resource_path("svg/{$group}/{$name}.svg");
    }
}
