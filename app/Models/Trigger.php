<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Trigger extends Model
{
    use HasFactory;

    protected $table = 'triggers';
    protected $fillable = [
        'is_active',
        'name',
        'device_type',
        'device_id',
        'bot_id',
        'event_type',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public const DEVICE_CAMERA = 'camera';
    public const DEVICE_TERMINAL = 'terminal';
    public const DEVICE_BARIER = 'barier';
    public const DEVICE_CONTROLLER = 'controller';

    public const EVENT_KNOWED = 'knowed';
    public const EVENT_UNKNOWED = 'unknowed';
    public const EVENT_INCOME = 'income';
    public const EVENT_OUTCOME = 'outcome';
    public const EVENT_MANUAL = 'manual';
    public const EVENT_DENIED = 'denied';


    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class, 'stream_id', 'id');
    }
    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class, 'bot_id', 'id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'trigger_tag', 'trigger_id', 'tag_id');
    }

}
