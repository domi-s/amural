<?php

use App\Http\Middleware\ContentSecurityPolicy;
use Database\Factories\ConceptFactory;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LocaleSeeder;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->seed([LocaleSeeder::class, CountrySeeder::class]);
});

const THIRD_PARTY_FONT_HOSTS = ['googleapis', 'gstatic', 'fonts.google', 'bunny.net', 'next/font'];

/** @return list<string> the pages a visitor can open without signing in */
function publicPages(): array
{
    return ['/', '/login', '/register'];
}

// Self-hosted body font

it('declares one Geologica face per subset, each variable, swapping and limited by unicode-range', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    preg_match_all("/@font-face \{\s*font-family: 'Geologica';(.*?)\}/s", $css, $faces);

    expect($faces[1])->toHaveCount(5);

    foreach ($faces[1] as $face) {
        expect($face)->toContain('font-weight: 100 900')
            ->toContain('font-display: swap')
            ->toContain("format('woff2-variations')")
            ->toContain('unicode-range: U+');
    }

    foreach (['latin', 'latin-ext', 'greek', 'cyrillic', 'cyrillic-ext'] as $subset) {
        expect($css)->toContain("geologica-{$subset}-wght-normal.woff2");
        expect(is_file(resource_path("fonts/geologica/geologica-{$subset}-wght-normal.woff2")))->toBeTrue();
    }
});

it('keeps the font licence beside the font files', function () {
    $licence = resource_path('fonts/geologica/OFL.txt');

    expect(is_file($licence))->toBeTrue();
    expect(file_get_contents($licence))->toContain('SIL Open Font License, Version 1.1')->toContain('Geologica Project Authors');
});

it('uses the body font for body and interface text, at the weights that suit Geologica', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)->toContain("--font-body: 'Geologica', system-ui, sans-serif;")
        ->toContain('--font-sans: var(--font-body);')
        ->toMatch('/body \{\s*font-family: var\(--font-body\);\s*font-weight: 350;/')
        ->toMatch('/font-weight: 450;/');
});

it('keeps the display font self-hosted too', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)->toContain("url('../fonts/PhiliItaRus.woff')");
    expect(is_file(resource_path('fonts/PhiliItaRus.woff')))->toBeTrue();
    expect($css)->not->toMatch('/url\(\s*[\'"]?https?:/i');
});

it('preloads only the Latin font file', function () {
    foreach (publicPages() as $page) {
        $html = $this->get($page)->assertOk()->content();

        preg_match_all('/<link rel="preload"[^>]*as="font"[^>]*>/', $html, $preloads);

        expect($preloads[0])->toHaveCount(1, "{$page} should preload exactly one font");
        expect($preloads[0][0])->toContain('geologica-latin-wght-normal')->toContain('crossorigin');
    }
});

// No third-party fonts

it('serves no public page that mentions a third-party font host', function () {
    foreach (publicPages() as $page) {
        $html = strtolower($this->get($page)->assertOk()->content());

        foreach (THIRD_PARTY_FONT_HOSTS as $host) {
            expect($html)->not->toContain($host);
        }
    }
});

it('has no third-party font host or the old font anywhere in the source', function () {
    $folders = ['app', 'bootstrap', 'config', 'resources', 'routes', 'public/svg', 'database'];
    $found = [];

    foreach ($folders as $folder) {
        foreach (File::allFiles(base_path($folder)) as $file) {
            if (! in_array($file->getExtension(), ['php', 'js', 'css', 'svg', 'json', 'html'], true)) {
                continue;
            }

            $text = file_get_contents($file->getPathname());

            foreach (THIRD_PARTY_FONT_HOSTS as $host) {
                if (stripos($text, $host) !== false) {
                    $found[] = "{$file->getRelativePathname()} mentions {$host}";
                }
            }

            // The font we left behind, as a word (not "interface").
            if (preg_match('/\bInter\b(?! (?:Plus|Regular))/', $text) === 1 && ! str_contains($file->getPathname(), 'node_modules')) {
                $found[] = "{$file->getRelativePathname()} mentions the font Inter";
            }
        }
    }

    expect($found)->toBe([]);
});

it('has no font package from a third party in package.json', function () {
    $package = file_get_contents(base_path('package.json'));

    expect($package)->not->toContain('fontsource')->not->toContain('inter');
});

// Content-Security-Policy

it('sends a policy that allows fonts and styles only from this site', function () {
    $response = $this->get('/')->assertOk();

    $policy = $response->headers->get('Content-Security-Policy');

    expect($policy)->toBe("font-src 'self'; style-src 'self' 'unsafe-inline'");
});

it('sends the policy on every kind of response', function () {
    $this->get('/topics')->assertHeader('Content-Security-Policy');
    $this->get('/login')->assertHeader('Content-Security-Policy');
    $this->get('/topic/c_NOPE0000')->assertNotFound()->assertHeader('Content-Security-Policy');
});

it('never allows a Google font host in the policy', function () {
    $policy = strtolower((new ContentSecurityPolicy)->policy());

    expect($policy)->not->toContain('google')->not->toContain('gstatic')->not->toContain('*')->not->toContain('https:');
});

it('allows the Vite dev server only when it is running locally', function () {
    $local = new class extends ContentSecurityPolicy
    {
        protected function devServerSources(): array
        {
            return ['http://localhost:5173'];
        }
    };

    expect($local->policy())->toBe("font-src 'self' http://localhost:5173; style-src 'self' 'unsafe-inline' http://localhost:5173");
    // In the test environment (not local) nothing extra is allowed.
    expect((new ContentSecurityPolicy)->policy())->not->toContain('5173')->not->toContain('http:');
});

it('names the dev server by its origin, and by scheme only when its address is IPv6', function () {
    expect(ContentSecurityPolicy::sourcesFor('http://localhost:5173'))->toBe(['http://localhost:5173']);
    expect(ContentSecurityPolicy::sourcesFor('http://127.0.0.1:5173'))->toBe(['http://127.0.0.1:5173']);
    // A CSP source list cannot name [::1]: the browser would ignore it and block the dev server.
    expect(ContentSecurityPolicy::sourcesFor('http://[::1]:5173'))->toBe(['http:']);
    expect(ContentSecurityPolicy::sourcesFor('not a url'))->toBe([]);
});

it('still blocks every https font host in local development, even with the IPv6 dev server', function () {
    $policy = "font-src 'self' ".implode(' ', ContentSecurityPolicy::sourcesFor('http://[::1]:5173'));

    // "http:" matches only plain http; the Google and Bunny font hosts are https.
    expect($policy)->toBe("font-src 'self' http:")->not->toContain('https');
});
