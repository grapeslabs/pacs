<?php

namespace App\Observers;

use App\Models\GrapeslabsSkudController;
use App\Models\Key;
use App\Models\Person;
use App\Services\ControllerAccessService;
use Illuminate\Support\Facades\Log;
use GrapesLabs\PinvideoSkud\Models\SkudController;
use GrapesLabs\PinvideoSkud\Keys\RfidKey;
use GrapesLabs\PinvideoSkud\ControllerFactory;

class KeyObserver
{
    public bool $afterCommit = true;

    public function __construct(protected ControllerAccessService $access) {}

    public function created(Key $key): void
    {
        $key->load('person.tags');

        if (! $this->shouldSkipImmediateSync($key->person)) {
            $this->reconcileKey($key);
        }
        $this->reschedulePersonCommands($key->person);
    }

    public function updated(Key $key): void
    {
        if (! $key->wasChanged(['key', 'type', 'person_id', 'is_active'])) {
            return;
        }

        $key->load('person.tags');

        if (! $this->shouldSkipImmediateSync($key->person)) {
            $oldCode = $key->wasChanged('key') ? $key->getOriginal('key') : null;
            $this->reconcileKey($key, $oldCode, $key->getOriginal('type'));
        }

        $this->reschedulePersonCommands($key->person);

        if ($key->wasChanged('person_id')) {
            $oldPersonId = $key->getOriginal('person_id');
            if ($oldPersonId !== null && $oldPersonId !== $key->person_id) {
                $this->reschedulePersonCommands(Person::find($oldPersonId));
            }
        }
    }

    public function deleted(Key $key): void
    {
        $this->clearEverywhere($key->key, $key->type);
        $this->reschedulePersonCommands($key->person);
    }

    public function reconcilePersonKeys(Person $person): void
    {
        if ($this->shouldSkipImmediateSync($person)) {
            return;
        }

        foreach ($person->keys as $key) {
            $this->reconcileKey($key);
        }
    }

    protected function reconcileKey(Key $key, ?string $oldCode = null, ?string $oldType = null): void
    {
        $controllers = $this->rfidControllers();
        if ($controllers->isEmpty()) {
            return;
        }

        $allowedIds = $key->is_active ? $this->access->rfidControllersForKey($key)->pluck('id') : collect();
        $newKey = $this->createRfidKey($key->key, $key->type);
        $oldKey = ($oldCode !== null && $oldCode !== $key->key)
            ? $this->createRfidKey($oldCode, $oldType ?? $key->type)
            : null;

        foreach ($controllers as $controller) {
            try {
                $skud = ControllerFactory::create($this->toVendorController($controller));
                if ($oldKey) {
                    $skud->clearKeys([$oldKey]);
                }
                if ($allowedIds->contains($controller->id)) {
                    $skud->writeKeys([$newKey]);
                } else {
                    $skud->clearKeys([$newKey]);
                }
                Log::info("SKUD reconcile key {$key->id} success for controller ID: {$controller->id}");
            } catch (\Exception $e) {
                Log::error("SKUD reconcile key {$key->id} exception on controller {$controller->serial_number}: " . $e->getMessage());
            }
        }
    }

    protected function clearEverywhere(?string $code, ?string $type): void
    {
        if ($code === null) {
            return;
        }

        $rfidKey = $this->createRfidKey($code, $type);
        foreach ($this->rfidControllers() as $controller) {
            try {
                ControllerFactory::create($this->toVendorController($controller))->clearKeys([$rfidKey]);
                Log::info("SKUD delete key {$code} success for controller ID: {$controller->id}");
            } catch (\Exception $e) {
                Log::error("SKUD delete key {$code} exception on controller {$controller->serial_number}: " . $e->getMessage());
            }
        }
    }

    protected function rfidControllers()
    {
        return GrapeslabsSkudController::whereNotIn('type', ['pingate', 'pinterm'])->get();
    }

    protected function toVendorController(GrapeslabsSkudController $controller): SkudController
    {
        $vendor = new SkudController();
        $vendor->setRawAttributes($controller->getAttributes(), true);
        $vendor->exists = $controller->exists;

        return $vendor;
    }

    protected function createRfidKey(?string $code, ?string $type): RfidKey
    {
        return new RfidKey(code: $code, type: $type);
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
