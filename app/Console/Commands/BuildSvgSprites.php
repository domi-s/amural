<?php

namespace App\Console\Commands;

use App\Support\FlagSprite;
use Illuminate\Console\Command;

class BuildSvgSprites extends Command
{
    protected $signature = 'amural:svg-sprites {--check : Only check that the sprite is up to date}';

    protected $description = 'Build public/svg/flags.svg from the flags in resources/svg/flags';

    public function handle(FlagSprite $sprite): int
    {
        if ($this->option('check')) {
            if ($sprite->isUpToDate()) {
                $this->info('public/svg/flags.svg is up to date.');

                return self::SUCCESS;
            }

            $this->error('public/svg/flags.svg is out of date. Run php artisan amural:svg-sprites.');

            return self::FAILURE;
        }

        $sprite->write();
        $this->info('Wrote public/svg/flags.svg.');

        return self::SUCCESS;
    }
}
