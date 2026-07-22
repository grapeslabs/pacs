<?php

namespace App\MoonShine\Resources;

use App\Actuators\ActuatorDriverManager;
use App\Actuators\ActuatorService;
use App\Models\ActuatorDevice;
use Illuminate\Database\Eloquent\Model;
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
    protected ?string $editModalTitle = 'Редактирование устройства';
    protected string $column = 'name';
    protected bool $detailSidebar = true;
    protected ?string $createLabel = 'Добавить устройство';

    public function detailSidebarData(Model $item): array
    {
        $icPlug = '<svg viewBox="0 0 24 24" fill="none"><path d="M9 3v5M15 3v5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M6 8h12v3a6 6 0 0 1-12 0z" stroke="currentColor" stroke-width="1.6"/><path d="M12 17v4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';
        $icGear = '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.6"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.5 5.5l2 2M16.5 16.5l2 2M18.5 5.5l-2 2M7.5 16.5l-2 2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';

        $active = $item->status === ActuatorDevice::STATUS_ACTIVE;

        $settingLabels = [
            'modbus_tcp_host' => 'Modbus TCP · Хост',
            'modbus_tcp_port' => 'Modbus TCP · Порт',
            'modbus_tcp_unit_id' => 'Modbus TCP · Unit ID',
            'modbus_tcp_function' => 'Modbus TCP · Функция',
            'modbus_tcp_address' => 'Modbus TCP · Адрес',
            'modbus_tcp_on_value' => 'Modbus TCP · Значение «вкл»',
            'modbus_tcp_off_value' => 'Modbus TCP · Значение «выкл»',
            'dingtian_dt_r002_host' => 'Dingtian · Хост',
            'dingtian_dt_r002_port' => 'Dingtian · Порт',
            'dingtian_dt_r002_unit_id' => 'Dingtian · Unit ID',
            'dingtian_dt_r002_relay' => 'Dingtian · Реле',
            'dingtian_dt_r002_duration' => 'Dingtian · Длительность, с',
            'http_base_url' => 'HTTP · Базовый URL',
            'http_channel' => 'HTTP · Канал',
            'http_open_url' => 'HTTP · URL открытия',
            'http_close_url' => 'HTTP · URL закрытия',
            'http_method' => 'HTTP · Метод',
        ];

        $fields = [['label' => 'Драйвер', 'value' => $item->driver_label]];
        foreach (($item->settings ?? []) as $key => $value) {
            if (is_array($value)) {
                continue;
            }
            if (is_bool($value)) {
                $display = $value ? 'Да' : 'Нет';
            } else {
                $display = blank($value) ? 'Нет' : (string) $value;
            }
            $fields[] = [
                'label' => $settingLabels[$key] ?? $key,
                'value' => $display,
            ];
        }

        $footer = [];
        foreach ([
            ['open', 'doOpen', 'lock-open', 'Открыть'],
            ['close', 'doClose', 'lock-closed', 'Закрыть'],
            ['test', 'doTest', 'signal', 'Проверка связи'],
        ] as [$capability, $method, $icon, $label]) {
            if (! $this->supports($item, $capability)) {
                continue;
            }
            $url = $this->getAsyncMethodUrl($method, page: $this->getIndexPage(), params: ['item_id' => $item->getKey()]);
            $footer[] = ['html' => (string) ActionButton::make('', $url)
                ->icon($icon)
                ->async()
                ->customAttributes(['class' => 'ds-fbtn', 'title' => $label])];
        }
        $footer[] = ['spacer' => true];
        $footer[] = ['html' => $this->detailEditButtonHtml($item)];

        return [
            'title' => 'Карточка устройства',
            'subtitle' => $item->driver_label,
            'hero' => [
                'title' => $item->name,
                'subtitle' => $item->driver_label,
                'icon' => $icPlug,
                'badges' => [
                    $active
                        ? ['text' => $item->status_label]
                        : ['text' => $item->status_label, 'tone' => 'muted'],
                ],
            ],
            'sections' => [
                [
                    'icon' => $icGear,
                    'title' => 'Параметры',
                    'fields' => $fields,
                ],
            ],
            'footer' => $footer,
        ];
    }

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
            ->prepend($this->actionButton('Тест', 'doTest', 'signal', 'test'));
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
                'class' => 'js-actuator-button',
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
