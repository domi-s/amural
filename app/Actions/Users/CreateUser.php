<?php

namespace App\Actions\Users;

use App\Actions\Locales\ReplaceContentLocales;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Creates a user together with the interface language and at least one content language
 * every user must have.
 */
class CreateUser
{
    public function __construct(private readonly ReplaceContentLocales $replace) {}

    /**
     * @param  array<string, mixed>  $attributes  first_name, last_name, email, password (already hashed)
     * @param  list<string>  $contentLocales  Locale codes, most preferred first.
     */
    public function execute(array $attributes, string $interfaceLocale, array $contentLocales): User
    {
        Validator::make(['interface_locale' => $interfaceLocale], [
            'interface_locale' => ['required', 'string', Rule::exists('locales', 'code')->where('is_interface', true)],
        ])->validate();

        return DB::transaction(function () use ($attributes, $interfaceLocale, $contentLocales) {
            $user = User::query()->create([...$attributes, 'interface_locale' => $interfaceLocale]);

            $this->replace->execute($user, $contentLocales);

            return $user;
        });
    }
}
