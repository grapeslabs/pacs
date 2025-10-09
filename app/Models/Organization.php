<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'inn',
        'full_name',
        'short_name',
        'address',
        'contact_data',
        'comment'
    ];

    public function people(): HasMany
    {
        return $this->hasMany(Person::class);
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value ? html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8') : null,
        );
    }

    protected function shortName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value ? html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8') : null,
        );
    }

    protected function address(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value ? html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8') : null,
        );
    }

    protected function contactData(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value ? html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8') : null,
        );
    }

    protected function comment(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value ? html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8') : null,
        );
    }
}
