<?php

namespace App\Actions\Locales;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Changes the language the interface is shown in. Only locales the interface is translated
 * into are allowed.
 */
class UpdateInterfaceLocale
{
    public function execute(User $user, string $code): User
    {
        Validator::make(['interface_locale' => $code], [
            'interface_locale' => ['required', 'string', Rule::exists('locales', 'code')->where('is_interface', true)],
        ])->validate();

        return DB::transaction(function () use ($user, $code) {
            $user->update(['interface_locale' => $code]);

            return $user;
        });
    }
}
