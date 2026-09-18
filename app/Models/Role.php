<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use SoftDeletes;

    protected $table = 'moonshine_user_roles';

    protected $fillable = [
        'name',
        'description',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_super' => 'boolean',
    ];
}
