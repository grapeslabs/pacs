<?php

namespace App\Actuators\Drivers;

use App\Models\ActuatorDevice;
use App\MoonShine\Fields\CustomNumber;
use App\MoonShine\Fields\SelectField;
use ModbusTcpClient\Packet\ModbusFunction\WriteSingleCoilRequest;
use ModbusTcpClient\Packet\ModbusFunction\WriteSingleRegisterRequest;

class ModbusTcpDriver extends AbstractModbusDriver
{
    private const FUNCTION_COIL = 'coil';
    private const FUNCTION_REGISTER = 'register';

    public static function key(): string
    {
        return 'modbus-tcp';
    }

    public static function title(): string
    {
        return 'Modbus TCP - general';
    }

    protected function channelFields(): array
    {
        return [
            $this->wire(SelectField::make('Тип записи', 'function')
                ->options([
                    self::FUNCTION_COIL     => 'Coil — Write Single Coil (FC05)',
                    self::FUNCTION_REGISTER => 'Holding register — Write Single Register (FC06)',
                ])
                ->default(self::FUNCTION_COIL)),
            $this->wire(CustomNumber::make('Адрес coil / регистра', 'address')->default(0)),
            $this->wire(CustomNumber::make('Значение «Открыть»', 'on_value')->default(1)
                ->hint('Для регистра (FC06); у coil игнорируется (всегда ON)')),
            $this->wire(CustomNumber::make('Значение «Закрыть»', 'off_value')->default(0)
                ->hint('Для регистра (FC06); у coil игнорируется (всегда OFF)')),
        ];
    }

    protected function channelRules(): array
    {
        return [
            'function'  => ['required', 'in:' . self::FUNCTION_COIL . ',' . self::FUNCTION_REGISTER],
            'address'   => ['required', 'integer', 'min:0'],
            'on_value'  => ['nullable', 'integer', 'between:0,65535'],
            'off_value' => ['nullable', 'integer', 'between:0,65535'],
        ];
    }

    public function open(ActuatorDevice $device): void
    {
        $this->write($device, true);
    }

    public function close(ActuatorDevice $device): void
    {
        $this->write($device, false);
    }

    private function write(ActuatorDevice $device, bool $state): void
    {
        $address = (int) $this->setting($device, 'address', 0);
        $unitId  = $this->unitId($device);

        if ((string) $this->settingScalar($device, 'function', self::FUNCTION_COIL) === self::FUNCTION_REGISTER) {
            $value = (int) $this->setting($device, $state ? 'on_value' : 'off_value', $state ? 1 : 0);
            $request = new WriteSingleRegisterRequest($address, $value, $unitId);
        } else {
            $request = new WriteSingleCoilRequest($address, $state, $unitId);
        }

        $this->send($device, $request);
    }
}
