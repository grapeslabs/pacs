<?php

namespace App\Models;

use GrapesLabs\PinvideoSkud\Models\SkudController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Passage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'passages';

    protected $fillable = [
        'name',
        'entry_controller_id',
        'exit_controller_id',
        'comment',
    ];

    public function entryController(): BelongsTo
    {
        return $this->belongsTo(SkudController::class, 'entry_controller_id');
    }

    public function exitController(): BelongsTo
    {
        return $this->belongsTo(SkudController::class, 'exit_controller_id');
    }

    public function entryCameras(): BelongsToMany
    {
        return $this->belongsToMany(Stream::class, 'passage_entry_cameras', 'passage_id', 'stream_id');
    }

    public function exitCameras(): BelongsToMany
    {
        return $this->belongsToMany(Stream::class, 'passage_exit_cameras', 'passage_id', 'stream_id');
    }

    public function rules(): BelongsToMany
    {
        return $this->belongsToMany(CarPassageRule::class, 'car_passage_rule_passage', 'passage_id', 'car_passage_rule_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(CarPassageEvent::class, 'passage_id');
    }

    public function hasEntryController(): bool
    {
        return ! is_null($this->entry_controller_id);
    }

    public function hasExitController(): bool
    {
        return ! is_null($this->exit_controller_id);
    }
}
