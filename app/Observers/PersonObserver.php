<?php

namespace App\Observers;

use App\Models\Person;
use App\Models\SkudCommand;
use App\Models\GrapeslabsSkudController;
use GrapesLabs\PinvideoSkud\Models\SkudController;
use Carbon\Carbon;
use App\Services\VideoAnalyticService;
use GrapesLabs\PinvideoSkud\ControllerFactory;
use GrapesLabs\PinvideoSkud\Keys\FaceIdKey;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PersonObserver
{
    public bool $afterCommit = true;

    protected VideoAnalyticService $vas;

    protected array $deletedRfidKeysCache = [];

    public function __construct(VideoAnalyticService $vas)
    {
        $this->vas = $vas;
    }

    public function created(Person $person): void
    {
        if (empty($person->key_uid)) {
            $person->key_uid = $person->getSkudUid();
            $person->saveQuietly();
        }

        $this->updateVas($person);
        $person->load('keys');

        if (!$this->isCurrentlyFrozen($person->frozen_start, $person->frozen_end)) {
            $faceKey = $this->createFaceKey($person, $person->photo);
            $this->syncWithControllers($this->getFaceControllers(), [$faceKey], [], "add person face {$person->id}");

            $rfidCardsAdd = $person->keys->map(fn($k) => ['card' => (string)$k->key, 'flags' => 0, 'tz' => 255])->values()->toArray();
            if (!empty($rfidCardsAdd)) {
                foreach ($this->getRfidControllers() as $controller) {
                    $this->createSkudCommand($controller->id, Carbon::now(), 'add_cards', ['cards' => $rfidCardsAdd]);
                }
            }
        }

        $this->scheduleCommands($person);
    }

    public function updated(Person $person): void
    {
        $this->scheduleCommands($person);

        $wasFrozen = $this->isCurrentlyFrozen($person->getOriginal('frozen_start'), $person->getOriginal('frozen_end'));
        $isFrozen = $this->isCurrentlyFrozen($person->frozen_start, $person->frozen_end);

        $faceControllers = $this->getFaceControllers();
        $rfidControllers = $this->getRfidControllers();
        $uid = $person->key_uid ?? $person->getSkudUid();

        if ($person->wasChanged(['frozen_start', 'frozen_end'])) {
            if (!$wasFrozen && $isFrozen) {
                foreach ($faceControllers as $controller) {
                    $this->createSkudCommand($controller->id, Carbon::now(), 'del_persons', ['persons' => [['person' => $uid]]]);
                }

                $rfidCardsDel = $person->keys->map(fn($k) => ['card' => (string)$k->key])->values()->toArray();
                if (!empty($rfidCardsDel)) {
                    foreach ($rfidControllers as $controller) {
                        $this->createSkudCommand($controller->id, Carbon::now(), 'del_cards', ['cards' => $rfidCardsDel]);
                    }
                }
            } elseif ($wasFrozen && !$isFrozen) {
                $imagesBase64 = $this->getImagesData($person->photo, true);
                foreach ($faceControllers as $controller) {
                    $this->createSkudCommand($controller->id, Carbon::now(), 'add_persons', [
                        'persons' => [[
                            'person' => $uid,
                            'person_name' => $person->getFullName(),
                            'images' => $imagesBase64
                        ]]
                    ]);
                }

                $rfidCardsAdd = $person->keys->map(fn($k) => ['card' => (string)$k->key, 'flags' => 0, 'tz' => 255])->values()->toArray();
                if (!empty($rfidCardsAdd)) {
                    foreach ($rfidControllers as $controller) {
                        $this->createSkudCommand($controller->id, Carbon::now(), 'add_cards', ['cards' => $rfidCardsAdd]);
                    }
                }
            }
        }

        if ($person->wasChanged('photo') && !$isFrozen) {
            $this->updateVas($person);
            $keysToClear = !empty($person->getOriginal('photo')) ? [$this->createFaceKey($person, $person->getOriginal('photo'))] : [];
            $this->syncWithControllers($faceControllers, [$this->createFaceKey($person, $person->photo)], $keysToClear, "update person photo {$person->id}");
        }
    }

    public function deleting(Person $person): void
    {
        $this->deletedRfidKeysCache[$person->id] = $person->keys->pluck('key')->toArray();
    }

    public function deleted(Person $person): void
    {
        $uid = $person->key_uid ?? $person->getSkudUid();

        foreach ($this->getFaceControllers() as $controller) {
            $this->createSkudCommand($controller->id, Carbon::now(), 'del_persons', ['persons' => [['person' => $uid]]]);
        }

        $cards = $this->deletedRfidKeysCache[$person->id] ?? [];
        if (!empty($cards)) {
            $rfidCardsDel = array_map(fn($c) => ['card' => (string)$c], $cards);
            foreach ($this->getRfidControllers() as $controller) {
                $this->createSkudCommand($controller->id, Carbon::now(), 'del_cards', ['cards' => $rfidCardsDel]);
            }
        }

        SkudCommand::where('message', 'LIKE', '%"person":"' . $uid . '"%')->delete();
        foreach ($cards as $cardNum) {
            SkudCommand::where('message', 'LIKE', '%"card":"' . $cardNum . '"%')->delete();
        }

        $this->vas->personDelete($person->id);
        $person->keys()->getQuery()->delete();
        unset($this->deletedRfidKeysCache[$person->id]);
    }

    public function scheduleCommands(Person $person): void
    {
        $person->load('keys');

        $now = Carbon::now();
        $uid = $person->key_uid ?? $person->getSkudUid();

        SkudCommand::where('message', 'LIKE', '%"person":"' . $uid . '"%')
            ->where('execute_at', '>', $now)
            ->delete();

        $cardNumbers = $person->keys->pluck('key')->toArray();
        if (!empty($cardNumbers)) {
            foreach ($cardNumbers as $cardNum) {
                SkudCommand::where('message', 'LIKE', '%"card":"' . $cardNum . '"%')
                    ->where('execute_at', '>', $now)
                    ->delete();
            }
        }

        if (!$person->frozen_start && !$person->frozen_end) {
            return;
        }

        $start = $person->frozen_start ? Carbon::parse($person->frozen_start) : null;
        $end = $person->frozen_end ? Carbon::parse($person->frozen_end) : null;

        $faceControllers = $this->getFaceControllers();
        $rfidControllers = $this->getRfidControllers();

        $rfidCardsAdd = $person->keys->map(fn($key) => ['card' => (string)$key->key, 'flags' => 0, 'tz' => 255])->values()->toArray();
        $rfidCardsDel = $person->keys->map(fn($key) => ['card' => (string)$key->key])->values()->toArray();
        $imagesBase64 = $this->getImagesData($person->photo, true);

        if ($start && $start->isFuture()) {
            foreach ($faceControllers as $controller) {
                $this->createSkudCommand($controller->id, $start, 'del_persons', [
                    'persons' => [ ['person' => $uid] ]
                ]);
            }
            if (!empty($rfidCardsDel)) {
                foreach ($rfidControllers as $controller) {
                    $this->createSkudCommand($controller->id, $start, 'del_cards', [
                        'cards' => $rfidCardsDel
                    ]);
                }
            }
        }

        if ($end && $end->isFuture()) {
            foreach ($faceControllers as $controller) {
                $this->createSkudCommand($controller->id, $end, 'add_persons', [
                    'persons' => [[
                        'person' => $uid,
                        'person_name' => $person->getFullName(),
                        'images' => $imagesBase64,
                    ]]
                ]);
            }
            if (!empty($rfidCardsAdd)) {
                foreach ($rfidControllers as $controller) {
                    $this->createSkudCommand($controller->id, $end, 'add_cards', [
                        'cards' => $rfidCardsAdd
                    ]);
                }
            }
        }
    }

    protected function createSkudCommand(int $controllerId, Carbon $executeAt, string $operation, array $payload): void
    {
        $command = SkudCommand::create([
            'controller_id' => $controllerId,
            'execute_at' => $executeAt,
            'message' => '{}'
        ]);

        $message = array_merge([
            'id' => $command->id,
            'operation' => $operation,
        ], $payload);

        $command->update([
            'message' => json_encode($message, JSON_UNESCAPED_UNICODE)
        ]);
    }

    protected function syncWithControllers(Collection $controllers, array $keysToWrite, array $keysToClear, string $context): void
    {
        if ($controllers->isEmpty()) return;

        foreach ($controllers as $controller) {
            try {
                $packageController = new SkudController();
                $packageController->setRawAttributes($controller->getAttributes(), true);
                $packageController->exists = $controller->exists;
                $skudController = ControllerFactory::create($packageController);

                if (!empty($keysToClear)) $skudController->clearKeys($keysToClear);
                if (!empty($keysToWrite)) $skudController->writeKeys($keysToWrite);

                Log::info("SKUD $context success for controller: {$controller->serial_number}");
            } catch (\Exception $e) {
                Log::error("SKUD $context exception on controller {$controller->serial_number}: " . $e->getMessage());
            }
        }
    }

    protected function createFaceKey(Person $person, ?array $photoPaths): FaceIdKey
    {
        return new FaceIdKey(
            uid: $person->key_uid ?? $person->getSkudUid(),
            images: $this->getImagesData($photoPaths, false),
            name: $person->getFullName()
        );
    }

    protected function getImagesData(?array $photos, bool $asBase64): array
    {
        if (empty($photos)) return [];
        $images = [];
        foreach ($photos as $photoPath) {
            if (Storage::disk('public')->exists($photoPath)) {
                $content = Storage::disk('public')->get($photoPath);
                $images[] = [
                    'id' => pathinfo($photoPath, PATHINFO_FILENAME),
                    'content' => $asBase64 ? base64_encode($content) : $content
                ];
            }
        }
        return $images;
    }

    protected function updateVas(Person $person): void
    {
        if (empty($person->photo)) return;
        $photosFullPaths = [];
        foreach ($person->photo as $photoPath) {
            $fullPath = Storage::disk('public')->path($photoPath);
            if (file_exists($fullPath)) $photosFullPaths[] = $fullPath;
        }
        if (!empty($photosFullPaths)) {
            $this->vas->personCreate($person->getFullName(), $photosFullPaths, $person->id);
        }
    }

    public function isCurrentlyFrozen(?string $start, ?string $end): bool
    {
        if (!$start) return false;
        $now = Carbon::now();
        $startCarbon = Carbon::parse($start);
        $endCarbon = $end ? Carbon::parse($end) : null;

        if ($now->lessThan($startCarbon)) return false;
        if ($endCarbon && $now->greaterThanOrEqualTo($endCarbon)) return false;

        return true;
    }

    protected function getFaceControllers(): Collection
    {
        return GrapeslabsSkudController::where('type', 'pinterm')->get();
    }

    protected function getRfidControllers(): Collection
    {
        return SkudController::whereNotIn('type', ['pingate', 'pinterm'])->get();
    }
}
