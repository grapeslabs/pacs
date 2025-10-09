<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkudEventCarPlate extends Model
{
    protected $table = 'skud_event_car_plates';

    protected $fillable = [
        'event_id',
        'car_plate',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(SkudEvent::class, 'event_id');
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'car_plate', 'license_plate');
    }
}
