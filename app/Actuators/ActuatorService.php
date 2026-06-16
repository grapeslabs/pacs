<?php

namespace App\Actuators;

use App\Models\ActuatorDevice;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ActuatorService
{
    public function __construct(
        private ActuatorDriverManager $manager,
    ) {
    }

    public function execute(ActuatorDevice $device, string $action): void
    {
        if (! in_array($action, ['open', 'close'], true)) {
            throw new InvalidArgumentException("Недопустимое действие: {$action}");
        }

        if ($device->status !== ActuatorDevice::STATUS_ACTIVE) {
            Log::warning('Actuator: команда отклонена — устройство неактивно', [
                'device_id'  => $device->id,
                'driver_key' => $device->driver_key,
                'action'     => $action,
            ]);

            throw new \RuntimeException("Устройство «{$device->name}» неактивно.");
        }

        $driver = $this->manager->driver((string) $device->driver_key);

        if (! in_array($action, $driver->capabilities(), true)) {
            throw new InvalidArgumentException(
                "Драйвер {$device->driver_key} не поддерживает действие: {$action}"
            );
        }

        try {
            $driver->{$action}($device);

            Log::info('Actuator: команда отправлена', [
                'device_id'  => $device->id,
                'driver_key' => $device->driver_key,
                'action'     => $action,
            ]);
        } catch (\Throwable $e) {
            Log::error('Actuator: ошибка отправки команды', [
                'device_id'  => $device->id,
                'driver_key' => $device->driver_key,
                'action'     => $action,
                'message'    => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function test(ActuatorDevice $device): bool
    {
        return $this->manager->driver((string) $device->driver_key)->test($device);
    }
}
