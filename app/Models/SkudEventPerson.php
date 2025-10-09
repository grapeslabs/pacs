<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkudEventPerson extends Model
{
    protected $table = 'skud_event_persons';

    protected $fillable = [
        'event_id',
        'card_number',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(SkudEvent::class, 'event_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'card_number', 'key_uid');
    }
}
