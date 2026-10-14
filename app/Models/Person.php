<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Person extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection='pgsql';
    protected $table = 'person';

    protected $fillable = [
        'last_name',
        'first_name',
        'middle_name',
        'birth_date',
        'certificate_number',
        'photo',
        'organization_id',
        'comment',
        'grapesva_uuid',
        'key_uid',
        'face_vector',
        'vectorization_status',
        'vectorization_error',
        'vectorized_at',
        'frozen_start',
        'frozen_end',
        'frozen_reason',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'face_vector' => 'array',
        'vectorized_at' => 'datetime',
        'photo' => 'array',
        'frozen_start' => 'datetime',
        'frozen_end' => 'datetime',
    ];

    public function isFrozen(?\Carbon\Carbon $at = null): bool
    {
        $at = $at ?? now();

        return $this->frozen_start !== null
            && $this->frozen_start <= $at
            && ($this->frozen_end === null || $this->frozen_end >= $at);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'person_tag', 'person_id', 'tag_id');
    }

    public function keys(): HasMany
    {
        return $this->hasMany(Key::class);
    }


    public function getTagsListAttribute()
    {
        return $this->tags->pluck('name')->implode(', ');
    }

    public function cars(): BelongsToMany
    {
        return $this->belongsToMany(Car::class, 'car_person');
    }

    public function getFirstPhotoAttribute()
    {
        if ($this->photo && is_array($this->photo) && count($this->photo) > 0) {
            return $this->photo[0];
        }
        return null;
    }

    public function getSkudUid(): string
    {
        return 'person_' . $this->id;
    }

    public function getFullName(): string
    {
        return trim($this->last_name . ' ' . $this->first_name . ' ' . ($this->middle_name ?? ''));
    }
}
