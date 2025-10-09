<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Stream extends Model
{
    use HasFactory;
    protected $table = 'streams';
    protected $fillable = [
        'uid',
        'storage_id',
        'name',
        'location',
        'rtsp',
        'archive_time',
        'is_active',
        'is_recognize'
    ];
}
