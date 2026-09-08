<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoWall extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'video_walls';
    protected $fillable = [
        'name',
        'layout',
        'sort',
    ];
    protected $casts = [
        'layout' => 'array',
    ];
}
