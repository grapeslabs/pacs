<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GrapeslabsSkudController extends Model
{
    use HasFactory;

    protected $table = 'grapeslabs_skud_controllers';

    protected $fillable = [
        'serial_number',
        'type',
        'ip',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const TYPE_PINTERM = 'pinterm';
    const TYPE_IRONLOGIC = 'ironlogic';
    const TYPE_Z5RWEB = 'z5rweb';

    public function getTypeLabelAttribute(): string
    {
        $labels = [
            self::TYPE_PINTERM => 'PinTerm',
            self::TYPE_IRONLOGIC => 'IronLogic',
            self::TYPE_Z5RWEB => 'Z5RWeb',
        ];

        return $labels[$this->type] ?? 'Неизвестный тип';
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeBySerialNumber($query, $serialNumber)
    {
        return $query->where('serial_number', $serialNumber);
    }
}
