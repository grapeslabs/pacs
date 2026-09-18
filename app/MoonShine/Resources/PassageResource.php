<?php

namespace App\MoonShine\Resources;

use App\Models\ActuatorDevice;
use App\Models\Passage;
use App\Models\Stream;
use App\MoonShine\Fields\CustomText;
use App\MoonShine\Fields\CustomTextarea;
use App\MoonShine\Fields\SelectField;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

class PassageResource extends BaseModelResource
{
    protected string $model = Passage::class;
    protected string $title = 'Проезды';
    protected array $with = ['entryCameras', 'exitCameras', 'entryActuatorDevice', 'exitActuatorDevice'];
    protected string $column = 'name';


    public function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Наименование', 'name')->sortable(),
            Text::make('Устройство въезда', 'entryActuatorDevice', fn($item) => $item->entryActuatorDevice?->name ?? '—'),
            Text::make('Камеры въезда', 'entryCameras', fn($item) => $item->entryCameras->pluck('name')->implode(', ') ?: '—'),
            Text::make('Устройство выезда', 'exitActuatorDevice', fn($item) => $item->exitActuatorDevice?->name ?? '—'),
            Text::make('Камеры выезда', 'exitCameras', fn($item) => $item->exitCameras->pluck('name')->implode(', ') ?: '—'),
            Text::make('Комментарий', 'comment'),
        ];
    }

    public function formFields(): iterable
    {
        return [
            CustomText::make('Наименование', 'name')
                ->max(255, 'Наименование не может содержать более 255 символов')
                ->required(),

            Box::make('Въезд', [
                SelectField::make('Исп. устройство', 'entry_actuator_device_id')
                    ->nullable()
                    ->options($this->actuatorDeviceOptions()),
                SelectField::make('Камеры', 'entryCameras')
                    ->multiple()
                    ->options($this->lprCameraOptions()),
            ]),

            Box::make('Выезд', [
                SelectField::make('Исп. устройство', 'exit_actuator_device_id')
                    ->nullable()
                    ->options($this->actuatorDeviceOptions()),
                SelectField::make('Камеры', 'exitCameras')
                    ->multiple()
                    ->options($this->lprCameraOptions()),
            ]),

            CustomTextarea::make('Комментарий', 'comment')->placeholder('Дополнительная информацию')->nullable(),
        ];
    }

    public function rules($item): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'entry_actuator_device_id' => ['nullable', 'required_without:exit_actuator_device_id', 'exists:actuator_devices,id'],
            'exit_actuator_device_id' => ['nullable', 'exists:actuator_devices,id'],
            'entryCameras' => ['nullable', 'array'],
            'entryCameras.*' => ['exists:streams,id'],
            'exitCameras' => ['nullable', 'array'],
            'exitCameras.*' => ['exists:streams,id'],
            'comment' => ['nullable', 'string'],
        ];
    }

    public function validationMessages(): array
    {
        return [
            'entry_actuator_device_id.required_without' => 'Укажите хотя бы одно исполнительное устройство: на въезд или на выезд.',
        ];
    }

    public function search(): array
    {
        return ['name', 'comment'];
    }

    public function filters(): array
    {
        return [
            Text::make('Наименование', 'name'),
        ];
    }

    private function lprCameraOptions(): array
    {
        return Stream::query()
            ->get()
            ->filter(fn($s) => ! empty($s->va_options['is_plate_recognition']))
            ->pluck('name', 'id')
            ->toArray();
    }

    private function actuatorDeviceOptions(): array
    {
        return ActuatorDevice::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }
}
