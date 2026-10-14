<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\MoonShine\Pages\LogViewerPage;
use App\MoonShine\Pages\SettingsPage;
use App\MoonShine\Pages\PresenceReportPage;
use App\MoonShine\Pages\VideoWallPage;
use App\MoonShine\Resources\CarEventResource;
use App\MoonShine\Resources\CrossingEventResource;
use App\MoonShine\Resources\EventReportResource;
use App\MoonShine\Resources\PeopleReportResource;
use App\MoonShine\Resources\StorageResource;
use App\MoonShine\Resources\TriggerResource;
use App\MoonShine\Resources\SkudEventResource;
use App\MoonShine\Resources\UnknownReportResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\ConfiguratorContract;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use App\MoonShine\Resources\MoonShineUserResource;
use App\MoonShine\Resources\MoonShineUserRoleResource;
use App\MoonShine\Resources\ActuatorDeviceResource;
use App\MoonShine\Resources\PersonResource;
use App\MoonShine\Resources\OrganizationResource;
use App\MoonShine\Resources\TagResource;
use App\MoonShine\Resources\KeyResource;
use App\MoonShine\Resources\CarResource;
use App\MoonShine\Resources\GuestResource;
use App\MoonShine\Resources\TerminalResource;
use App\MoonShine\Resources\ControllerResource;
use App\MoonShine\Resources\CarPassageRuleResource;
use App\MoonShine\Resources\PersonPassageRuleResource;
use App\MoonShine\Resources\CarTagResource;
use App\MoonShine\Resources\PassageResource;
use App\MoonShine\Resources\PassageEventResource;
use App\MoonShine\Resources\ReferenceResource;
use App\MoonShine\Resources\SettingResource;
use App\MoonShine\Resources\CarBrandResource;
use App\MoonShine\Resources\CarColorResource;
use App\MoonShine\Resources\BotResource;
use App\MoonShine\Resources\BotChatResource;
use App\MoonShine\Resources\ApiKeyResource;
use App\MoonShine\Resources\VideoStreamResource;
use MoonShine\Contracts\Core\ResourceContract;
use MoonShine\Laravel\Enums\Ability;

class MoonShineServiceProvider extends ServiceProvider
{
    public function boot(CoreContract $core, ConfiguratorContract $config): void
    {
        $core
            ->resources([
                MoonShineUserResource::class,
                MoonShineUserRoleResource::class,
                ActuatorDeviceResource::class,
                PersonResource::class,
                OrganizationResource::class,
                TagResource::class,
                KeyResource::class,
                CarResource::class,
                GuestResource::class,
                TerminalResource::class,
                ControllerResource::class,
                PassageResource::class,
                CarPassageRuleResource::class,
                PersonPassageRuleResource::class,
                PassageEventResource::class,
                CarTagResource::class,
                ReferenceResource::class,
                SettingResource::class,
                CarBrandResource::class,
                CarColorResource::class,
                SettingResource::class,
                BotChatResource::class,
                SkudEventResource::class,
                ApiKeyResource::class,
                ...config('services.ms.enabled')?[
                    VideoStreamResource::class,
                    StorageResource::class,
                ]:[],
                ...config('services.va.enabled')?[
                    BotResource::class,
                    TriggerResource::class,
                    EventReportResource::class,
                    PeopleReportResource::class,
                    UnknownReportResource::class,
                    CarEventResource::class,
                    CrossingEventResource::class,
                ]:[],
            ])
            ->pages([
                ...$config->getPages(),
                LogViewerPage::class,
                SettingsPage::class,
                ...config('services.ms.enabled')?[VideoWallPage::class]:[],
                ...config('services.va.enabled')?[PresenceReportPage::class]:[],
            ]);
        $config->authorizationRules(
            static function (ResourceContract $resource, Model $user, Ability $ability): bool {
                if (!$user instanceof User) {
                    return false;
                }

                if ($user->isSuper()) {
                    return true;
                }

                return $user->hasPermission(
                    $resource::class,
                    $ability->value
                );
            }
        );
    }
}
