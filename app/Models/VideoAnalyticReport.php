<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoAnalyticReport extends Model
{
    protected $connection = 'analytic-database';
    protected $table = 'analytics_events';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'datetime' => 'datetime:Y-m-d H:i:s',
        'data' => 'json',
        'created_at' => 'datetime:Y-m-d H:i:s'
    ];

    public function stream()
    {
        return $this->belongsTo(Stream::class, 'camera_id', 'uid');
    }

    public function person()
    {
        return $this->belongsTo(Person::class, 'person_photobank_id', 'grapesva_uuid');
    }
}
