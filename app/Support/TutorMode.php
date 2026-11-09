<?php

namespace App\Support;

/**
 * One way of asking the tutor for help, with the words that go with it.
 */
final readonly class TutorMode
{
    /**
     * @param  list<string>  $examples  Things to try, shown as chips that fill the box.
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $placeholder,
        public string $hint,
        public array $examples,
    ) {}
}
