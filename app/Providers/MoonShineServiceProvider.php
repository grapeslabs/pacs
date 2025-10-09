<?php

declare(strict_types=1);

namespace App\Providers;

use App\MoonShine\Pages\SettingsPage;
use App\MoonShine\Resources\EventReportResource;
use App\MoonShine\Resources\PeopleReportResource;
use App\MoonShine\Resources\TriggerResource;
use App\MoonShine\Resources\SkudEventResource;
use App\MoonShine\Resources\UnknownReportResource;
use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\ConfiguratorContract;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use App\MoonShine\Resources\MoonShineUserResource;
use App\MoonShine\Resources\MoonShineUserRoleResource;
use App\MoonShine\Resources\EquipmentResource;
use App\MoonShine\Resources\PersonResource;
use App\MoonShine\Resources\OrganizationResource;
use App\MoonShine\Resources\TagResource;
use App\MoonShine\Resources\KeyResource;
use App\MoonShine\Resources\CarResource;
use App\MoonShine\Resources\GuestResource;
use App\MoonShine\Resources\TerminalResource;
use App\MoonShine\Resources\BarrierResource;
use App\MoonShine\Resources\ControllerResource;
use App\MoonShine\Resources\ReferenceResource;
use App\MoonShine\Resources\SettingResource;
use App\MoonShine\Resources\CarBrandResource;
use App\MoonShine\Resources\CarColorResource;
use App\MoonShine\Resources\PersonPhotoResource;
use App\MoonShine\Resources\BotResource;
use App\MoonShine\Resources\BotChatResource;
use App\MoonShine\Resources\BarrierEventResource;
use App\MoonShine\Resources\ApiKeyResource;
use App\MoonShine\Resources\InviterResource;
use App\MoonShine\Resources\VideoStreamResource;

class MoonShineServiceProvider extends ServiceProvider
{
    public function boot(CoreContract $core, ConfiguratorContract $config): void
    {
        $core
            ->resources([
                MoonShineUserResource::class,
                MoonShineUserRoleResource::class,
                EquipmentResource::class,
                PersonResource::class,
                OrganizationResource::class,
                TagResource::class,
                KeyResource::class,
                CarResource::class,
                GuestResource::class,
                TerminalResource::class,
                BarrierResource::class,
                ControllerResource::class,
                ReferenceResource::class,
                SettingResource::class,
                CarBrandResource::class,
                CarColorResource::class,
                SettingResource::class,
                BotResource::class,
                BotChatResource::class,
                SkudEventResource::class,
                BarrierEventResource::class,
                InviterResource::class,
                ApiKeyResource::class,
                BotResource::class,
                VideoStreamResource::class,
                TriggerResource::class,
                EventReportResource::class,
                PeopleReportResource::class,
                UnknownReportResource::class,
            ])
            ->pages([
                ...$config->getPages(),
                SettingsPage::class,
            ]);
    }
}
