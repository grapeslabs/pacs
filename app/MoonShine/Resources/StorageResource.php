<?php

namespace App\MoonShine\Resources;

use App\Models\Storage;
use App\MoonShine\Fields\CustomNumber;
use App\MoonShine\Fields\CustomPassword;
use App\MoonShine\Fields\CustomText;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\Layout\Div;
use MoonShine\UI\Components\Layout\Divider;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Password;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

class StorageResource extends BaseModelResource
{
    protected string $model = Storage::class;
    protected string $title = 'Хранилища';
    protected string $column = 'name';

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->except(Action::VIEW);
    }

    public function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Название', 'name')->sortable(),
            Text::make('Тип', 'type', fn($item) => Storage::$types[$item->type] ?? $item->type)->sortable(),
            Switcher::make('Статус', 'is_active')
                ->updateOnPreview()
                ->onValue(true)
                ->offValue(false),
            Text::make('Комментарий', 'comment')
        ];
    }

    public function formFields(): iterable
    {
        return [
            ID::make(),
            Checkbox::make('Активен', 'is_active')->default(true),
            CustomText::make('Название', 'name')->required()
                ->max(255),
            Select::make('Тип', 'type')
                ->options(Storage::$types)
                ->required(),
            Textarea::make('Комментарий', 'comment'),
            Divider::make('Параметры хранилища'),
            //s3
            CustomText::make('Шлюз', 's3_endpoint')
                ->changeFill(fn($model) => $model->getData('s3_endpoint'))
                ->onApply(fn($model, $value) =>  $model->setData('s3_endpoint', $value))
                ->url()
                ->showWhen('type', 's3'),
            CustomText::make('Бакет', 's3_bucket')
                ->changeFill(fn($model) => $model->getData('s3_bucket'))
                ->onApply(fn($model, $value) =>  $model->setData('s3_bucket', $value))
                ->showWhen('type', 's3'),
            CustomText::make('Ключ', 's3_key')
                ->changeFill(fn($model) => $model->getData('s3_key'))
                ->onApply(fn($model, $value) =>  $model->setData('s3_key', $value))
                ->showWhen('type', 's3')
                ->customAttributes(['autocomplete' => 'off']),
            CustomPassword::make('Секретный ключ', 's3_secret')
                ->changeFill(fn($model) => $model->getData('s3_secret'))
                ->onApply(fn($model, $value) =>  $model->setData('s3_secret', $value))
                ->showWhen('type', 's3')
                ->customAttributes(['autocomplete' => 'off']),
            CustomText::make('Регион', 's3_region')
                ->changeFill(fn($model) => $model->getData('s3_region'))
                ->onApply(fn($model, $value) =>  $model->setData('s3_region', $value))
                ->showWhen('type', 's3'),
            //sftp
            CustomText::make('Хост', 'sftp_host')
                ->changeFill(fn($model) => $model->getData('sftp_host'))
                ->onApply(fn($model, $value) =>  $model->setData('sftp_host', $value))
                ->showWhen('type', 'sftp'),
            CustomNumber::make('Порт', 'sftp_port')
                ->changeFill(fn($model) => $model->getData('sftp_port'))
                ->onApply(fn($model, $value) =>  $model->setData('sftp_port', $value))
                ->showWhen('type', 'sftp'),
            CustomText::make('Логин', 'sftp_login')
                ->changeFill(fn($model) => $model->getData('sftp_login'))
                ->onApply(fn($model, $value) =>  $model->setData('sftp_login', $value))
                ->showWhen('type', 'sftp'),
            CustomPassword::make('Пароль', 'sftp_password')
                ->changeFill(fn($model) => $model->getData('sftp_password'))
                ->onApply(fn($model, $value) =>  $model->setData('sftp_password', $value))
                ->showWhen('type', 'sftp'),
            CustomText::make('Путь', 'sftp_path')
                ->changeFill(fn($model) => $model->getData('sftp_path'))
                ->onApply(fn($model, $value) =>  $model->setData('sftp_path', $value))
                ->showWhen('type', 'sftp'),
            //ftp
            CustomText::make('Хост', 'ftp_host')
                ->changeFill(fn($model) => $model->getData('ftp_host'))
                ->onApply(fn($model, $value) =>  $model->setData('ftp_host', $value))
                ->showWhen('type', 'ftp'),
            CustomNumber::make('Порт', 'ftp_port')
                ->changeFill(fn($model) => $model->getData('ftp_port'))
                ->onApply(fn($model, $value) =>  $model->setData('ftp_port', $value))
                ->showWhen('type', 'ftp'),
            CustomText::make('Логин', 'ftp_login')
                ->changeFill(fn($model) => $model->getData('ftp_login'))
                ->onApply(fn($model, $value) =>  $model->setData('ftp_login', $value))
                ->showWhen('type', 'ftp'),
            CustomPassword::make('Пароль', 'ftp_password')
                ->changeFill(fn($model) => $model->getData('ftp_password'))
                ->onApply(fn($model, $value) =>  $model->setData('ftp_password', $value))
                ->showWhen('type', 'ftp'),
            CustomText::make('Путь', 'ftp_path')
                ->changeFill(fn($model) => $model->getData('ftp_path'))
                ->onApply(fn($model, $value) =>  $model->setData('ftp_path', $value))
                ->showWhen('type', 'ftp'),
        ];
    }

    protected function search(): array
    {
        return ['name', 'type', 'comment'];
    }
}
