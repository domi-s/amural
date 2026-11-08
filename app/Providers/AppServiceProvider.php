<?php

namespace App\Providers;

use App\Models\Concept;
use App\Models\Lesson;
use App\Support\LocaleResolver;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One resolver per request, so the language chain is worked out once.
        $this->app->scoped(LocaleResolver::class, fn ($app) => new LocaleResolver($app['request']));
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
