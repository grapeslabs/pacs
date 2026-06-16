<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActuatorDevice extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'driver_key',
        'settings',
        'status',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            self::STATUS_ACTIVE => 'Активно',
            self::STATUS_INACTIVE => 'Неактивно',
        ];

        return (string) ($labels[$this->status] ?? 'Неизвестный статус');
    }

    public function getDriverLabelAttribute(): string
    {
        $manager = app(\App\Actuators\ActuatorDriverManager::class);

        return $manager->has((string) $this->driver_key)
            ? $manager->options()[$this->driver_key]
            : (string) $this->driver_key;
    }

    public function getData(string $key, $default = null)
    {
        return data_get($this->settings ?? [], $key, $default);
    }

    public function setData(string $key, $value)
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->settings = $settings;
        return $this;
    }

    public function scopeOfStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
