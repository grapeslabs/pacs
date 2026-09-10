<?php

namespace App\Jobs;

use App\Models\Key;
use App\Models\PersonPassageRule;
use App\Observers\PersonObserver;
use App\Services\ControllerAccessService;
use GrapesLabs\PinvideoSkud\ControllerFactory;
use GrapesLabs\PinvideoSkud\Keys\RfidKey;
use GrapesLabs\PinvideoSkud\Models\SkudCommand;
use GrapesLabs\PinvideoSkud\Models\SkudController;
use App\Models\GrapeslabsSkudController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use MoonShine\Laravel\Notifications\MoonShineNotification;

class PushKeysToControllers implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120];
    public int $timeout = 600;
    public int $uniqueFor = 600;

    public function __construct(
        private array $controllerIds,
        private array|null $keyIds = null,
        private int|null $userId = null,
    ) {
    }

    public function uniqueId(): string
    {
        return 'push-keys:' . implode(',', $this->controllerIds)
            . ':' . ($this->keyIds === null ? 'all' : implode(',', $this->keyIds));
    }

    public function handle(ControllerAccessService $access, PersonObserver $personObserver): void
    {
        $controllers = GrapeslabsSkudController::query()
            ->whereIn('id', $this->controllerIds)
            ->whereNotIn('type', ['pingate', 'pinterm'])
            ->get();

        if ($controllers->isEmpty()) {
            return;
        }

        $access->flush();

        if ($this->keyIds === null) {
            $this->dropPendingCommands($controllers->pluck('id')->all());
        }

        $pushed = 0;

        $this->keysQuery()->chunkById(200, function ($keys) use ($controllers, $access, $personObserver, &$pushed) {
            foreach ($keys as $key) {
                $frozen = $key->person
                    && $personObserver->isCurrentlyFrozen($key->person->frozen_start, $key->person->frozen_end);
                $allowedIds = $frozen ? collect() : $access->rfidControllersForKey($key)->pluck('id');
                $rfidKey = new RfidKey(code: $key->key, type: $key->type);

                foreach ($controllers as $controller) {
                    $this->applyKey($controller, $rfidKey, $allowedIds->contains($controller->id), "push keys rfid {$key->id}");
                    $pushed++;
                }
            }
        });

        $this->notify($pushed, $controllers->count());
    }

    private function keysQuery()
    {
        $query = Key::query()->with('person.tags');

        if ($this->keyIds !== null) {
            $query->whereIn('id', $this->keyIds);
        }

        return $query;
    }

    private function dropPendingCommands(array $controllerIds): void
    {
        SkudCommand::query()
            ->whereIn('controller_id', $controllerIds)
            ->where(fn ($q) => $q->whereNull('execute_at')->orWhere('execute_at', '<=', now()))
            ->delete();
    }

    private function applyKey(GrapeslabsSkudController $controller, RfidKey $key, bool $allowed, string $context): void
    {
        try {
            $vendor = new SkudController();
            $vendor->setRawAttributes($controller->getAttributes(), true);
            $vendor->exists = $controller->exists;
            $skud = ControllerFactory::create($vendor);

            if ($allowed) {
                $skud->writeKeys([$key]);
            } else {
                $skud->clearKeys([$key]);
            }
        } catch (\Exception $e) {
            Log::error("SKUD $context exception on controller {$controller->serial_number}: " . $e->getMessage());
        }
    }

    private function notify(int $pushed, int $controllers): void
    {
        if (! $this->userId) {
            return;
        }

        MoonShineNotification::send(
            "Синхронизация карт завершена. Обновлено {$pushed} записей на {$controllers} контроллерах.",
            ids: [$this->userId],
        );
    }
}
