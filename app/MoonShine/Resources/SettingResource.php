<?php

namespace App\MoonShine\Resources;

use App\Models\Setting;
use App\MoonShine\Pages\CustomIndexPage;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Textarea;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Password;

class SettingResource extends BaseModelResource
{
    protected string $model = Setting::class;
    protected string $title = 'Настройки';
    protected string $column = 'name';
    protected function pages(): array
    {
        return [
            CustomIndexPage::class,
            DetailPage::class,
            FormPage::class,
        ];
    }

    protected function beforeSave(mixed $item): mixed
    {
        $request = request()->all();
        $type = $request['type'] ?? Setting::TYPE_TEXT;
        $virtualKey = 'value_' . $type;
        $item->value = $request[$virtualKey] ?? null;
        $virtualFields = [
            'value_' . Setting::TYPE_TEXT,
            'value_' . Setting::TYPE_TEXTAREA,
            'value_' . Setting::TYPE_NUMBER,
            'value_' . Setting::TYPE_BOOLEAN,
            'value_' . Setting::TYPE_SELECT,
            'value_' . Setting::TYPE_JSON,
            'value_' . Setting::TYPE_PASSWORD,
        ];

        foreach ($virtualFields as $field) {
            unset($item->$field);
        }

        return $item;
    }

    protected function beforeCreating(mixed $item): mixed
    {
        $item = $this->beforeSave($item);
        return $item;
    }

    protected function beforeUpdating(mixed $item): mixed
    {
        $item = $this->beforeSave($item);
        return $item;
    }

    public function indexFields(): iterable
    {
        return [
            ID::make()
                ->sortable(),

            Text::make('Ключ', 'key')
                ->sortable(),

            Text::make('Название', 'name')
                ->sortable(),

            Select::make('Группа', 'group')
                ->options([
                    Setting::GROUP_GENERAL => 'Основные',
                    Setting::GROUP_SYSTEM => 'Системные',
                    Setting::GROUP_INTEGRATION => 'Интеграции',
                    Setting::GROUP_EMAIL => 'Email',
                    Setting::GROUP_SECURITY => 'Безопасность',
                    Setting::GROUP_OTHER => 'Прочие',
                ])
                ->sortable(),

            Select::make('Тип', 'type')
                ->options([
                    Setting::TYPE_TEXT => 'Текст',
                    Setting::TYPE_TEXTAREA => 'Текстовое поле',
                    Setting::TYPE_NUMBER => 'Число',
                    Setting::TYPE_BOOLEAN => 'Да/Нет',
                    Setting::TYPE_SELECT => 'Выпадающий список',
                    Setting::TYPE_JSON => 'JSON',
                    Setting::TYPE_PASSWORD => 'Пароль',
                ])
                ->sortable(),

            Checkbox::make('Публичная', 'is_public')
                ->sortable(),

            Number::make('Порядок', 'sort_order')
                ->sortable(),
        ];
    }

    public function formFields(): iterable
    {
        return [
            ID::make(),

            Text::make('Ключ', 'key')
                ->required(),

            Text::make('Название', 'name')
                ->required(),

            Textarea::make('Описание', 'description')
                ->nullable(),

            Select::make('Тип поля', 'type')
                ->options([
                    Setting::TYPE_TEXT => 'Текст',
                    Setting::TYPE_TEXTAREA => 'Текстовое поле',
                    Setting::TYPE_NUMBER => 'Число',
                    Setting::TYPE_BOOLEAN => 'Да/Нет',
                    Setting::TYPE_SELECT => 'Выпадающий список',
                    Setting::TYPE_JSON => 'JSON',
                    Setting::TYPE_PASSWORD => 'Пароль',
                ])
                ->default(Setting::TYPE_TEXT)
                ->required(),

            $this->getValueField(),

            Json::make('Опции', 'options')
                ->keyValue('Ключ', 'Значение')
                ->removable()
                ->nullable(),

            Select::make('Группа', 'group')
                ->options([
                    Setting::GROUP_GENERAL => 'Основные',
                    Setting::GROUP_SYSTEM => 'Системные',
                    Setting::GROUP_INTEGRATION => 'Интеграции',
                    Setting::GROUP_EMAIL => 'Email',
                    Setting::GROUP_SECURITY => 'Безопасность',
                    Setting::GROUP_OTHER => 'Прочие',
                ])
                ->default(Setting::GROUP_GENERAL),

            Number::make('Порядок сортировки', 'sort_order')
                ->default(0),

            Checkbox::make('Публичная настройка', 'is_public')
                ->default(false),

            Checkbox::make('Шифровать значение', 'is_encrypted')
                ->default(false),
        ];
    }

    private function getValueField()
    {
        $type = request()->input('type', $this->getItem()?->type);

        switch ($type) {
            case Setting::TYPE_TEXTAREA:
                return Textarea::make('Значение', 'value')
                    ->nullable();

            case Setting::TYPE_NUMBER:
                return Number::make('Значение', 'value')
                    ->nullable();

            case Setting::TYPE_BOOLEAN:
                return Checkbox::make('Значение', 'value');

            case Setting::TYPE_PASSWORD:
                return Password::make('Значение', 'value')
                    ->nullable();

            case Setting::TYPE_JSON:
                return Json::make('Значение', 'value')
                    ->keyValue('Ключ', 'Значение')
                    ->nullable();

            default:
                return Text::make('Значение', 'value')
                    ->nullable();
        }
    }

    public function detailFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Ключ', 'key'),
            Text::make('Название', 'name'),
            Textarea::make('Описание', 'description'),
            Text::make('Тип поля', 'type')
                ->setValue(fn($item) => $item->type_label),
            Text::make('Значение', 'value'),
            Json::make('Опции', 'options')
                ->keyValue('Ключ', 'Значение'),
            Text::make('Группа', 'group')
                ->setValue(fn($item) => $item->group_label),
            Number::make('Порядок сортировки', 'sort_order'),
            Checkbox::make('Публичная настройка', 'is_public'),
            Checkbox::make('Шифрованное значение', 'is_encrypted'),
        ];
    }

    public function rules($item): array
    {
        return [
            'key' => ['required', 'string', 'max:100', 'unique:settings,key,' . ($item?->id ?? 0), 'regex:/^[a-z0-9_]+$/'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:text,textarea,number,boolean,select,json,password'],
            'value' => ['nullable'],
            'options' => ['nullable', 'array'],
            'group' => ['required', 'string', 'in:general,system,integration,email,security,other'],
            'sort_order' => ['integer', 'min:0'],
            'is_public' => ['boolean'],
            'is_encrypted' => ['boolean'],
        ];
    }

    public function search(): array
    {
        return [
            'key',
            'name',
            'description'
        ];
    }

    public function filters(): array
    {
        return [
            Select::make('Группа', 'group')
                ->options([
                    Setting::GROUP_GENERAL => 'Основные',
                    Setting::GROUP_SYSTEM => 'Системные',
                    Setting::GROUP_INTEGRATION => 'Интеграции',
                    Setting::GROUP_EMAIL => 'Email',
                    Setting::GROUP_SECURITY => 'Безопасность',
                    Setting::GROUP_OTHER => 'Прочие',
                ])
                ->nullable(),

            Select::make('Тип', 'type')
                ->options([
                    Setting::TYPE_TEXT => 'Текст',
                    Setting::TYPE_TEXTAREA => 'Текстовое поле',
                    Setting::TYPE_NUMBER => 'Число',
                    Setting::TYPE_BOOLEAN => 'Да/Нет',
                    Setting::TYPE_SELECT => 'Выпадающий список',
                    Setting::TYPE_JSON => 'JSON',
                    Setting::TYPE_PASSWORD => 'Пароль',
                ])
                ->searchable(false)
                ->nullable()
                ->placeholder('Фильтрация по типу'),
        ];
    }
}
