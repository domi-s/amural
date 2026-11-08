<?php

namespace App\Livewire;

use App\Actions\Locales\SwitchContentLocale;
use App\Models\Locale;
use App\Models\User;
use App\Support\LocaleResolver;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * The header language switcher: shows the current content language and lets a visitor pick
 * another of the 26. The write itself is the SwitchContentLocale action.
 */
class LanguageSwitcher extends Component
{
    public function select(string $code, SwitchContentLocale $switch): void
    {
        $user = auth()->user();

        $switch->execute($code, $user instanceof User ? $user : null, session()->driver());

        // Back to the page the visitor was on, without a ?lang= that would override the choice.
        // A full reload, not wire:navigate: the whole page (menus, <html lang>) changes language.
        $this->redirect($this->previousUrlWithoutLang());
    }

    public function render(LocaleResolver $resolver): View
    {
        $locales = Locale::query()->orderByDesc('is_launch')->orderBy('sort')->get();

        $current = $locales->firstWhere('code', $resolver->contentChain()[0] ?? LocaleResolver::DEFAULT)
            ?? $locales->first();

        return view('livewire.language-switcher', [
            'current' => $current,
            'launch' => $locales->where('is_launch', true),
            'others' => $locales->where('is_launch', false),
        ]);
    }

    /**
     * A same-site path and query only, so the redirect can never leave the site.
     */
    private function previousUrlWithoutLang(): string
    {
        $parts = parse_url(url()->previous()) ?: [];

        parse_str($parts['query'] ?? '', $query);
        unset($query['lang']);

        return ($parts['path'] ?? '/').($query === [] ? '' : '?'.http_build_query($query));
    }
}
