<?php

use App\Actions\Locales\ReplaceContentLocales;
use App\Actions\Locales\UpdateInterfaceLocale;
use App\Actions\Users\UpdateProfile;
use App\Models\Country;
use App\Models\Locale;
use App\Models\UserContentLocale;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component {
    public string $first_name = '';
    public string $last_name = '';
    public string $username = '';
    public string $bio = '';
    public string $country_code = '';
    public int $avatar_preset = 1;

    /** @var list<string> Locale codes, most preferred first. */
    public array $content_locales = [];
    public string $add_locale = '';

    public function mount(): void
    {
        $user = Auth::user();

        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->username = $user->username;
        $this->bio = $user->bio ?? '';
        $this->country_code = $user->country_code ?? '';
        $this->avatar_preset = $user->avatar_preset;

        $this->loadContentLocales();
    }

    public function saveProfile(UpdateProfile $update): void
    {
        $user = $update->execute(Auth::user(), $this->only([
            'first_name', 'last_name', 'username', 'bio', 'country_code', 'avatar_preset',
        ]));

        // Show what was actually saved (trimmed, lowercased username).
        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->username = $user->username;
        $this->bio = $user->bio ?? '';
        $this->country_code = $user->country_code ?? '';

        $this->dispatch('profile-saved');
    }

    public function setInterfaceLocale(string $code, UpdateInterfaceLocale $update): void
    {
        $update->execute(Auth::user(), $code);

        // A full reload, so the whole page comes back in the new language.
        $this->redirect(route('profile'));
    }

    public function addContentLocale(ReplaceContentLocales $replace): void
    {
        $replace->execute(Auth::user(), [...$this->content_locales, $this->add_locale]);

        $this->add_locale = '';
        $this->loadContentLocales();
    }

    public function removeContentLocale(string $code, ReplaceContentLocales $replace): void
    {
        $replace->execute(Auth::user(), array_values(array_diff($this->content_locales, [$code])));

        $this->loadContentLocales();
    }

    public function moveContentLocale(string $code, int $position, ReplaceContentLocales $replace): void
    {
        $others = array_values(array_diff($this->content_locales, [$code]));
        array_splice($others, max(0, min($position, count($others))), 0, [$code]);

        $replace->execute(Auth::user(), $others);

        $this->loadContentLocales();
    }

    private function loadContentLocales(): void
    {
        $this->content_locales = UserContentLocale::query()
            ->where('user_id', Auth::id())
            ->orderBy('position')
            ->pluck('locale_code')
            ->all();
    }

    public function rendering(View $view): void
    {
        $view->title(__('Profile'));
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $locales = Locale::query()->orderByDesc('is_launch')->orderBy('sort')->get();

        return [
            'countries' => Country::query()->orderBy('english_name')->get(),
            'locales' => $locales->keyBy('code'),
            'interfaceLocales' => $locales->where('is_interface', true),
            'addableLocales' => $locales->whereNotIn('code', $this->content_locales),
            'currentInterface' => Auth::user()->interface_locale,
            'contentError' => $this->getErrorBag()->first('locales') ?: $this->getErrorBag()->first('locales.*'),
        ];
    }
}; ?>

<div class="mx-auto flex w-full max-w-3xl flex-col gap-14 pb-16">
    <flux:heading size="xl" level="1">{{ __('Profile') }}</flux:heading>

    {{-- Details --}}
    <form wire:submit="saveProfile" class="flex flex-col gap-6">
        <div class="grid gap-6 sm:grid-cols-2">
            <flux:input wire:model="first_name" :label="__('First name')" type="text" required autocomplete="given-name" />
            <flux:input wire:model="last_name" :label="__('Last name')" type="text" required autocomplete="family-name" />
        </div>

        <flux:input wire:model="username" :label="__('Username')" type="text" required autocomplete="username" />

        <flux:textarea wire:model="bio" :label="__('Bio')" rows="4" maxlength="1000" />

        <flux:select wire:model="country_code" :label="__('Country')">
            <flux:select.option value="">{{ __('Choose a country') }}</flux:select.option>
            @foreach ($countries as $country)
                <flux:select.option value="{{ $country->code }}">
                    {{ $country->native_name }}@if ($country->native_name !== $country->english_name) / {{ $country->english_name }}@endif
                </flux:select.option>
            @endforeach
        </flux:select>

        <fieldset>
            <legend class="mb-2 text-sm font-medium">{{ __('Avatar') }}</legend>
            <div class="flex flex-wrap gap-3">
                @foreach (range(1, 8) as $number)
                    <label class="cursor-pointer">
                        <input type="radio" wire:model="avatar_preset" value="{{ $number }}" class="peer sr-only" />
                        <span class="block rounded-full p-1 ring-2 ring-transparent peer-checked:ring-white peer-focus-visible:ring-sky-400">
                            <x-avatar :preset="$number" size="size-14" />
                        </span>
                    </label>
                @endforeach
            </div>
            @error('avatar_preset')
                <p class="mt-2 text-sm text-red-400" role="alert">{{ $message }}</p>
            @enderror
        </fieldset>

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Save profile') }}</flux:button>

            <x-action-message on="profile-saved">{{ __('Saved.') }}</x-action-message>
        </div>
    </form>

    {{-- Languages --}}
    <section class="flex flex-col gap-10" aria-labelledby="languages-heading">
        <flux:heading id="languages-heading" size="lg" level="2">{{ __('Languages') }}</flux:heading>

        <fieldset>
            <legend class="text-sm font-medium">{{ __('Interface language') }}</legend>
            <p class="mt-1 text-sm opacity-70">{{ __('Menus, buttons and messages are shown in this language.') }}</p>

            <div class="mt-3 flex flex-col gap-2">
                @foreach ($interfaceLocales as $locale)
                    <label class="flex cursor-pointer items-center gap-3">
                        <input
                            type="radio"
                            name="interface_locale"
                            value="{{ $locale->code }}"
                            wire:click="setInterfaceLocale('{{ $locale->code }}')"
                            @checked($locale->code === $currentInterface)
                            class="size-4"
                        />
                        <span lang="{{ $locale->language }}" class="font-medium">{{ $locale->native_name }}</span>
                        @if ($locale->english_name !== $locale->native_name)
                            <span class="text-sm opacity-70">{{ $locale->english_name }}</span>
                        @endif
                    </label>
                @endforeach
            </div>

            @error('interface_locale')
                <p class="mt-2 text-sm text-red-400" role="alert">{{ $message }}</p>
            @enderror
        </fieldset>

        <div>
            <h3 id="content-locales-heading" class="text-sm font-medium">{{ __('Languages I read and teach') }}</h3>
            <p class="mt-1 text-sm opacity-70">{{ __('Topics are shown in the first of these that has a name.') }}</p>

            <ol
                x-sort="$wire.moveContentLocale($item, $position)"
                aria-labelledby="content-locales-heading"
                class="mt-3 flex flex-col gap-2"
            >
                @foreach ($content_locales as $code)
                    @php($locale = $locales[$code])
                    <li
                        wire:key="content-locale-{{ $code }}"
                        x-sort:item="'{{ $code }}'"
                        class="flex items-center gap-3 border border-zinc-600 px-3 py-2"
                    >
                        <span x-sort:handle class="cursor-grab select-none opacity-60" aria-hidden="true">&#8942;&#8942;</span>
                        <span class="w-5 text-sm opacity-60">{{ $loop->iteration }}</span>
                        <span class="flex-1">
                            <span lang="{{ $locale->language }}" class="font-medium">{{ $locale->native_name }}</span>
                            @if ($locale->english_name !== $locale->native_name)
                                <span class="text-sm opacity-70">{{ $locale->english_name }}</span>
                            @endif
                        </span>

                        <flux:button size="sm" variant="ghost" icon="chevron-up" :aria-label="__('Move up')" :disabled="$loop->first" wire:click="moveContentLocale('{{ $code }}', {{ $loop->index - 1 }})" />
                        <flux:button size="sm" variant="ghost" icon="chevron-down" :aria-label="__('Move down')" :disabled="$loop->last" wire:click="moveContentLocale('{{ $code }}', {{ $loop->index + 1 }})" />
                        <flux:button size="sm" variant="ghost" icon="x-mark" :aria-label="__('Remove')" :disabled="count($content_locales) === 1" wire:click="removeContentLocale('{{ $code }}')" />
                    </li>
                @endforeach
            </ol>

            @if ($contentError)
                <p class="mt-2 text-sm text-red-400" role="alert">{{ $contentError }}</p>
            @endif

            <form wire:submit="addContentLocale" class="mt-4 flex items-end gap-3">
                <flux:select wire:model="add_locale" :label="__('Add a language')" class="flex-1">
                    <flux:select.option value="">{{ __('Choose a language') }}</flux:select.option>
                    @foreach ($addableLocales as $locale)
                        <flux:select.option value="{{ $locale->code }}">{{ $locale->native_name }} / {{ $locale->english_name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:button type="submit">{{ __('Add') }}</flux:button>
            </form>
        </div>
    </section>
</div>
