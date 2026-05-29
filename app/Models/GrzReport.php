<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrzReport extends Model
{
    protected $connection = 'grz-database';
    protected $table = 'recognized_plates';

    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'is_authorized' => 'boolean',
    ];

    public function stream()
    {
        return $this->belongsTo(Stream::class, 'camera_id', 'uid');
    }

    public function car()
    {
        return $this->belongsTo(Car::class, 'plate_text', 'license_plate');
    }
}