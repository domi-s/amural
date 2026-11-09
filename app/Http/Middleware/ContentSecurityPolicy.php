<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A Content-Security-Policy that keeps third-party fonts and styles out for good: fonts load only
 * from our own origin and stylesheets only from our own origin or inline (Livewire and Alpine need
 * inline styles). Fonts are self-hosted, so a Google or other CDN font link added by mistake is
 * blocked by the browser instead of quietly leaking visitors' addresses.
 *
 * Only fonts and styles are restricted; scripts, images and frames are left as they were.
 */
class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Content-Security-Policy', $this->policy());

        return $response;
    }

    public function policy(): string
    {
        $dev = $this->devServerSources();

        $directives = [
            'font-src' => ["'self'", ...$dev],
            'style-src' => ["'self'", "'unsafe-inline'", ...$dev],
        ];

        return collect($directives)
            ->map(fn (array $sources, string $name) => $name.' '.implode(' ', $sources))
            ->implode('; ');
    }

    /**
     * In local development `npm run dev` serves the CSS and fonts from the Vite dev server, whose
     * address is written to public/hot. That server is allowed locally and nowhere else.
     *
     * @return list<string>
     */
    protected function devServerSources(): array
    {
        if (! app()->isLocal() || ! is_file(public_path('hot'))) {
            return [];
        }

        return self::sourcesFor(trim((string) file_get_contents(public_path('hot'))));
    }

    /**
     * The CSP source for a dev server address: its origin, or, for an IPv6 address (which a CSP
     * source list cannot express, so the browser would ignore it and block the dev server), the
     * plain-http scheme. That still blocks every https font host.
     *
     * @return list<string>
     */
    public static function sourcesFor(string $devServerUrl): array
    {
        $parts = parse_url($devServerUrl);

        if (! isset($parts['scheme'], $parts['host'])) {
            return [];
        }

        if (str_contains($parts['host'], ':') || str_starts_with($parts['host'], '[')) {
            return [$parts['scheme'].':'];
        }

        return [$parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '')];
    }
}
