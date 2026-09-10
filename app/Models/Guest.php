<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'external_id',
        'full_name',
        'phone',
        'photo',
        'document',
        'comment',
        'entry_start',
        'entry_end',
    ];

    protected $casts = [
        'photo' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'entry_start' => 'datetime',
        'entry_end' => 'datetime',
    ];

    public static function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        if (strlen($digits) === 11 && $digits[0] === '8') {
            $digits = '7' . substr($digits, 1);
        }

        return $digits;
    }

    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = $value;
        $this->attributes['phone_normalized'] = self::normalizePhone($value) ?: null;
    }

    public function visits()
    {
        return $this->hasMany(GuestVisit::class);
    }

    public function getPhotoUrlsAttribute()
    {
        if (empty($this->photo) || !is_array($this->photo)) {
            return [];
        }

        return array_map(function ($photoPath) {
            return $photoPath ? asset('storage/' . $photoPath) : null;
        }, $this->photo);
    }
}
