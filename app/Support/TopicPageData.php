<?php

namespace App\Support;

use App\Enums\Stage;

/**
 * The choices the topic page makes about its content: which word to circle, where the path's stops go. Pure functions with no database
 * or view in them, so each rule can be tested on its own.
 */
final class TopicPageData
{
    public const MAX_PATH_SIDE = 2;

    public const MAX_NEIGHBOURS = 5;

    /**
     * The index of the word in a title to draw a marker circle around: the longest word of five
     * or more letters that is not the first word (the first earliest on a tie). Null if none.
     */
    public static function circledWordIndex(string $title): ?int
    {
        $best = null;
        $bestLength = 4;

        foreach (explode(' ', $title) as $index => $word) {
            if ($index === 0) {
                continue;
            }

            $length = mb_strlen((string) preg_replace('/[^\p{L}]/u', '', $word));

            if ($length > $bestLength) {
                $best = $index;
                $bestLength = $length;
            }
        }

        return $best;
    }

    /**
     * The definition split into a drop cap and the rest, or null when it should be set plainly:
     * only Latin script gets a drop cap, and only when the display font can draw the letter.
     *
     * @return array{0: string, 1: string}|null
     */
    public static function dropCap(string $definition, ?string $script): ?array
    {
        if ($script !== 'Latn' || $definition === '') {
            return null;
        }

        $first = mb_substr($definition, 0, 1);

        if (preg_match('/^\p{L}$/u', $first) !== 1 || ! DisplayFont::supports($first)) {
            return null;
        }

        return [$first, mb_substr($definition, 1)];
    }

    /**
     * The stops along the path, left to right, placed evenly. Prerequisites come first (the
     * caller has already put required ones before helpful ones), then the topic, then what it
     * leads to, at most two on each side.
     *
     * @param  list<TopicLink>  $before
     * @param  list<TopicLink>  $leadsTo
     * @return list<PathStop>
     */
    public static function pathStops(array $before, string $name, ?string $lang, array $leadsTo): array
    {
        if ($before === [] && $leadsTo === []) {
            return [];
        }

        $raw = [];

        foreach (array_slice($before, 0, self::MAX_PATH_SIDE) as $link) {
            $raw[] = [PathStop::FIRST, $link->name, $link->url, $link->langAttribute()];
        }

        $raw[] = [PathStop::HERE, $name, null, $lang];

        foreach (array_slice($leadsTo, 0, self::MAX_PATH_SIDE) as $link) {
            $raw[] = [PathStop::THEN, $link->name, $link->url, $link->langAttribute()];
        }

        $count = count($raw);
        $stops = [];

        foreach ($raw as $index => $stop) {
            $stops[] = new PathStop($stop[0], $stop[1], $stop[2], $stop[3], round(12 + $index * (76 / ($count - 1)), 2));
        }

        return $stops;
    }

    /**
     * "Primary to lower secondary", or just "Primary" for one stage.
     */
    public static function stageKicker(Stage $from, Stage $to): string
    {
        return $from === $to
            ? $from->label()
            : __(':from to :to', ['from' => $from->label(), 'to' => mb_strtolower($to->label())]);
    }

    /**
     * "Ages 9 to 13" when the stages have ages (from the language files), otherwise the stages
     * in words.
     */
    public static function stageAges(Stage $from, Stage $to): string
    {
        $start = $from->ages();
        $end = $to->ages();

        if ($start !== null && $end !== null) {
            return __('Ages :from to :to', ['from' => $start['from'], 'to' => $end['to']]);
        }

        return self::stageKicker($from, $to);
    }
}
