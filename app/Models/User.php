<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
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
        'is_super' => 'boolean',
    ];
    public function role()
    {
        return $this->belongsTo(Role::class, 'moonshine_user_role_id');
    }

    public function moonshineUserRole()
    {
        return $this->belongsTo(Role::class, 'moonshine_user_role_id');
    }

    public function isSuper(): bool
    {
        return (bool) $this->is_super || (bool) $this->role?->is_super;
    }

    public function hasPermission(string $resourceClass, string|\BackedEnum $action): bool
    {
        if ($this->isSuper()) { return true; }

        if ($action instanceof \BackedEnum) {
            $action = $action->value;
        }

        $own = $this->permissions[$resourceClass][$action] ?? null;

        if ($own !== null) {
            return (bool) $own;
        }

        return !empty($this->role?->permissions[$resourceClass][$action]);
    }
}
