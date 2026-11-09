<?php

namespace App\Livewire;

use App\Actions\Locales\SwitchContentLocale;
use App\Models\Locale;
use App\Models\User;
use App\Support\FlagSprite;
use App\Support\LanguageChoices;
use App\Support\LocaleResolver;
use App\Support\SvgLibrary;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
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
            'mapConfig' => $this->mapConfig($locales, $current),
        ]);
    }

    /**
     * What the map of Europe needs: where its files are, the languages by the codes the map
     * uses, and the words it shows. The map itself is fetched only when the Map tab opens.
     *
     * @param  Collection<int, Locale>  $locales
     * @return array<string, mixed>
     */
    private function mapConfig(Collection $locales, ?Locale $current): array
    {
        return [
            'mapUrl' => asset('svg/europe-map.svg'),
            'flagsUrl' => asset(FlagSprite::PATH),
            'flags' => app(SvgLibrary::class)->names('flags'),
            'current' => $current?->language,
            'currentCode' => $current?->code,
            'languages' => LanguageChoices::byLanguage($locales),
            'labels' => ['choose' => __('Choose a country on the map')],
        ];
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
