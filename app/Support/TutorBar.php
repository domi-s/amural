<?php

namespace App\Support;

/**
 * The words and modes of the tutor bar on the topics page.
 *
 * The tutor is not a question-answering chatbot. It does three jobs, and the bar makes you choose
 * one: find a lesson, find exercises, or guide you to the answer yourself, with hints and questions
 * rather than the answer. Nothing here talks to a model yet; this only decides what the bar says.
 */
final class TutorBar
{
    public const FIND_LESSON = 'lesson';

    public const FIND_EXERCISES = 'practice';

    public const GUIDE = 'guide';

    /**
     * @return list<TutorMode>
     */
    public static function modes(): array
    {
        return [
            new TutorMode(
                key: self::FIND_LESSON,
                label: __('Find a lesson'),
                placeholder: __('What do you need to teach or learn? For example: adding fractions with unlike denominators, ages 10 to 12'),
                hint: __('We search the lessons teachers have shared and show you the best ones, in your language.'),
                examples: [
                    __('Adding fractions with different denominators'),
                    __('How plants make food, ages 9 to 11'),
                    __('The causes of the First World War'),
                ],
            ),
            new TutorMode(
                key: self::FIND_EXERCISES,
                label: __('Find exercises'),
                placeholder: __('What do you want to practise? For example: 15 questions on solving linear equations'),
                hint: __('We find exercises at the right level, and can put a set together for you.'),
                examples: [
                    __('Solving linear equations, easy to hard'),
                    __('Past tense verbs, ten questions'),
                    __('Reading a bar chart'),
                ],
            ),
            new TutorMode(
                key: self::GUIDE,
                label: __('Guide me'),
                placeholder: __("Tell us the problem you're stuck on, and what you've tried so far."),
                hint: __('Hints, not answers. We ask questions and point the way, so you work it out yourself.'),
                examples: [
                    __("I don't get why you flip the second fraction when dividing"),
                    __('I solved 3x + 5 = 20 and got x = 8'),
                    __('How do I start an essay on the French Revolution?'),
                ],
            ),
        ];
    }

    /**
     * The question above the bar. One of four, chosen by `$seed` (the day of the year, so a visitor
     * sees the same one all day), and with the visitor's first name when there is one.
     */
    public static function headline(?string $firstName, int $seed): string
    {
        $name = $firstName === null ? '' : trim($firstName);
        $choice = abs($seed) % 4;

        if ($name === '') {
            return match ($choice) {
                0 => __('What would you like to learn today?'),
                1 => __('What are we working on?'),
                2 => __('Stuck on something?'),
                default => __('Where shall we start?'),
            };
        }

        return match ($choice) {
            0 => __('What would you like to learn today, :name?', ['name' => $name]),
            1 => __('What are we working on, :name?', ['name' => $name]),
            2 => __('Stuck on something, :name?', ['name' => $name]),
            default => __('Where shall we start, :name?', ['name' => $name]),
        };
    }
}
