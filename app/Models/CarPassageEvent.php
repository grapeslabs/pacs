<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CarPassageEvent extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'car_passage_events';

    public const STATUS_ALLOWED = 'allowed';
    public const STATUS_DENIED = 'denied';
    public const STATUS_IN_DB = 'in_db';
    public const STATUS_NOT_IN_DB = 'not_in_db';
    public const STATUS_NOT_RECOGNIZED = 'not_recognized';

    public const STATUSES = [
        self::STATUS_ALLOWED        => 'Проезд разрешён',
        self::STATUS_DENIED         => 'Проезд запрещён',
        self::STATUS_IN_DB          => 'В базе',
        self::STATUS_NOT_IN_DB      => 'Не в базе',
        self::STATUS_NOT_RECOGNIZED => 'Не распознан',
    ];

    protected $fillable = [
        'recognized_plate_id',
        'plate_text',
        'camera_id',
        'stream_id',
        'car_id',
        'car_passage_rule_id',
        'rule_name',
        'passage_id',
        'direction',
        'status',
        'is_authorized',
        'controllers',
        'image_path',
        'plate_image_path',
        'recognized_at',
    ];

    protected $casts = [
        'controllers'   => 'array',
        'is_authorized' => 'boolean',
        'recognized_at' => 'datetime',
    ];

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class, 'stream_id');
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'car_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(CarPassageRule::class, 'car_passage_rule_id');
    }

    public function passage(): BelongsTo
    {
        return $this->belongsTo(Passage::class, 'passage_id');
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('lpr-events')->url($this->image_path) : null;
    }

    public function plateImageUrl(): ?string
    {
        return $this->plate_image_path ? Storage::disk('lpr-events')->url($this->plate_image_path) : null;
    }
}
