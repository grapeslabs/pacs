<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use GrapesLabs\PinvideoSkud\Models\SkudController;
use App\Models\SkudEventPerson;

class GrapeslabsSkudEvent extends Model
{
    use HasFactory;

    protected $table = 'grapeslabs_skud_events';

    protected $fillable = [
        'datetime',
        'event_id',
        'controller_id',
        'type',
        'event',
    ];

    protected $casts = [
        'datetime' => 'datetime',
        'event' => 'array',
    ];

    public function controller(): BelongsTo
    {
        return $this->belongsTo(SkudController::class, 'controller_id');
    }

    public function carPlate()
    {
        return $this->hasOne(SkudEventCarPlate::class, 'event_id', 'id');
    }
    public function cardNumber()
    {
        return $this->hasOne(SkudEventPerson::class, 'event_id', 'id');
    }
}
