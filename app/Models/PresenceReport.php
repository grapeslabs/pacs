<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresenceReport extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';

    protected $guarded = [];

    protected $casts = [
        'params' => 'array',
        'result' => 'array',
    ];
}
