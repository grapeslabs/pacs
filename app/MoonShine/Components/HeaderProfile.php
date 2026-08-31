<?php

declare(strict_types=1);

namespace App\MoonShine\Components;

use Illuminate\Support\Facades\Storage;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\Laravel\Pages\ProfilePage;
use MoonShine\UI\Components\MoonShineComponent;

final class HeaderProfile extends MoonShineComponent
{
    protected string $view = 'components.layout.header-profile';

    protected function viewData(): array
    {
        $user = rescue(fn () => MoonShineAuth::getGuard()->user(), null, report: false);

        return [
            'userName' => $this->displayName($user),
            'email' => $user?->email ?? '',
            'role' => rescue(fn (): string => (string) $user?->moonshineUserRole?->name, '', report: false),
            'avatar' => $this->avatarUrl($user),
            'profileUrl' => rescue(
                fn (): string => toPage(moonshineConfig()->getPage('profile', ProfilePage::class)),
                '#',
                report: false,
            ),
            'instructionUrl' => 'https://grapeslabs.ru/projects',
            'logoutUrl' => rescue(fn (): string => moonshineRouter()->to('logout'), '#', report: false),
        ];
    }

    private function displayName(?object $user): string
    {
        $name = trim((string) ($user?->name ?? ''));

        if ($name === '' || mb_strtolower($name) === 'default') {
            return $user ? (string) strstr((string) $user->email, '@', true) : '';
        }

        return $name;
    }

    private function avatarUrl(?object $user): string
    {
        $placeholder = moonshineAssets()->getAsset('vendor/moonshine/avatar.jpg');

        $avatar = $user?->avatar ?? null;

        if ($avatar === null || $avatar === '') {
            return $placeholder;
        }

        if (str_starts_with((string) $avatar, 'http://') || str_starts_with((string) $avatar, 'https://')) {
            return (string) $avatar;
        }

        return rescue(
            fn (): string => Storage::disk(moonshineConfig()->getDisk())->url($avatar),
            $placeholder,
            report: false,
        );
    }
}
