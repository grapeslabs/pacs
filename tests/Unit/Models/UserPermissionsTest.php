<?php

namespace Tests\Unit\Models;

use App\Models\Role;
use App\Models\User;
use App\MoonShine\Resources\KeyResource;
use MoonShine\Laravel\Enums\Ability;
use PHPUnit\Framework\TestCase;

class UserPermissionsTest extends TestCase
{
    private function user(array $own, array $role): User
    {
        $user = new User(['permissions' => $own]);
        $user->setRelation('role', new Role(['permissions' => $role]));

        return $user;
    }

    public function test_missing_key_inherits_the_role(): void
    {
        $user = $this->user([], [KeyResource::class => ['update' => true]]);

        $this->assertTrue($user->hasPermission(KeyResource::class, Ability::UPDATE));
    }

    public function test_personal_true_adds_to_the_role_instead_of_replacing_it(): void
    {
        $user = $this->user(
            [KeyResource::class => ['create' => true]],
            [KeyResource::class => ['update' => true]],
        );

        $this->assertTrue($user->hasPermission(KeyResource::class, Ability::CREATE));
        $this->assertTrue($user->hasPermission(KeyResource::class, Ability::UPDATE));
    }

    public function test_personal_false_overrides_the_role(): void
    {
        $user = $this->user(
            [KeyResource::class => ['update' => false]],
            [KeyResource::class => ['update' => true]],
        );

        $this->assertFalse($user->hasPermission(KeyResource::class, Ability::UPDATE));
    }

    public function test_nothing_anywhere_is_denied(): void
    {
        $user = $this->user([], []);

        $this->assertFalse($user->hasPermission(KeyResource::class, Ability::UPDATE));
    }

    public function test_super_role_grants_everything(): void
    {
        $user = new User();
        $user->setRelation('role', (new Role())->forceFill(['is_super' => true]));

        $this->assertTrue($user->isSuper());
        $this->assertTrue($user->hasPermission(KeyResource::class, Ability::FORCE_DELETE));
    }

    public function test_super_user_grants_everything(): void
    {
        $user = (new User())->forceFill(['is_super' => true]);

        $this->assertTrue($user->isSuper());
        $this->assertTrue($user->hasPermission(KeyResource::class, Ability::FORCE_DELETE));
    }
}
