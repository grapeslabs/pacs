<?php

namespace App\Actuators\Drivers;

use App\Models\ActuatorDevice;
use App\MoonShine\Fields\CustomNumber;
use App\MoonShine\Fields\CustomText;
use ModbusTcpClient\Network\BinaryStreamConnection;
use ModbusTcpClient\Packet\ModbusRequest;
use ModbusTcpClient\Packet\ResponseFactory;

abstract class AbstractModbusDriver extends AbstractActuatorDriver
{
    abstract protected function channelFields(): array;
    abstract protected function channelRules(): array;

    protected function defaultUnitId(): int
    {
        return 1;
    }

    public function fields(): array
    {
        return array_merge([
            $this->wire(CustomText::make('Хост (IP)', 'host')->ipv4()),
            $this->wire(CustomNumber::make('Порт', 'port')->default(502)),
            $this->wire(CustomNumber::make('Unit ID', 'unit_id')->default($this->defaultUnitId())),
        ], $this->channelFields());
    }

    protected function rawRules(): array
    {
        return array_merge([
            'host'    => ['required', 'ipv4'],
            'port'    => ['nullable', 'integer', 'between:1,65535'],
            'unit_id' => ['nullable', 'integer', 'between:0,255'],
        ], $this->channelRules());
    }

    public function test(ActuatorDevice $device): bool
    {
        $this->retryOnSignal(function () use ($device) {
            $connection = $this->connection($device);

            try {
                $connection->connect();
            } finally {
                $connection->close();
            }
        });

        return true;
    }

    protected function unitId(ActuatorDevice $device): int
    {
        return (int) $this->setting($device, 'unit_id', $this->defaultUnitId());
    }

    protected function send(ActuatorDevice $device, ModbusRequest $request): void
    {
        $this->retryOnSignal(function () use ($device, $request) {
            $connection = $this->connection($device);

            try {
                $connection->connect();
                $binaryData = $connection->sendAndReceive($request);
                ResponseFactory::parseResponseOrThrow($binaryData);
            } finally {
                $connection->close();
            }
        });
    }

    protected function connection(ActuatorDevice $device): BinaryStreamConnection
    {
        return BinaryStreamConnection::getBuilder()
            ->setHost((string) $this->setting($device, 'host'))
            ->setPort((int) $this->setting($device, 'port', 502))
            ->setConnectTimeoutSec(2)
            ->setReadTimeoutSec(2)
            ->setWriteTimeoutSec(2)
            ->build();
    }

    protected function retryOnSignal(callable $fn, int $attempts = 3): mixed
    {
        $previousLimit = (int) ini_get('max_execution_time');
        set_time_limit(0);

        try {
            for ($i = 1; ; $i++) {
                try {
                    return $fn();
                } catch (\Throwable $e) {
                    $isSignal = str_contains($e->getMessage(), 'interrupted by an incoming signal');

                    if ($isSignal && $i < $attempts) {
                        continue;
                    }

                    if ($isSignal) {
                        throw new \RuntimeException(
                            'Устройство приняло TCP-соединение, но не ответило по Modbus. '
                            . 'Проверьте, что это действительно Modbus TCP устройство, '
                            . 'включён Modbus, и верны unit_id и адрес coil/канала.',
                            0,
                            $e
                        );
                    }

                    throw $e;
                }
            }
        } finally {
            set_time_limit($previousLimit);
        }
    }
}
