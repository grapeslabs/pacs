<?php

namespace App\Jobs;

use App\Models\GrapeslabsSkudController;
use App\Models\Person;
use App\Observers\PersonObserver;
use App\Services\ControllerAccessService;
use GrapesLabs\PinvideoSkud\ControllerFactory;
use GrapesLabs\PinvideoSkud\Keys\FaceIdKey;
use GrapesLabs\PinvideoSkud\Models\SkudCommand;
use GrapesLabs\PinvideoSkud\Models\SkudController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use MoonShine\Laravel\Notifications\MoonShineNotification;

class PushPersonsToControllers implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120];
    public int $timeout = 600;
    public int $uniqueFor = 600;

    public function __construct(
        private array $controllerIds,
        private array|null $personIds = null,
        private int|null $userId = null,
    ) {
    }

    public function uniqueId(): string
    {
        return 'push-persons:' . implode(',', $this->controllerIds)
            . ':' . ($this->personIds === null ? 'all' : implode(',', $this->personIds));
    }

    public function handle(ControllerAccessService $access, PersonObserver $personObserver): void
    {
        $controllers = GrapeslabsSkudController::query()
            ->whereIn('id', $this->controllerIds)
            ->where('type', 'pinterm')
            ->get();

        if ($controllers->isEmpty()) {
            return;
        }

        $access->flush();

        if ($this->personIds === null) {
            $this->dropPendingCommands($controllers->pluck('id')->all());
        }

        $controllerIds = $controllers->pluck('id');
        $pushed = 0;

        $this->personsQuery()->chunkById(200, function ($people) use ($controllers, $controllerIds, $access, $personObserver, &$pushed) {
            foreach ($people as $person) {
                $frozen = $personObserver->isCurrentlyFrozen($person->frozen_start, $person->frozen_end);
                $allowedIds = $frozen ? collect() : $access->faceControllersForPerson($person)->pluck('id');
                $needImages = $allowedIds->intersect($controllerIds)->isNotEmpty();

                $faceKey = new FaceIdKey(
                    uid: $person->key_uid ?? $person->getSkudUid(),
                    images: $needImages ? $this->imagesData($person->photo) : [],
                    name: $person->getFullName(),
                );

                foreach ($controllers as $controller) {
                    $this->applyKey($controller, $faceKey, $allowedIds->contains($controller->id), "push persons face {$person->id}");
                    $pushed++;
                }
            }
        });

        $this->scheduleFrozen($personObserver);

        $this->notify($pushed, $controllers->count());
    }

    private function personsQuery()
    {
        $query = Person::query()->with('tags');

        if ($this->personIds !== null) {
            $query->whereIn('id', $this->personIds);
        }

        return $query;
    }

    private function scheduleFrozen(PersonObserver $personObserver): void
    {
        $query = Person::query()->whereNotNull('frozen_start');

        if ($this->personIds !== null) {
            $query->whereIn('id', $this->personIds);
        }

        $query->chunkById(200, function ($people) use ($personObserver) {
            foreach ($people as $person) {
                $personObserver->scheduleCommands($person);
            }
        });
    }

    private function dropPendingCommands(array $controllerIds): void
    {
        SkudCommand::query()
            ->whereIn('controller_id', $controllerIds)
            ->where(fn ($q) => $q->whereNull('execute_at')->orWhere('execute_at', '<=', now()))
            ->delete();
    }

    private function imagesData(?array $photos): array
    {
        if (empty($photos)) {
            return [];
        }

        $images = [];

        foreach ($photos as $photoPath) {
            if (Storage::disk('public')->exists($photoPath)) {
                $images[] = [
                    'id' => pathinfo($photoPath, PATHINFO_FILENAME),
                    'content' => Storage::disk('public')->get($photoPath),
                ];
            }
        }

        return $images;
    }

    private function applyKey(GrapeslabsSkudController $controller, FaceIdKey $key, bool $allowed, string $context): void
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
            "Синхронизация лиц завершена. Обновлено {$pushed} записей на {$controllers} контроллерах.",
            ids: [$this->userId],
        );
    }
}
