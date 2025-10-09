<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tag extends Model
{
    use HasFactory;

    protected $table = 'tags';

    protected $fillable = ['name', 'short_name'];

    public function persons()
    {
        return $this->belongsToMany(Person::class, 'person_tag');
    }
}
