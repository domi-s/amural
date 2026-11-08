<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Locale extends Model
{
    /** @use HasFactory<\Database\Factories\LocaleFactory> */
    use HasFactory;

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'is_interface' => 'boolean',
        'is_launch' => 'boolean',
    ];
}
