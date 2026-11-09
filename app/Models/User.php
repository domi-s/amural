<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\Models\Concerns\HasPublicId;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable // implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPublicId, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * `name` is a generated column (first_name + last_name) and is never written.
     *
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'username',
        'first_name',
        'last_name',
        'email',
        'password',
        'interface_locale',
        'country_code',
        'bio',
        'avatar_preset',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'avatar_preset' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->username ??= $user->newUsername();
            $user->interface_locale ??= 'en-GB';
            $user->avatar_preset ??= random_int(1, 8);
        });
    }

    public function publicIdPrefix(): string
    {
        return 'u';
    }

    /** Curators and admins decide what is reviewed. */
    public function isCurator(): bool
    {
        return in_array($this->role, [UserRole::Curator, UserRole::Admin], true);
    }

    /**
     * A default username from the name, made unique with a number suffix.
     */
    private function newUsername(): string
    {
        $base = Str::of(Str::ascii($this->first_name.' '.$this->last_name))->lower()->slug('.')->value() ?: 'user';

        do {
            $candidate = $base.random_int(1, 99999);
        } while (static::query()->where('username', $candidate)->exists());

        return $candidate;
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    /** @return HasMany<UserContentLocale, $this> */
    public function contentLocales(): HasMany
    {
        return $this->hasMany(UserContentLocale::class)->orderBy('position');
    }

    /** @return BelongsTo<Locale, $this> */
    public function interfaceLocale(): BelongsTo
    {
        return $this->belongsTo(Locale::class, 'interface_locale', 'code');
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }
}
