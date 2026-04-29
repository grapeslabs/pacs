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
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    public function role()
    {
        return $this->belongsTo(Role::class, 'moonshine_user_role_id');
    }

    public function hasPermission(string $resourceClass, string|\BackedEnum $action): bool
    {
        if ($this->id === 1 || $this->moonshine_user_role_id === 1) {
            return true;
        }

        if ($action instanceof \BackedEnum) {
            $action = $action->value;
        }

        if (!$this->role || empty($this->role->permissions)) {
            return false;
        }

        return !empty($this->role->permissions[$resourceClass][$action]);
    }
}
