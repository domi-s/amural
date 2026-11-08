<?php

namespace App\Providers;

use App\Models\Concept;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Short, stable names for polymorphic attachments (artifactables.artifactable_type).
        Relation::enforceMorphMap([
            'concept' => Concept::class,
            'lesson' => Lesson::class,
        ]);
    }
}
