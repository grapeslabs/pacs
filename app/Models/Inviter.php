<?php

namespace App\Models;

use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inviter extends Model
{
    use HasFactory;
    protected $fillable = [
        'person_id',
        'user_name'];

    public function person()
    {
        return $this->belongsTo(Person::class); // Связываем с моделью Person
    }
}
