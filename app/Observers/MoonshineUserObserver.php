<?php
namespace App\Observers;

use MoonShine\Permissions\Models\MoonshineUser;
use MoonShine\Permissions\Models\MoonshineUserPermission;

class MoonshineUserObserver
{
    public function created(MoonshineUser $user): void
    {
        if ($user->getKey() === 1) {
            $permissions = [];
            foreach (moonshine()->getResources() as $resource) {
                foreach ($resource->getGateAbilities() as $ability) {
                    $permissions[$resource::class][$ability->value] = true;
                }
            }

            MoonshineUserPermission::create([
                'moonshine_user_id' => $user->getKey(),
                'permissions' => $permissions,
            ]);
        }
    }
}
