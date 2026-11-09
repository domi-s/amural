<?php

namespace App\Support;

use App\Models\Locale;
use App\Models\User;
use App\Models\UserContentLocale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Decides which language the interface is in, and which languages to try, in order,
 * when showing content (a topic name, a lesson). One service, used everywhere.
 */
class LocaleResolver
{
    public const DEFAULT = 'en-GB';

    public const SESSION_INTERFACE = 'interface_locale';

    public const SESSION_CONTENT = 'content_locale';

    /** @var Collection<string, Locale>|null */
    private ?Collection $locales = null;

    /** @var list<string>|null */
    private ?array $chain = null;

    private ?int $requestId = null;

    /**
     * @param  Request|Closure(): Request  $source  The request, or something that returns the
     *                                              current one (the container swaps requests).
     */
    public function __construct(private readonly Request|Closure $source) {}

    /**
     * The language of the interface (UI strings), as a locale code.
     *
     * User's interface locale, then (for guests) the session, then the best
     * Accept-Language match among interface locales, then en-GB.
     */
    public function interfaceLocale(): string
    {
        $user = $this->user();
        $stored = $user !== null ? $user->interface_locale : $this->sessionValue(self::SESSION_INTERFACE);

        if ($this->isInterface($stored)) {
            return $stored;
        }

        return $this->acceptedLocales(interfaceOnly: true)[0] ?? self::DEFAULT;
    }

    /**
     * The language code Laravel's translator uses for the interface ("ro" for ro-RO), so
     * __() finds lang/ro.json. Falls back to English.
     */
    public function interfaceLanguage(): string
    {
        $locale = $this->locales()[$this->interfaceLocale()] ?? null;

        return $locale === null ? 'en' : $locale->language;
    }

    /**
     * Remember a valid ?lang= in the session, so the choice sticks on later pages.
     */
    public function rememberRequestedLocale(): void
    {
        $lang = $this->request()->query('lang');

        if ($this->isValid($lang) && $this->request()->hasSession()) {
            $this->request()->session()->put(self::SESSION_CONTENT, $lang);
        }
    }

    /**
     * Locale codes to try, in order, when choosing which label to show. Always unique and,
     * once locales are seeded, always ends with every locale so something can be shown.
     *
     * ?lang= (if valid), the user's content locales in order, the guest session,
     * Accept-Language matches, en-GB, then any (launch locales first, then sort).
     *
     * @return list<string>
     */
    public function contentChain(): array
    {
        $this->request(); // drops cached results if the request has changed

        if ($this->chain !== null) {
            return $this->chain;
        }

        $chain = [];
        $add = function (mixed $code) use (&$chain): void {
            if (is_string($code) && $this->locales()->has($code) && ! in_array($code, $chain, true)) {
                $chain[] = $code;
            }
        };

        $add($this->request()->query('lang'));

        $user = $this->user();
        if ($user !== null) {
            foreach ($this->userContentLocales($user) as $code) {
                $add($code);
            }
        } else {
            $add($this->sessionValue(self::SESSION_CONTENT));
        }

        foreach ($this->acceptedLocales(interfaceOnly: false) as $code) {
            $add($code);
        }

        $add(self::DEFAULT);

        foreach ($this->locales()->keys() as $code) {
            $add($code);
        }

        return $this->chain = $chain;
    }

    /**
     * The signed-in user's own content languages, most preferred first; empty for a guest.
     *
     * @return list<string>
     */
    public function preferredLocales(): array
    {
        $user = $this->user();

        return $user === null ? [] : $this->userContentLocales($user);
    }

    /**
     * Is this a locale content can be written in?
     */
    public function isValid(mixed $code): bool
    {
        return is_string($code) && $this->locales()->has($code);
    }

    /**
     * Forget the cached chain, e.g. after the user changed their languages mid-request.
     */
    public function forget(): void
    {
        $this->chain = null;
        $this->locales = null;
    }

    /**
     * The current request. Cached results belong to one request, so they reset when it changes.
     */
    private function request(): Request
    {
        $request = $this->source instanceof Closure ? ($this->source)() : $this->source;

        if ($this->requestId !== spl_object_id($request)) {
            $this->requestId = spl_object_id($request);
            $this->chain = null;
            $this->locales = null;
        }

        return $request;
    }

    private function user(): ?User
    {
        return $this->request()->user();
    }

    private function isInterface(mixed $code): bool
    {
        return is_string($code) && $this->locales()->get($code)?->is_interface === true;
    }

    private function sessionValue(string $key): mixed
    {
        return $this->request()->hasSession() ? $this->request()->session()->get($key) : null;
    }

    /**
     * All locales keyed by code: launch locales first, then by sort.
     *
     * @return Collection<string, Locale>
     */
    private function locales(): Collection
    {
        return $this->locales ??= Locale::query()
            ->orderByDesc('is_launch')
            ->orderBy('sort')
            ->get()
            ->keyBy('code');
    }

    /**
     * @return list<string>
     */
    private function userContentLocales(User $user): array
    {
        return UserContentLocale::query()
            ->where('user_id', $user->id)
            ->orderBy('position')
            ->pluck('locale_code')
            ->all();
    }

    /**
     * Locale codes matching the Accept-Language header, best first. A tag with a region
     * ("ro-RO") matches that locale; a bare language ("ro") matches the first locale in
     * that language.
     *
     * @return list<string>
     */
    private function acceptedLocales(bool $interfaceOnly): array
    {
        $candidates = $interfaceOnly
            ? $this->locales()->filter(fn (Locale $locale) => $locale->is_interface)
            : $this->locales();

        $matches = [];

        foreach ($this->request()->getLanguages() as $tag) {
            $tag = str_replace('_', '-', $tag);

            $exact = $candidates->first(fn (Locale $locale) => strcasecmp($locale->code, $tag) === 0);
            $byLanguage = $candidates->first(fn (Locale $locale) => strcasecmp($locale->language, $tag) === 0);
            $match = $exact ?? $byLanguage;

            if ($match !== null && ! in_array($match->code, $matches, true)) {
                $matches[] = $match->code;
            }
        }

        return $matches;
    }
}
