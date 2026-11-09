<?php

namespace App\Support;

/**
 * Compares two texts word by word, for the history page.
 */
final class WordDiff
{
    public const SAME = 'same';

    public const ADDED = 'added';

    public const REMOVED = 'removed';

    /**
     * Segments in reading order, with neighbouring words of the same kind joined.
     *
     * @return list<array{0: string, 1: string}> [kind, text]
     */
    public static function between(string $old, string $new): array
    {
        $a = self::words($old);
        $b = self::words($new);

        // lengths[i][j]: how many words the tails $a[i..] and $b[j..] have in common, in order.
        $lengths = array_fill(0, count($a) + 1, array_fill(0, count($b) + 1, 0));

        for ($i = count($a) - 1; $i >= 0; $i--) {
            for ($j = count($b) - 1; $j >= 0; $j--) {
                $lengths[$i][$j] = $a[$i] === $b[$j]
                    ? $lengths[$i + 1][$j + 1] + 1
                    : max($lengths[$i + 1][$j], $lengths[$i][$j + 1]);
            }
        }

        $segments = [];
        $i = $j = 0;

        while ($i < count($a) || $j < count($b)) {
            if ($i < count($a) && $j < count($b) && $a[$i] === $b[$j]) {
                self::push($segments, self::SAME, $a[$i++]);
                $j++;
            } elseif ($j < count($b) && ($i === count($a) || $lengths[$i][$j + 1] > $lengths[$i + 1][$j])) {
                self::push($segments, self::ADDED, $b[$j++]);
            } else {
                self::push($segments, self::REMOVED, $a[$i++]);
            }
        }

        return $segments;
    }

    /** @return list<string> Words and the spaces between them, so the text can be put back together. */
    private static function words(string $text): array
    {
        return preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /** @param  list<array{0: string, 1: string}>  $segments */
    private static function push(array &$segments, string $kind, string $text): void
    {
        $last = count($segments) - 1;

        if ($last >= 0 && $segments[$last][0] === $kind) {
            $segments[$last][1] .= $text;
        } else {
            $segments[] = [$kind, $text];
        }
    }
}
