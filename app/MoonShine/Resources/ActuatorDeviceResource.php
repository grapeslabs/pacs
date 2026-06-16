<?php

namespace App\MoonShine\Resources;

use App\Actuators\ActuatorDriverManager;
use App\Actuators\ActuatorService;
use App\Models\ActuatorDevice;
use App\MoonShine\Fields\ColoredSelectField;
use App\MoonShine\Fields\CustomText;
use App\MoonShine\Fields\SelectField;
use Illuminate\Support\Facades\Log;
use MoonShine\Laravel\Http\Responses\MoonShineJsonResponse;
use MoonShine\Laravel\MoonShineRequest;
use MoonShine\Support\Enums\ToastType;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

class ActuatorDeviceResource extends BaseModelResource
{
    protected string $model = ActuatorDevice::class;
    protected string $title = 'Исполнительные устройства';
    protected string $column = 'name';

    public function menuGroup(): string
    {
        return 'Исполнительные устройства';
    }

    private function manager(): ActuatorDriverManager
    {
        return app(ActuatorDriverManager::class);
    }

    public function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),

            Text::make('Название', 'name')->sortable(),

            Text::make('Тип устройства', 'driver_key')
                ->setValue(fn($item) => $item->driver_label),

            ColoredSelectField::make('Статус', 'status')
                ->options([
                    ActuatorDevice::STATUS_ACTIVE   => ['label' => 'Активно',   'color' => 'green'],
                    ActuatorDevice::STATUS_INACTIVE => ['label' => 'Неактивно', 'color' => 'gray'],
                ]),
        ];
    }

    public function formFields(): iterable
    {
        $manager = $this->manager();

        $fields = [
            ID::make(),

            CustomText::make('Название', 'name')->max(255)->required(),
            SelectField::make('Тип устройства', 'driver_key')
                ->options($manager->options())
                ->required(),
        ];

        foreach ($manager->keys() as $key) {
            foreach ($manager->driver($key)->fields() as $field) {
                $fields[] = $field->showWhen('driver_key', $key);
            }
        }

        $fields[] = SelectField::make('Статус', 'status')
            ->options([
                ActuatorDevice::STATUS_ACTIVE => 'Активно',
                ActuatorDevice::STATUS_INACTIVE => 'Неактивно',
            ])
            ->default(ActuatorDevice::STATUS_ACTIVE);

        return $fields;
    }

    public function detailFields(): iterable
    {
        $fields = [
            ID::make(),
            Text::make('Название', 'name'),
            Text::make('Тип устройства', 'driver_key')
                ->setValue(fn($item) => $item->driver_label),
            Text::make('Статус', 'status')
                ->setValue(fn($item) => $item->status_label),
        ];

        $key = (string) ($this->getItem()?->driver_key ?? '');

        if ($this->manager()->has($key)) {
            foreach ($this->manager()->driver($key)->fields() as $field) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    public function rules($item): array
    {
        $rules = [
            'name'       => ['required', 'string', 'max:255'],
            'driver_key' => ['required', 'string'],
            'status'     => ['required', 'string', 'in:active,inactive'],
        ];

        $key = request('driver_key');
        if ($key && $this->manager()->has($key)) {
            $rules = array_merge($rules, $this->manager()->driver($key)->rules());
        }

        return $rules;
    }

    public function search(): array
    {
        return ['name', 'driver_key'];
    }

    public function filters(): array
    {
        return [
            SelectField::make('Тип устройства', 'driver_key')
                ->options($this->manager()->options())
                ->nullable(),

            SelectField::make('Статус', 'status')
                ->options([
                    ActuatorDevice::STATUS_ACTIVE => 'Активно',
                    ActuatorDevice::STATUS_INACTIVE => 'Неактивно',
                ])
                ->nullable(),
        ];
    }

    protected function indexButtons(): ListOf
    {
        return parent::indexButtons()
            ->prepend($this->actionButton('Закрыть', 'doClose', 'lock-closed', 'close'))
            ->prepend($this->actionButton('Открыть', 'doOpen', 'lock-open', 'open'))
            ->prepend($this->actionButton('Тест', 'doTest', 'signal'));
    }

    private function actionButton(string $label, string $method, string $icon, ?string $capability = null): ActionButton
    {
        $button = ActionButton::make(
            '',
            fn($item) => $this->getAsyncMethodUrl(
                method: $method,
                params: ['item_id' => $item->getKey()],
            )
        )
            ->icon($icon)
            ->async()
            ->showInLine()
            ->customAttributes([
                'title' => $label,
                '@click.stop' => '',
            ]);

        if ($capability !== null) {
            $button->canSee(fn($item) => $this->supports($item, $capability));
        }

        return $button;
    }

    private function supports($item, string $capability): bool
    {
        $manager = $this->manager();
        $key = (string) $item->driver_key;

        return $manager->has($key)
            && in_array($capability, $manager->driver($key)->capabilities(), true);
    }

    public function doOpen(MoonShineRequest $request): MoonShineJsonResponse
    {
        return $this->runAction($request, 'open', 'Команда «Открыть» отправлена');
    }

    public function doClose(MoonShineRequest $request): MoonShineJsonResponse
    {
        return $this->runAction($request, 'close', 'Команда «Закрыть» отправлена');
    }

    public function doTest(MoonShineRequest $request): MoonShineJsonResponse
    {
        $device = ActuatorDevice::find($request->get('item_id'));

        if (! $device) {
            return MoonShineJsonResponse::make()->toast('Устройство не найдено', ToastType::ERROR);
        }

        if ($inactive = $this->guardActive($device, 'test')) {
            return $inactive;
        }

        try {
            app(ActuatorService::class)->test($device);

            return MoonShineJsonResponse::make()->toast('Связь с устройством установлена', ToastType::SUCCESS);
        } catch (\Throwable $e) {
            return MoonShineJsonResponse::make()->toast('Нет связи: ' . $e->getMessage(), ToastType::ERROR);
        }
    }

    private function guardActive(ActuatorDevice $device, string $action): ?MoonShineJsonResponse
    {
        if ($device->status === ActuatorDevice::STATUS_ACTIVE) {
            return null;
        }

        Log::warning('Actuator: ручная команда отклонена — устройство неактивно', [
            'device_id'  => $device->id,
            'driver_key' => $device->driver_key,
            'action'     => $action,
        ]);

        return MoonShineJsonResponse::make()->toast(
            "Устройство «{$device->name}» неактивно",
            ToastType::WARNING,
        );
    }

    private function runAction(MoonShineRequest $request, string $action, string $okMessage): MoonShineJsonResponse
    {
        $device = ActuatorDevice::find($request->get('item_id'));

        if (! $device) {
            return MoonShineJsonResponse::make()->toast('Устройство не найдено', ToastType::ERROR);
        }

        if ($inactive = $this->guardActive($device, $action)) {
            return $inactive;
        }

        try {
            app(ActuatorService::class)->execute($device, $action);

            return MoonShineJsonResponse::make()->toast($okMessage, ToastType::SUCCESS);
        } catch (\Throwable $e) {
            return MoonShineJsonResponse::make()->toast('Ошибка: ' . $e->getMessage(), ToastType::ERROR);
        }
    }
}
