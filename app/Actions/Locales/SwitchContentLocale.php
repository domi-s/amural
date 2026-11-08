<?php

namespace App\Actions\Locales;

use App\Models\Locale;
use App\Models\User;
use App\Models\UserContentLocale;
use App\Support\LocaleResolver;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * The header language switcher. For a signed-in user the chosen language is prepended to
 * their content languages (moved to the front if already there); for a guest it is stored
 * in the session.
 *
 * When the chosen language is one the interface is translated into, the interface follows
 * it too, so the page visibly changes. A language the interface is not translated into only
 * changes which names are shown, not the menus.
 */
class SwitchContentLocale
{
    public function __construct(
        private readonly ReplaceContentLocales $replace,
        private readonly UpdateInterfaceLocale $updateInterface,
    ) {}

    public function execute(string $code, ?User $user, ?Session $session = null): void
    {
        Validator::make(['locale' => $code], [
            'locale' => ['required', 'string', Rule::exists('locales', 'code')],
        ])->validate();

        $interfaceFollows = Locale::query()->whereKey($code)->where('is_interface', true)->exists();

        if ($user === null) {
            $session ??= session()->driver();

            $session->put(LocaleResolver::SESSION_CONTENT, $code);

            if ($interfaceFollows) {
                $session->put(LocaleResolver::SESSION_INTERFACE, $code);
            }

            return;
        }

        DB::transaction(function () use ($user, $code, $interfaceFollows) {
            $current = UserContentLocale::query()
                ->where('user_id', $user->id)
                ->orderBy('position')
                ->pluck('locale_code')
                ->all();

            $this->replace->execute($user, [$code, ...array_values(array_diff($current, [$code]))]);

            if ($interfaceFollows) {
                $this->updateInterface->execute($user, $code);
            }
        });
    }
}
