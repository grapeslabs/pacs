<?php

namespace App\MoonShine\Resources;

use App\Models\ApiKey;
use App\MoonShine\Fields\CustomDate;
use App\MoonShine\Fields\CustomText;
use App\MoonShine\Fields\SelectField;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Checkbox;
use MoonShine\UI\Fields\Switcher;
use MoonShine\Support\ListOf;
use MoonShine\Laravel\Enums\Action;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Preview;
use Carbon\Carbon;


class ApiKeyResource extends BaseModelResource
{
    protected string $model = ApiKey::class;
    protected string $title = 'Ключи API';


    public function indexButtons(): ListOf
    {
        return parent::indexButtons()
            ->prepend(
                ActionButton::make(
                    '',
                    fn($item) => "javascript:navigator.clipboard.writeText('{$item->key}').then(() => dispatchEvent(new CustomEvent('toast', {detail: {type: 'success', text: 'Ключ скопирован'}})))"
                )
                    ->class('js-copy-button')
                    ->icon('clipboard')
                    ->showInLine()
            );
    }
    public function indexFields(): iterable
    {
        return [
            Text::make('Название', 'name')
                ->sortable()
            ->copy(),

            Date::make('Создан', 'created_at')
                ->withTime(true)
                ->format('d.m.Y H:i')
                ->sortable(),

            Date::make('Срок действия', 'expires_at')
                ->format('d.m.Y')
                ->sortable(),

            Switcher::make('Активен', 'is_active')
                ->onValue(1)
                ->offValue(0)
                ->updateOnPreview()
                ->setLabel('')
                ->sortable(),

            Text::make('Ключ', 'key'),

        ];
    }

    public function formFields(): iterable
    {
        return [
            CustomText::make('Название', 'name')
                ->required()
                ->placeholder('Введите название ключа'),

            Checkbox::make('Бессрочный', 'is_unlimited')
                ->setValue(false),

            CustomDate::make('Срок действия', 'expires_at')
                ->after(Carbon::now(), 'Срок действия не может быть в прощлом')
                ->format('d.m.Y')
                ->nullable()
                ->showWhen('is_unlimited', '=', false),
            ];
    }

    public function rules($item): array
    {
        return [
            'name' => 'required|string|max:255',
            'expires_at' => 'nullable|date',
            'is_unlimited' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function validationMessages(): array
    {
        return [
            'name.required' => 'Поле "Название" обязательно для заполнения',
            'name.max' => 'Название не может быть длиннее 255 символов',
            'expires_at.date' => 'Некорректный формат даты',
        ];
    }

    protected function activeActions(): ListOf
    {
        return parent::activeActions()
            ->except(Action::VIEW);
    }

    public function search(): array
    {
        return ['name', 'key'];
    }

    public function filters(): array
    {
        return [
            Text::make('Название', 'name')
                ->placeholder('Поиск по названию'),
            DateRange::make('Дата создания (с/по)', 'created_at'),
            DateRange::make('Срок действия (с/по)', 'expires_at'),
            Switcher::make('Активен', 'is_active')
                ->offValue(false)
                ->onValue(true),

            Switcher::make('Бессрочный', 'is_unlimited')
                ->offValue(false)
                ->onValue(true)
        ];
    }

    public function modifyQueryBuilder($builder): \Illuminate\Contracts\Database\Eloquent\Builder
    {
        return $builder->orderBy('created_at', 'desc');
    }
}
