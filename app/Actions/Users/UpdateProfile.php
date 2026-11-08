<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Updates the details a user edits on their profile page. Language settings have their own
 * actions; `name` is generated from first and last name and is never written.
 */
class UpdateProfile
{
    /**
     * @param  array<string, mixed>  $data  first_name, last_name, username, bio, country_code, avatar_preset
     */
    public function execute(User $user, array $data): User
    {
        $data = $this->normalised($data);

        $validated = Validator::make($data, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'username' => [
                'required', 'string', 'min:3', 'max:30',
                // Lowercase letters and digits, optionally separated by single dots, hyphens or underscores.
                'regex:/^[a-z0-9]+([._-][a-z0-9]+)*$/',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'bio' => ['nullable', 'string', 'max:1000'],
            'country_code' => ['nullable', 'string', Rule::exists('countries', 'code')],
            'avatar_preset' => ['required', 'integer', 'between:1,8'],
        ])->validate();

        return DB::transaction(function () use ($user, $validated) {
            $user->update($validated);

            return $user->refresh();
        });
    }

    /**
     * Trim text, lowercase the username, and turn blank optional fields into null.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalised(array $data): array
    {
        foreach (['first_name', 'last_name', 'username', 'bio'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim($data[$field]);
            }
        }

        if (isset($data['username']) && is_string($data['username'])) {
            $data['username'] = mb_strtolower($data['username']);
        }

        foreach (['bio', 'country_code'] as $field) {
            if (isset($data[$field]) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }
}
