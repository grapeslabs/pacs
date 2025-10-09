<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    use HasFactory;
    protected $table = 'api_keys';

    protected $fillable = [
        'name',
        'key',
        'expires_at',
        'is_unlimited',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_unlimited' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->key = bin2hex(random_bytes(32));
        });

        static::saving(function ($model) {
            if ($model->is_unlimited) {
                $model->expires_at = null;
            }
        });
    }


}
