<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Equipment extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'type',
        'model',
        'serial_number',
        'status',
        'location',
        'skud_controller_id',
        'person_uid',
        'person_name',
    ];

//    protected $casts = [
//        'created_at' => 'datetime',
//        'updated_at' => 'datetime',
//    ];

    const TYPE_TERMINAL = 'terminal';
    const TYPE_BARRIER = 'barrier';
    const TYPE_CONTROLLER = 'controller';

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_MAINTENANCE = 'maintenance';

    public function getTypeLabelAttribute()
    {
        $labels = [
            self::TYPE_TERMINAL => 'Терминал доступа',
            self::TYPE_BARRIER => 'Шлагбаум',
            self::TYPE_CONTROLLER => 'Контроллер СКУД',
        ];

        return (string) ($labels[$this->type] ?? 'Неизвестный тип');
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            self::STATUS_ACTIVE => 'Активен',
            self::STATUS_INACTIVE => 'Неактивен',
            self::STATUS_MAINTENANCE => 'На обслуживании',
        ];

        return (string) ($labels[$this->status] ?? 'Неизвестный статус');
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOfStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
