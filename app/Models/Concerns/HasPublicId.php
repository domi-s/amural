<?php

namespace App\Models\Concerns;

/**
 * A URL-safe id: a prefix plus 8 Crockford base32 characters, e.g. c_7Q2K9M3X.
 * Internal bigint keys never leave the database.
 */
trait HasPublicId
{
    private const CROCKFORD = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    public static function bootHasPublicId(): void
    {
        static::creating(function ($model) {
            if (blank($model->public_id)) {
                $model->public_id = $model->newPublicId();
            }
        });
    }

    abstract public function publicIdPrefix(): string;

    protected function newPublicId(): string
    {
        do {
            $candidate = $this->publicIdPrefix().'_'.self::randomCrockford(8);
        } while (static::query()->where('public_id', $candidate)->exists());

        return $candidate;
    }

    private static function randomCrockford(int $length): string
    {
        $alphabet = self::CROCKFORD;
        $out = '';

        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $out;
    }
}
