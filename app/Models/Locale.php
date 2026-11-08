<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Locale extends Model
{
    /** @use HasFactory<\Database\Factories\LocaleFactory> */
    use HasFactory;

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * The native name as it is shown: with a capital first letter ("română" -> "Română").
     * The stored name stays as the spec lists it.
     *
     * @return Attribute<string, never>
     */
    protected function displayName(): Attribute
    {
        return Attribute::get(fn (): string => Str::ucfirst($this->native_name));
    }

    protected $casts = [
        'is_interface' => 'boolean',
        'is_launch' => 'boolean',
    ];
}
