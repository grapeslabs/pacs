<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Car extends Model
{
    use HasFactory;
    protected $table = 'cars';

    protected $fillable = [
        'license_plate',
        'brand_id',
        'color_id',
        'organization_id',
        'comment'
    ];

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'car_person');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(CarBrand::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(CarColor::class);
    }

    public function getPeopleNamesAttribute()
    {
        return $this->people->pluck('last_name')->implode(', ');
    }

    public function setPeopleIdsAttribute($value)
    {
        if (is_array($value)) {
            $this->people()->sync($value);
        }
    }

public function scopeWithFirstPerson($query)
{
    return $query->addSelect([
        'first_person_name' => Person::select('last_name')
            ->join('car_person', 'person.id', '=', 'car_person.person_id')
            ->whereColumn('car_person.car_id', 'cars.id')
            ->orderBy('last_name')
            ->limit(1)
    ]);
}
}
