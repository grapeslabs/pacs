<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CrossingEvent extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'crossing_events';

    protected $guarded = [];

    public const TYPE_CROSS = 'cross';

    public const TYPES = [
        self::TYPE_CROSS => 'Пересечение линии',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function imageUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('analytic')->url($this->photo_path) : null;
    }
}
