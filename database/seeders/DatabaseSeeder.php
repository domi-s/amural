<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            LocaleSeeder::class,
            CountrySeeder::class,
            ConceptSpineSeeder::class,
        ]);
    }
}
