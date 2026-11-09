<?php

namespace App\Providers;

use App\Models\Concept;
use App\Models\Lesson;
use App\Support\CountryNames;
use App\Support\LocaleResolver;
use App\Support\SvgLibrary;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SvgLibrary::class);
        $this->app->scoped(CountryNames::class);

        // One resolver per request, so the language chain is worked out once.
        $this->app->scoped(LocaleResolver::class, fn ($app) => new LocaleResolver(fn () => $app['request']));
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
