<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Key extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPES =[
        'Mifare' => 'Mifare',
    ];
    protected $fillable = [
        'key',
        'type',
        'person_id',
        'is_active',
        'issued_at',
        'expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'issued_at' => 'date',
        'expires_at' => 'date',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
