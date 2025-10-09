<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Guest extends Model
{
    use HasFactory;

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
