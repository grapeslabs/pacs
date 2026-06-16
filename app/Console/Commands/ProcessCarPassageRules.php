<?php

namespace App\Console\Commands;

use App\Actuators\ActuatorService;
use App\Models\Car;
use App\Models\CarPassageEvent;
use App\Models\CarPassageRule;
use App\Models\Passage;
use App\Models\Stream;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessCarPassageRules extends Command
{
    protected $signature = 'lpr:process-rules';
    protected $description = 'Process recognized license plates against car passage rules and trigger controllers';

    private const CACHE_KEY = 'car_passage_rules_last_recognized_id';

    private int $maxIterations = 1000;
    private int $batchSize = 200;

    public function handle(): int
    {
        $this->info('Car passage rules worker started.');

        $iterations = 0;
        while ($iterations < $this->maxIterations) {
            try {
                $this->process();
            } catch (\Throwable $e) {
                Log::error('Car passage rules: critical error', [
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTraceAsString(),
                ]);
            }
            $iterations++;
            sleep(1);
        }

        $this->info('Car passage rules worker scheduled restart.');

        return self::SUCCESS;
    }

    protected function process(): void
    {
        $lastId = (int) Cache::get(self::CACHE_KEY, 0);

        if ($lastId === 0) {
            $maxExisting = (int) $this->recognizedPlates()->max('id');
            if ($maxExisting > 0) {
                Cache::put(self::CACHE_KEY, $maxExisting);
                return;
            }
        }

        $plates = $this->recognizedPlates()
            ->where('id', '>', $lastId)
            ->orderBy('id')
            ->limit($this->batchSize)
            ->get();

        if ($plates->isEmpty()) {
            return;
        }

        $this->info("New recognitions found: {$plates->count()}");

        $activeRules    = $this->getActiveRules();
        $activePassages = $this->getActivePassages();

        $maxProcessed = $lastId;
        foreach ($plates as $plate) {
            try {
                $this->processPlate($plate, $activeRules, $activePassages);
            } catch (\Throwable $e) {
                Log::error('Car passage rules: failed to process recognition', [
                    'recognized_plate_id' => $plate->id,
                    'message'             => $e->getMessage(),
                ]);
            }
            $maxProcessed = max($maxProcessed, (int) $plate->id);
        }

        if ($maxProcessed > $lastId) {
            Cache::put(self::CACHE_KEY, $maxProcessed);
        }
    }

    protected function recognizedPlates(): Builder
    {
        return DB::connection('lpr-database')->table('recognized_plates');
    }

    protected function getActiveRules(): Collection
    {
        return CarPassageRule::query()
            ->where('is_active', true)
            ->with(['passages', 'cars', 'people.cars', 'carTags.cars'])
            ->orderBy('id')
            ->get();
    }

    protected function getActivePassages(): Collection
    {
        return Passage::query()
            ->with(['entryCameras', 'exitCameras', 'entryActuatorDevice', 'exitActuatorDevice'])
            ->get();
    }

    protected function processPlate(object $plate, Collection $activeRules, Collection $activePassages): void
    {
        $recognizedId = (int) $plate->id;

        if (CarPassageEvent::query()->where('recognized_plate_id', $recognizedId)->exists()) {
            return;
        }

        $plateText  = trim((string) ($plate->plate_text ?? ''));
        $cameraUid  = $plate->camera_id ?? null;
        $normalized = $plateText !== '' ? CarPassageRule::normalizePlate($plateText) : '';

        $car    = $normalized !== '' ? Car::query()->where('license_plate', $normalized)->first() : null;
        $stream = ! empty($cameraUid) ? Stream::query()->where('uid', $cameraUid)->first() : null;

        [$passage, $direction] = $this->findPassageAndDirection($activePassages, $cameraUid);

        $decisiveRule    = null;
        $controllersInfo = [];

        if ($plateText === '') {
            $status = CarPassageEvent::STATUS_NOT_RECOGNIZED;
        } else {
            $decisiveRule = $this->decisiveRule($activeRules, $passage, $direction, $plateText);

            if ($decisiveRule !== null) {
                if ($decisiveRule->type === CarPassageRule::TYPE_ALLOW) {
                    $status          = CarPassageEvent::STATUS_ALLOWED;
                    $controllersInfo = $this->openController($passage, $direction, $decisiveRule, $plateText, $cameraUid);
                } else {
                    $status = CarPassageEvent::STATUS_DENIED;
                    Log::info('Car passage rules: access denied by rule', [
                        'rule_id'    => $decisiveRule->id,
                        'plate'      => $plateText,
                        'camera_uid' => $cameraUid,
                    ]);
                }
            } else {
                $status = $car !== null ? CarPassageEvent::STATUS_IN_DB : CarPassageEvent::STATUS_NOT_IN_DB;
            }
        }

        $imagePath      = $this->resolveLprImagePath($plate->image ?? null);
        $plateImagePath = $this->resolveLprImagePath($plate->plate ?? null);

        CarPassageEvent::query()->create([
            'recognized_plate_id' => $recognizedId,
            'plate_text'          => $plateText !== '' ? $plateText : null,
            'camera_id'           => $cameraUid,
            'stream_id'           => $stream?->id,
            'car_id'              => $car?->id,
            'car_passage_rule_id' => $decisiveRule?->id,
            'rule_name'           => $decisiveRule?->name,
            'passage_id'          => $passage?->id,
            'direction'           => $direction,
            'status'              => $status,
            'is_authorized'       => isset($plate->is_authorized) ? (bool) $plate->is_authorized : null,
            'controllers'         => $controllersInfo ?: null,
            'image_path'          => $imagePath,
            'plate_image_path'    => $plateImagePath,
            'recognized_at'       => $plate->created_at,
        ]);
    }

    protected function findPassageAndDirection(Collection $passages, ?string $cameraUid): array
    {
        if (empty($cameraUid)) {
            return [null, null];
        }

        foreach ($passages as $passage) {
            if ($passage->entryCameras->contains(fn($s) => $s->uid === $cameraUid)) {
                return [$passage, CarPassageRule::DIRECTION_ENTRY];
            }
            if ($passage->exitCameras->contains(fn($s) => $s->uid === $cameraUid)) {
                return [$passage, CarPassageRule::DIRECTION_EXIT];
            }
        }

        return [null, null];
    }

    protected function decisiveRule(
        Collection $rules,
        ?Passage $passage,
        ?string $direction,
        string $plateText
    ): ?CarPassageRule {
        if ($passage === null) {
            return null;
        }

        foreach ($rules as $rule) {
            if (! $rule->passages->contains('id', $passage->id)) {
                continue;
            }

            $directionMatch = $rule->direction === CarPassageRule::DIRECTION_BOTH
                || $rule->direction === $direction;

            if (! $directionMatch) {
                continue;
            }

            if ($rule->matchesPlate($plateText)) {
                return $rule;
            }
        }

        return null;
    }

    protected function openController(
        Passage $passage,
        string $direction,
        CarPassageRule $rule,
        string $plateText,
        ?string $cameraUid
    ): array {
        $device = $direction === CarPassageRule::DIRECTION_ENTRY
            ? $passage->entryActuatorDevice
            : $passage->exitActuatorDevice;

        if (! $device) {
            Log::warning('Car passage rules: no actuator device configured for direction', [
                'passage_id' => $passage->id,
                'direction'  => $direction,
                'rule_id'    => $rule->id,
            ]);
            return [];
        }

        try {
            app(ActuatorService::class)->execute($device, 'open');

            Log::info('Car passage rules: actuator opened', [
                'rule_id'            => $rule->id,
                'passage_id'         => $passage->id,
                'direction'          => $direction,
                'plate'              => $plateText,
                'camera_uid'         => $cameraUid,
                'actuator_device_id' => $device->id,
            ]);

            return [[
                'actuator_device_id' => $device->id,
                'name'               => $device->name,
                'driver'             => $device->driver_key,
            ]];
        } catch (\Throwable $e) {
            Log::error('Car passage rules: failed to open actuator device', [
                'actuator_device_id' => $device->id,
                'message'            => $e->getMessage(),
            ]);
            return [];
        }
    }

    protected function resolveLprImagePath(?string $lprPath): ?string
    {
        if (empty($lprPath)) {
            return null;
        }

        $path = ltrim(preg_replace('#^events/#', '', trim($lprPath)), '/');

        return $path ?: null;
    }
}
