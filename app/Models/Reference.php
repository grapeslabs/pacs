<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reference extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'data',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'data' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const TYPE_STATUS = 'status';
    const TYPE_CATEGORY = 'category';
    const TYPE_TYPE = 'type';
    const TYPE_UNIT = 'unit';
    const TYPE_OTHER = 'other';

    public function getTypeLabelAttribute()
    {
        return [
            self::TYPE_STATUS => 'Статусы',
            self::TYPE_CATEGORY => 'Категории',
            self::TYPE_TYPE => 'Типы',
            self::TYPE_UNIT => 'Единицы измерения',
            self::TYPE_OTHER => 'Прочие',
        ][$this->type] ?? $this->type;
    }

    public function getStatusLabelAttribute()
    {
        return $this->is_active ? 'Активен' : 'Неактивен';
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}