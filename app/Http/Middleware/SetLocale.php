<?php

namespace App\Http\Middleware;

use App\Support\LocaleResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Puts the interface in the visitor's language and remembers a ?lang= choice.
 * Must run after the session has started.
 */
class SetLocale
{
    public function __construct(private readonly LocaleResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->resolver->rememberRequestedLocale();

        $language = $this->resolver->interfaceLanguage();
        app()->setLocale($language);
        Carbon::setLocale($language);

        return $next($request);
    }
}
