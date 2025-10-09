<?php

namespace App\MoonShine\Resources;

use App\Models\Car;
use App\Models\CarBrand;
use App\Models\CarColor;
use App\Models\Organization;
use App\Models\Person;
use App\MoonShine\Pages\CustomIndexPage;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Textarea;
use MoonShine\Contracts\UI\ActionButtonContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Database\Eloquent\Builder;

class CarResource extends BaseModelResource
{
    protected string $model = Car::class;
    protected string $title = 'Автомобили';
    protected function pages(): array
    {
        return [
            CustomIndexPage::class,
            DetailPage::class,
            FormPage::class,
        ];
    }

    protected function modifyDetailButton(ActionButtonContract $button): ActionButtonContract
    {
        return $button->canSee(fn() => false);
    }

    /**
     * Переопределяем сохранение для синхронизации с Pingate
     */
    public function save($item, $fields = null): mixed
    {
        $data = request()->all();
        $personIds = $data['people_ids'] ?? [];

        if (isset($data['license_plate'])) {
            $data['license_plate'] = $this->cleanLicensePlate($data['license_plate']);
        }

        unset($data['people_ids']);

        $originalRequest = request()->duplicate();
        request()->replace($data);

        try {
            $savedItem = parent::save($item, $fields);

            if ($savedItem instanceof Model && $savedItem->id) {
                // Синхронизируем привязанных людей
                $savedItem->people()->sync($personIds);

                // Отправляем автомобиль в Pingate контроллеры
                $this->syncCarWithPingateControllers($savedItem);
            }

            return $savedItem;
        } finally {
            request()->replace($originalRequest->all());
        }
    }

    /**
     * Синхронизация автомобиля с Pingate контроллерами
     */
    protected function syncCarWithPingateControllers(Car $car): void
    {
        try {
            // Получаем все Pingate контроллеры
            $controllers = \GrapesLabs\PinvideoSkud\Models\SkudController::where('type', 'pingate')->get();

            if ($controllers->isEmpty()) {
                Log::info('No Pingate controllers found for car sync', ['car_id' => $car->id]);
                return;
            }

            $licensePlate = $this->cleanLicensePlate($car->license_plate);

            if (empty($licensePlate)) {
                Log::warning('Car has empty license plate, skipping SKUD sync', ['car_id' => $car->id]);
                return;
            }

            foreach ($controllers as $controller) {
                try {
                    // Отправляем номер на контроллер
                    $result = \GrapesLabs\PinvideoSkud\Controllers\PinGateController\PingateOutputPacketProcessor::add_cars(
                        $controller->id,
                        [$licensePlate]
                    );

                    Log::info('Car sent to Pingate controller', [
                        'car_id' => $car->id,
                        'license_plate' => $licensePlate,
                        'controller_id' => $controller->id,
                        'controller_sn' => $controller->serial_number,
                        'result' => $result,
                    ]);

                } catch (\Exception $e) {
                    Log::error('Error sending car to Pingate controller', [
                        'car_id' => $car->id,
                        'controller_id' => $controller->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            session()->flash('message', 'Автомобиль добавлен во все Pingate контроллеры');

        } catch (\Exception $e) {
            Log::error('Pingate sync failed for car', [
                'car_id' => $car->id ?? 'new',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Ошибка добавления автомобиля в СКУД: ' . $e->getMessage());
        }
    }

    /**
     * Переопределение удаление для удаления из Pingate
     */
    public function delete($item, ?\MoonShine\Contracts\Core\DependencyInjection\FieldsContract $fields = null): bool
    {
        // Удаляем номер из Pingate контроллеров
        $this->removeCarFromPingateControllers($item);
        return parent::delete($item, $fields);
    }

    /**
     * Удаление автомобиля из Pingate контроллеров
     */
    protected function removeCarFromPingateControllers(Car $car): void
    {
        try {
            $controllers = \GrapesLabs\PinvideoSkud\Models\SkudController::where('type', 'pingate')->get();

            if ($controllers->isEmpty()) {
                return;
            }

            $licensePlate = $this->cleanLicensePlate($car->license_plate);

            if (empty($licensePlate)) {
                return;
            }

            foreach ($controllers as $controller) {
                try {
                    // Удаляем номер из контроллера
                    $result = \GrapesLabs\PinvideoSkud\Controllers\PinGateController\PingateOutputPacketProcessor::del_cars(
                        $controller->id,
                        [$licensePlate]
                    );

                    Log::info('Car removed from Pingate controller', [
                        'car_id' => $car->id,
                        'license_plate' => $licensePlate,
                        'controller_id' => $controller->id,
                        'result' => $result,
                    ]);

                } catch (\Exception $e) {
                    Log::error('Error removing car from Pingate controller', [
                        'car_id' => $car->id,
                        'controller_id' => $controller->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Pingate removal failed for car', [
                'car_id' => $car->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Переопределение массового удаления для удаления из Pingate
     */
    public function massDelete(array $ids): void
    {
        // Сначала получаем автомобили для удаления из Pingate
        $cars = Car::whereIn('id', $ids)->get();

        // Удаляем каждый автомобиль из Pingate контроллеров
        foreach ($cars as $car) {
            $this->removeCarFromPingateControllers($car);
        }

        // Теперь выполняем массовое удаление из БД
        parent::massDelete($ids);
    }


    /**
     * Очистка номера от пробелов и форматирование
     */
    protected function cleanLicensePlate(string $licensePlate): string
    {
        // Убираем пробелы и приводим к верхнему регистру
        return strtoupper(preg_replace('/\s+/', '', $licensePlate));
    }

    public function indexFields(): iterable
    {
        return [
            Text::make('ГРЗ', 'license_plate')->sortable(),
            Select::make('Марка', 'brand_id')
                ->options(CarBrand::query()->pluck('name', 'id')->toArray())
                ->sortable(),
            Select::make('Цвет', 'color_id')
                ->options(CarColor::query()->pluck('name', 'id')->toArray())
                ->sortable(),
            Text::make('Персоны', 'people_names')->sortable(function (
                Builder $query,
                string $column,
                string $direction,
            ) {
                return $query->orderBy(
                    DB::raw("(SELECT last_name FROM person WHERE person.id IN (SELECT person_id FROM car_person WHERE car_person.car_id = cars.id) ORDER BY last_name {$direction} LIMIT 1)"),
                    $direction,
                );
            }),
            Select::make('Организация', 'organization_id')
                ->options(Organization::query()->get()->pluck('short_name', 'id')->toArray())
                ->searchable()
                ->nullable()
                ->sortable(),
            Textarea::make('Комментарий', 'comment')
                ->sortable(),
        ];
    }

    public function formFields(): iterable
    {
        $personIds = [];
        if ($this->getItem() && $this->getItem()->exists) {
            $personIds = $this->getItem()->people->pluck('id')->toArray();
        }

        return [
            Text::make('ГРЗ', 'license_plate')
                ->required()
                ->customAttributes([
                    'x-data' => '{ licensePlate: $el.value || "" }',
                    'x-model' => 'licensePlate',
                    'x-on:input' => '
                    let value = $event.target.value.toUpperCase();
                    let formatted = "";
                    let cursorPos = $event.target.selectionStart;

                    value = value.replace(/[^А-Я0-9\s]/g, "");

                    let chars = value.replace(/\s/g, "").split("");
                    let result = [];

                    for (let i = 0; i < chars.length; i++) {
                        if (i === 0) {
                            if (/[А-Я]/.test(chars[i])) {
                                result.push(chars[i]);
                            }
                        } else if (i >= 1 && i <= 3) {
                            if (/[0-9]/.test(chars[i])) {
                                result.push(chars[i]);
                            }
                        } else if (i >= 4 && i <= 5) {
                            if (/[А-Я]/.test(chars[i])) {
                                result.push(chars[i]);
                            }
                        } else if (i >= 6 && i <= 8) {
                            if (/[0-9]/.test(chars[i])) {
                                result.push(chars[i]);
                            }
                        }
                    }

                    if (result.length > 0) formatted += result[0];
                    if (result.length > 1) formatted += " " + result.slice(1, 4).join("");
                    if (result.length > 4) formatted += " " + result.slice(4, 6).join("");
                    if (result.length > 6) formatted += " " + result.slice(6, 9).join("");

                    $event.target.value = formatted;
                ',
                    'placeholder' => 'А 123 БЦ 78',
                    'maxlength' => '15'
                ])
                ->placeholder('А 000 АА 777')
                ->hint('Используйте только: А, В, Е, К, М, Н, О, Р, С, Т, У, Х'),
            Select::make('Марка', 'brand_id')
                ->required()
                ->options(CarBrand::query()->pluck('name', 'id')->toArray())
                ->searchable(),
            Select::make('Цвет', 'color_id')
                ->required()
                ->options(CarColor::query()->pluck('name', 'id')->toArray())
                ->searchable(),
            Select::make('Персоны', 'people_ids')
                ->options(Person::query()->pluck('last_name', 'id')->toArray())
                ->searchable()
                ->multiple()
                ->default($personIds),
            Select::make('Организация', 'organization_id')
                ->options(Organization::query()->get()->pluck('short_name', 'id')->toArray())
                ->searchable()
                ->nullable(),
            Textarea::make('Комментарий', 'comment')->nullable(),
        ];
    }

    public function detailFields(): iterable
    {
        return [
            ID::make(),
            Text::make('ГРЗ', 'license_plate'),
            Select::make('Марка', 'brand_id')
                ->options(CarBrand::query()->pluck('name', 'id')->toArray()),
            Select::make('Цвет', 'color_id')
                ->options(CarColor::query()->pluck('name', 'id')->toArray()),
            Select::make('Организация', 'organization_id')
                ->options(Organization::query()->pluck('short_name', 'id')->toArray()),
            Textarea::make('Комментарий', 'comment'),
        ];
    }

    public function rules($item): array
    {
        return [
            'license_plate' => ['required', 'string', 'max:20', 'unique:cars,license_plate,' . ($item?->id ?? 0)],
            'brand_id' => ['required', 'exists:car_brands,id'],
            'color_id' => ['required', 'exists:car_colors,id'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'people_ids' => ['nullable', 'array'],
            'people_ids.*' => ['exists:person,id'],
            'comment' => ['nullable', 'string'],
        ];
    }

    public function search(): array
    {
        return ['license_plate', 'organization.short_name', 'brand.name', 'color.name', 'people.last_name','comment'];
    }

    public function filters(): array
    {
        return [
            Text::make('ГРЗ', 'license_plate')
                ->customAttributes([
                    'x-data' => '{ licensePlate: "" }',
                    'x-model' => 'licensePlate',
                    'x-on:input' => '
                    let value = $event.target.value.toUpperCase();
                    let formatted = "";
                    let cursorPos = $event.target.selectionStart;

                    value = value.replace(/[^А-Я0-9\s]/g, "");

                    let chars = value.replace(/\s/g, "").split("");
                    let result = [];

                    for (let i = 0; i < chars.length; i++) {
                        if (i === 0) {
                            if (/[А-Я]/.test(chars[i])) {
                                result.push(chars[i]);
                            }
                        } else if (i >= 1 && i <= 3) {
                            if (/[0-9]/.test(chars[i])) {
                                result.push(chars[i]);
                            }
                        } else if (i >= 4 && i <= 5) {
                            if (/[А-Я]/.test(chars[i])) {
                                result.push(chars[i]);
                            }
                        } else if (i >= 6 && i <= 8) {
                            if (/[0-9]/.test(chars[i])) {
                                result.push(chars[i]);
                            }
                        }
                    }

                    if (result.length > 0) formatted += result[0];
                    if (result.length > 1) formatted += " " + result.slice(1, 4).join("");
                    if (result.length > 4) formatted += " " + result.slice(4, 6).join("");
                    if (result.length > 6) formatted += " " + result.slice(6, 9).join("");

                    $event.target.value = formatted;
                ',
                    'placeholder' => 'Фильтрация по ГРЗ',
                    'maxlength' => '15'
                ]),
            Select::make('Марка', 'brand_id')
                ->options(CarBrand::query()->pluck('name', 'id')->toArray())
                ->searchable()
                ->nullable(),
            Select::make('Цвет', 'color_id')
                ->options(CarColor::query()->pluck('name', 'id')->toArray())
                ->searchable()
                ->nullable(),
            Select::make('Организация', 'organization_id')
                ->options(Organization::query()->get()->pluck('short_name', 'id')->toArray())
                ->searchable()
                ->nullable(),
            Select::make('Персоны', 'people_filter')
                ->options(Person::query()->pluck('last_name', 'id')->toArray())
                ->searchable()
                ->multiple()
                ->nullable()
                ->onApply(function (Builder $query, $value) {
                    if (empty($value)) {
                        return $query;
                    }

                    return $query->whereHas('people', function ($q) use ($value) {
                        $q->whereIn('person.id', (array)$value);
                    });
                }),
            Text::make('Комментарий', 'comment'),
        ];
    }

    public function indexQuery(): \Illuminate\Contracts\Database\Query\Builder
    {
        return parent::indexQuery()->with(['brand', 'color', 'organization', 'people']);
    }
}
