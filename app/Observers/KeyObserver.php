<?php

namespace App\Observers;

use App\Models\Key;
use App\Models\Person;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;
use GrapesLabs\PinvideoSkud\Models\SkudController;
use GrapesLabs\PinvideoSkud\Keys\RfidKey;
use GrapesLabs\PinvideoSkud\ControllerFactory;

class KeyObserver
{
    public bool $afterCommit = true;

    public function created(Key $key): void
    {
        if ($this->shouldSkipImmediateSync($key->person)) {
            $this->reschedulePersonCommands($key->person);
            return;
        }

        $this->addKey($key);
        $this->reschedulePersonCommands($key->person);
    }

    public function updated(Key $key): void
    {
        if ($key->wasChanged(['key', 'type'])) {
            if ($this->shouldSkipImmediateSync($key->person)) {
                $this->reschedulePersonCommands($key->person);
                return;
            }

            $this->updateKey($key);
            $this->reschedulePersonCommands($key->person);
        }
    }

    public function deleted(Key $key): void
    {
        $this->delKey($key);
        $this->reschedulePersonCommands($key->person);
    }

    protected function addKey(Key $key): void
    {
        $rfidKey = $this->createRfidKey($key->key, $key->type);
        $this->syncWithControllers([$rfidKey], [], "add key {$key->id}");
    }

    protected function updateKey(Key $key): void
    {
        $oldRfidKey = $this->createRfidKey($key->getOriginal('key'), $key->getOriginal('type'));
        $newRfidKey = $this->createRfidKey($key->key, $key->type);

        $this->syncWithControllers([$newRfidKey], [$oldRfidKey], "update key {$key->id}");
    }

    protected function delKey(Key $key): void
    {
        $rfidKey = $this->createRfidKey($key->key, $key->type);
        $this->syncWithControllers([], [$rfidKey], "delete key {$key->id}");
    }

    protected function syncWithControllers(array $keysToWrite, array $keysToClear, string $actionLogContext): void
    {
        $controllers = $this->getControllers();
        if ($controllers->isEmpty()) {
            Log::error('SKUD controllers not found');
            return;
        }

        foreach ($controllers as $controller) {
            try {
                $skudController = ControllerFactory::create($controller);
                if (!empty($keysToClear)) {
                    $skudController->clearKeys($keysToClear);
                }
                if (!empty($keysToWrite)) {
                    $skudController->writeKeys($keysToWrite);
                }
                Log::info("SKUD $actionLogContext success for controller ID: " . ($controller->id ?? 'unknown'));
            } catch (\Exception $e) {
                Log::error("SKUD $actionLogContext exception on controller: " . $e->getMessage());
            }
        }
    }

    protected function createRfidKey(?string $code, ?string $type): RfidKey
    {
        return new RfidKey(code: $code, type: $type);
    }

    protected function getControllers(): Collection
    {
        return SkudController::whereNotIn('type', ['pingate', 'pinterm'])->get();
    }

    protected function shouldSkipImmediateSync(?Person $person): bool
    {
        if (!$person) return false;
        return app(PersonObserver::class)->isCurrentlyFrozen($person->frozen_start, $person->frozen_end);
    }

    protected function reschedulePersonCommands(?Person $person): void
    {
        if ($person) {
            app(PersonObserver::class)->scheduleCommands($person);
        }
    }
}
