<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrapeslabsSkudCommand extends Model
{
    use HasFactory;

    protected $fillable = [
        'controller_id',
        'message',
    ];

    protected $casts = [
        'message' => 'array',
    ];
}
