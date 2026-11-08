<?php

namespace App\Support;

/**
 * Decides whether a piece of text can be set in the display font, Philibert Italic Rush.
 *
 * The font draws plain Latin letters well, but nearly every accented letter in it is
 * spaced wrongly (its advance width does not match its base letter, so "î" leaves a gap
 * and "á" runs into the next letter) and it has no ă, ș or ț at all. Checked against the
 * font file with fontTools: of the accented Latin letters up to U+024F only
 * à ì í ý ÿ š Ë Ù Û Ÿ have a sane width. Text with anything else, and any Greek or
 * Cyrillic, is set in Inter instead, as the spec already says for Greek and Bulgarian.
 *
 * If the font is ever replaced by a complete one, widen SUPPORTED here.
 */
final class DisplayFont
{
    /** Besides plain ASCII, the only characters the display font draws correctly. */
    private const SUPPORTED = 'àìíýÿšËÙÛŸ–‘’';

    public const DISPLAY = 'font-display italic';

    public const FALLBACK = 'font-semibold';

    public static function supports(string $text): bool
    {
        foreach (mb_str_split($text) as $character) {
            $code = mb_ord($character);

            if ($code >= 0x20 && $code <= 0x7E) {
                continue;
            }

            if (str_contains(self::SUPPORTED, $character)) {
                continue;
            }

            return false;
        }

        return true;
    }

    /**
     * The Tailwind classes to set a heading in: the display font when it can draw every
     * character, otherwise Inter semibold.
     */
    public static function classes(string $text): string
    {
        return self::supports($text) ? self::DISPLAY : self::FALLBACK;
    }
}
