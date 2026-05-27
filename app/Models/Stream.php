<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stream extends Model
{
    use HasFactory, SoftDeletes;
    protected $connection='pgsql';
    protected $table = 'streams';
    protected $fillable = [
        'uid',
        'storage_id',
        'name',
        'location',
        'rtsp',
        'archive_time',
        'is_active',
        'is_recognize',
        'va_options',
    ];
    protected $casts = [
        'va_options' => 'json',
    ];

    public function storage() {
        return $this->belongsTo(Storage::class);
    }
}
