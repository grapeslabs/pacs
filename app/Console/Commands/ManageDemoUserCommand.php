<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use MoonShine\Permissions\Models\MoonshineUser;
use MoonShine\Permissions\Models\MoonshineUserPermission;

class ManageDemoUserCommand extends Command
{
    protected $signature = 'demo:manage-demo-user';

    protected $description = 'Управление демо-пользователем системы';

    public function handle(): int
    {
        if (! config('demo.enabled')) {
            return self::SUCCESS;
        }

        $email = config('demo.email');
        $password = Str::random(12);

        $user = MoonshineUser::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Пользователь',
                'moonshine_user_role_id' => 1,
                'password' => Hash::make($password),
            ]
        );

        if (! $user->wasRecentlyCreated) {
            $user->password = Hash::make($password);
            $user->saveQuietly();
        }

        $permissions = [];

        foreach (moonshine()->getResources() as $resource) {
            foreach ($resource->getGateAbilities() as $ability) {
                $permissions[$resource::class][$ability->value] = true;
            }
        }

        MoonshineUserPermission::updateOrCreate(
            ['moonshine_user_id' => $user->getKey()],
            ['permissions' => $permissions]
        );

        Cache::forever('demo_current_password', $password);

        return self::SUCCESS;
    }
}
