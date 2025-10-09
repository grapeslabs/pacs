<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoAnalyticReport extends Model
{
    protected $connection = 'analytic-database';
    protected $table = 'analytics_events';

    protected $casts = [
        'datetime' => 'datetime:Y-m-d H:i:s',
        'data' => 'json',
        'created_at' => 'datetime:Y-m-d H:i:s'
    ];
}
