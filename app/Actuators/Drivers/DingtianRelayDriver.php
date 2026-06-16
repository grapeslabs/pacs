<?php

namespace App\Actuators\Drivers;

use App\Models\ActuatorDevice;
use App\MoonShine\Fields\CustomNumber;
use App\MoonShine\Fields\SelectField;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\WriteSingleRegisterRequest;
use ModbusTcpClient\Utils\Types;

class DingtianRelayDriver extends AbstractModbusDriver
{
    private const ADVANCE_WRITE_REGISTER = 0x0003;

    private const RELAY_ONOFF_REGISTER = 0x0036;

    private const TYPE_DELAY = 0x0002;

    private const DEFAULT_DURATION = 5;
    private const RELAY_BOTH = 'both';

    public static function key(): string
    {
        return 'dingtian-dt-r002';
    }

    public static function title(): string
    {
        return 'Dingtian DT-R002 (Modbus TCP)';
    }

    protected function defaultUnitId(): int
    {
        return 255;
    }

    protected function channelFields(): array
    {
        return [
            $this->wire(SelectField::make('Канал реле', 'relay')
                ->options([
                    1 => 'Реле 1',
                    2 => 'Реле 2',
                    self::RELAY_BOTH => 'Оба реле',
                ])
                ->default(1)),
            $this->wire(CustomNumber::make('Длительность открытия, сек', 'duration')
                ->default(self::DEFAULT_DURATION)
                ->hint('На сколько секунд открыть; реле закроется само. 0 — открыть навсегда (без авто-закрытия)')),
        ];
    }

    protected function channelRules(): array
    {
        return [
            'relay'    => ['required', 'in:1,2,' . self::RELAY_BOTH],
            'duration' => ['required', 'integer', 'between:0,65535'],
        ];
    }

    public function open(ActuatorDevice $device): void
    {
        $duration = (int) $this->settingScalar($device, 'duration', self::DEFAULT_DURATION);

        foreach ($this->targetRelayIndexes($device) as $index) {
            $request = $duration === 0
                ? new WriteSingleRegisterRequest(self::RELAY_ONOFF_REGISTER + $index, 1, $this->unitId($device))
                : $this->delayRequest($device, $index, $duration);

            $this->send($device, $request);
        }
    }

    public function close(ActuatorDevice $device): void
    {
        foreach ($this->targetRelayIndexes($device) as $index) {
            $this->send($device, new WriteSingleRegisterRequest(
                self::RELAY_ONOFF_REGISTER + $index,
                0,
                $this->unitId($device),
            ));
        }
    }

    private function delayRequest(ActuatorDevice $device, int $relayIndex, int $duration): WriteMultipleRegistersRequest
    {
        $relayWord = ($relayIndex << 1) | 1;

        return new WriteMultipleRegistersRequest(
            self::ADVANCE_WRITE_REGISTER,
            [
                Types::toRegister(self::TYPE_DELAY),
                Types::toRegister(0),
                Types::toRegister($relayWord),
                Types::toRegister($duration),
            ],
            $this->unitId($device),
        );
    }

    private function targetRelayIndexes(ActuatorDevice $device): array
    {
        $relay = $this->settingScalar($device, 'relay', 1);

        if ((string) $relay === self::RELAY_BOTH) {
            return [0, 1];
        }

        return [((int) $relay) - 1];
    }
}
