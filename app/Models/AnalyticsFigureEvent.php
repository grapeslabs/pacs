<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsFigureEvent extends Model
{
    protected $connection = 'analytic-database';
    protected $table = 'analytics_figure_events';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'datetime' => 'datetime:Y-m-d H:i:s',
        'data' => 'json',
    ];

    public function stream()
    {
        return $this->belongsTo(Stream::class, 'camera_id', 'uid');
    }
}
