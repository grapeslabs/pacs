<?php

namespace App\MoonShine\Resources;

use App\Models\Passage;
use App\Models\Stream;
use App\MoonShine\Fields\CustomText;
use App\MoonShine\Fields\CustomTextarea;
use App\MoonShine\Fields\SelectField;
use App\MoonShine\Pages\CustomIndexPage;
use GrapesLabs\PinvideoSkud\Models\SkudController;
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

    private const OPENABLE_CONTROLLER_TYPES = ['z5rweb', 'ironlogic'];

    protected function pages(): array
    {
        return [CustomIndexPage::class, DetailPage::class, FormPage::class];
    }

    public function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Наименование', 'name')->sortable(),
            Text::make('Контроллер въезда', 'entryController', fn($item) => $item->entryController?->serial_number ?? '—'),
            Text::make('Камеры въезда', 'entryCameras', fn($item) => $item->entryCameras->pluck('name')->implode(', ') ?: '—'),
            Text::make('Контроллер выезда', 'exitController', fn($item) => $item->exitController?->serial_number ?? '—'),
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
                        SelectField::make('Контроллер', 'entry_controller_id')
                            ->nullable()
                            ->options($this->controllerOptions()),
                    ], colSpan: 4, adaptiveColSpan: 12),

                    Column::make([
                        SelectField::make('Камеры', 'entryCameras')
                            ->multiple()
                            ->options($this->grzCameraOptions()),
                    ], colSpan: 8, adaptiveColSpan: 12),
                ]),
            ]),

            Box::make('Выезд', [
                Grid::make([
                    Column::make([
                        SelectField::make('Контроллер', 'exit_controller_id')
                            ->nullable()
                            ->options($this->controllerOptions()),
                    ], colSpan: 4, adaptiveColSpan: 12),

                    Column::make([
                        SelectField::make('Камеры', 'exitCameras')
                            ->multiple()
                            ->options($this->grzCameraOptions()),
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
            Text::make('Контроллер въезда', 'entryController', fn($item) => $item->entryController?->serial_number ?? '—'),
            Text::make('Камеры въезда', 'entryCameras', fn($item) => $item->entryCameras->pluck('name')->implode(', ') ?: '—'),
            Text::make('Контроллер выезда', 'exitController', fn($item) => $item->exitController?->serial_number ?? '—'),
            Text::make('Камеры выезда', 'exitCameras', fn($item) => $item->exitCameras->pluck('name')->implode(', ') ?: '—'),
            Text::make('Комментарий', 'comment'),
        ];
    }

    public function rules($item): array
    {
        return [
            'name'                => ['required', 'string', 'max:255'],
            'entry_controller_id' => ['nullable', 'required_without:exit_controller_id', 'exists:grapeslabs_skud_controllers,id'],
            'exit_controller_id'  => ['nullable', 'required_without:entry_controller_id', 'exists:grapeslabs_skud_controllers,id'],
            'entryCameras'        => ['nullable', 'array'],
            'entryCameras.*'      => ['exists:streams,id'],
            'exitCameras'         => ['nullable', 'array'],
            'exitCameras.*'       => ['exists:streams,id'],
            'comment'             => ['nullable', 'string'],
        ];
    }

    public function validationMessages(): array
    {
        return [
            'entry_controller_id.required_without' => 'Необходимо заполнить хотя бы один блок: «Въезд» или «Выезд».',
            'exit_controller_id.required_without'  => 'Необходимо заполнить хотя бы один блок: «Въезд» или «Выезд».',
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
        return parent::indexQuery()->with(['entryController', 'exitController', 'entryCameras', 'exitCameras']);
    }

    private function grzCameraOptions(): array
    {
        return Stream::query()
            ->get()
            ->filter(fn($s) => ! empty($s->va_options['is_plate_recognition']))
            ->pluck('name', 'id')
            ->toArray();
    }

    private function controllerOptions(): array
    {
        return SkudController::query()
            ->whereIn('type', self::OPENABLE_CONTROLLER_TYPES)
            ->get()
            ->mapWithKeys(fn (SkudController $c) => [
                $c->id => trim(($c->serial_number ?? (string) $c->id) . ' (' . $c->type . ')'),
            ])
            ->toArray();
    }
}
