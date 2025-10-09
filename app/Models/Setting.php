<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'value',
        'type',
        'options',
        'group',
        'sort_order',
        'is_public',
        'is_encrypted'
    ];

    protected $casts = [
        'options' => 'array',
        'is_public' => 'boolean',
        'is_encrypted' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const TYPE_TEXT = 'text';
    const TYPE_TEXTAREA = 'textarea';
    const TYPE_NUMBER = 'number';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_SELECT = 'select';
    const TYPE_JSON = 'json';
    const TYPE_PASSWORD = 'password';

    const GROUP_GENERAL = 'general';
    const GROUP_SYSTEM = 'system';
    const GROUP_INTEGRATION = 'integration';
    const GROUP_EMAIL = 'email';
    const GROUP_SECURITY = 'security';
    const GROUP_OTHER = 'other';

    public function getValueAttribute($value)
    {
        if ($this->is_encrypted && !empty($value)) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value;
            }
        }

        return $value;
    }

    public function setValueAttribute($value)
    {
        if ($this->is_encrypted && !empty($value)) {
            $value = Crypt::encryptString($value);
        }

        $this->attributes['value'] = $value;
    }

    public function getTypeLabelAttribute()
    {
        return [
            self::TYPE_TEXT => 'Текст',
            self::TYPE_TEXTAREA => 'Текстовое поле',
            self::TYPE_NUMBER => 'Число',
            self::TYPE_BOOLEAN => 'Да/Нет',
            self::TYPE_SELECT => 'Выпадающий список',
            self::TYPE_JSON => 'JSON',
            self::TYPE_PASSWORD => 'Пароль',
        ][$this->type] ?? $this->type;
    }

    public function getGroupLabelAttribute()
    {
        return [
            self::GROUP_GENERAL => 'Основные',
            self::GROUP_SYSTEM => 'Системные',
            self::GROUP_INTEGRATION => 'Интеграции',
            self::GROUP_EMAIL => 'Email',
            self::GROUP_SECURITY => 'Безопасность',
            self::GROUP_OTHER => 'Прочие',
        ][$this->group] ?? $this->group;
    }

    public function scopeOfGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public static function getValue($key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }
}