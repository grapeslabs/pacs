<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

        $user = User::firstOrCreate(
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

        Cache::forever('demo_current_password', $password);

        return self::SUCCESS;
    }
}
