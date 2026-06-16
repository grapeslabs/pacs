<?php

namespace App\Actuators\Drivers;

use App\Models\ActuatorDevice;
use App\Models\GrapeslabsSkudController;
use GrapesLabs\PinvideoSkud\Controllers\IronLogicAdapter\OutputPacketProcessor as IronLogicProcessor;
use App\MoonShine\Fields\SelectField;

class SkudControllerDriver extends AbstractActuatorDriver
{
    public static function key(): string
    {
        return 'skud-controller';
    }

    public static function title(): string
    {
        return 'СКУД контроллер';
    }

    public function capabilities(): array
    {
        return ['open'];
    }

    public function fields(): array
    {
        return [
            $this->wire(SelectField::make('СКУД контроллер', 'controller_id')
                ->options($this->controllerOptions())),
        ];
    }

    protected function rawRules(): array
    {
        return [
            'controller_id' => ['required', 'integer', 'exists:grapeslabs_skud_controllers,id'],
        ];
    }

    public function open(ActuatorDevice $device): void
    {
        $controllerId = $this->settingScalar($device, 'controller_id');

        IronLogicProcessor::open_door((string) $controllerId);
    }

    public function close(ActuatorDevice $device): void
    {
        throw new \RuntimeException('Закрытие не поддерживается у СКУД-контроллера (открытие двери импульсное).');
    }

    private function controllerOptions(): array
    {
        return GrapeslabsSkudController::query()
            ->orderBy('serial_number')
            ->get()
            ->mapWithKeys(fn(GrapeslabsSkudController $c) => [
                $c->id => $c->serial_number . ' (' . $c->type_label . ')',
            ])
            ->all();
    }
}
