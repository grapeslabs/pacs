<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CarTag extends Model
{
    use HasFactory;

    protected $table = 'car_tags';

    protected $fillable = ['name', 'short_name'];

    public function cars(): BelongsToMany
    {
        return $this->belongsToMany(Car::class, 'car_car_tag', 'car_tag_id', 'car_id');
    }
}
