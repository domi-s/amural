<?php

namespace App\Actions\Locales;

use App\Models\User;
use App\Models\UserContentLocale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Sets the languages a user reads and teaches in, in order of preference. Adding, removing
 * and reordering are all this one write. At least one language must remain.
 */
class ReplaceContentLocales
{
    /**
     * @param  list<string>  $codes  Locale codes, most preferred first.
     */
    public function execute(User $user, array $codes): void
    {
        Validator::make(['locales' => $codes], [
            'locales' => ['required', 'array', 'min:1'],
            'locales.*' => ['required', 'string', 'distinct', Rule::exists('locales', 'code')],
        ])->validate();

        DB::transaction(function () use ($user, $codes) {
            UserContentLocale::query()
                ->where('user_id', $user->id)
                ->whereNotIn('locale_code', $codes)
                ->delete();

            $position = 0;

            foreach ($codes as $code) {
                UserContentLocale::query()->updateOrCreate(
                    ['user_id' => $user->id, 'locale_code' => $code],
                    ['position' => $position++],
                );
            }
        });
    }
}
