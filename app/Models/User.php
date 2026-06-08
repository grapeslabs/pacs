<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table = 'moonshine_users';

    protected $fillable = [
        'moonshine_user_role_id',
        'name',
        'email',
        'password',
        'permissions',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'permissions' => 'array',
    ];
    public function role()
    {
        return $this->belongsTo(Role::class, 'moonshine_user_role_id');
    }

    public function moonshineUserRole()
    {
        return $this->belongsTo(Role::class, 'moonshine_user_role_id');
    }

    public function hasPermission(string $resourceClass, string|\BackedEnum $action): bool
    {
        if ($this->id === 1 || $this->moonshine_user_role_id === 1) { return true; }

        if ($action instanceof \BackedEnum) {
            $action = $action->value;
        }
        if (is_array($this->permissions) && count($this->permissions) > 0) {
            return !empty($this->permissions[$resourceClass][$action]);
        }
        return !empty($this?->role?->permissions[$resourceClass][$action]);
    }
}
