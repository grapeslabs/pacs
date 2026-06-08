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
        'person_id'
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
