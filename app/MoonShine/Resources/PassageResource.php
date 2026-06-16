<?php

namespace App\MoonShine\Resources;

use App\Models\ActuatorDevice;
use App\Models\Passage;
use App\Models\Stream;
use App\MoonShine\Fields\CustomText;
use App\MoonShine\Fields\CustomTextarea;
use App\MoonShine\Fields\SelectField;
use App\MoonShine\Pages\CustomIndexPage;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

class PassageResource extends BaseModelResource
{
    protected string $model = Passage::class;
    protected string $title = 'Проезды';
    protected string $column = 'name';

    protected function pages(): array
    {
        return [CustomIndexPage::class, DetailPage::class, FormPage::class];
    }

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
                Grid::make([
                    Column::make([
                        SelectField::make('Исп. устройство', 'entry_actuator_device_id')
                            ->nullable()
                            ->options($this->actuatorDeviceOptions()),
                    ], colSpan: 4, adaptiveColSpan: 12),

                    Column::make([
                        SelectField::make('Камеры', 'entryCameras')
                            ->multiple()
                            ->options($this->lprCameraOptions()),
                    ], colSpan: 8, adaptiveColSpan: 12),
                ]),
            ]),

            Box::make('Выезд', [
                Grid::make([
                    Column::make([
                        SelectField::make('Исп. устройство', 'exit_actuator_device_id')
                            ->nullable()
                            ->options($this->actuatorDeviceOptions()),
                    ], colSpan: 4, adaptiveColSpan: 12),

                    Column::make([
                        SelectField::make('Камеры', 'exitCameras')
                            ->multiple()
                            ->options($this->lprCameraOptions()),
                    ], colSpan: 8, adaptiveColSpan: 12),
                ]),
            ]),

            CustomTextarea::make('Комментарий', 'comment')->nullable(),
        ];
    }

    public function detailFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Наименование', 'name'),
            Text::make('Устройство въезда', 'entryActuatorDevice', fn($item) => $item->entryActuatorDevice?->name ?? '—'),
            Text::make('Камеры въезда', 'entryCameras', fn($item) => $item->entryCameras->pluck('name')->implode(', ') ?: '—'),
            Text::make('Устройство выезда', 'exitActuatorDevice', fn($item) => $item->exitActuatorDevice?->name ?? '—'),
            Text::make('Камеры выезда', 'exitCameras', fn($item) => $item->exitCameras->pluck('name')->implode(', ') ?: '—'),
            Text::make('Комментарий', 'comment'),
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

    public function indexQuery(): \Illuminate\Contracts\Database\Query\Builder
    {
        return parent::indexQuery()->with(['entryCameras', 'exitCameras', 'entryActuatorDevice', 'exitActuatorDevice']);
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
