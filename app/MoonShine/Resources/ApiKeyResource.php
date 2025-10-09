<?php

namespace App\MoonShine\Resources;

use App\Models\ApiKey;
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
            Text::make('Название', 'name')
                ->required()
                ->placeholder('Введите название ключа'),

            Checkbox::make('Бессрочный', 'is_unlimited')
                ->setValue(false),

            Date::make('Срок действия', 'expires_at')
                ->withTime(false)
                ->format('d.m.Y')
                ->nullable()
                ->showWhen('is_unlimited', '=', false)
                ->hint('Укажите дату окончания действия ключа'),
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
                ->placeholder('Поиск по названию')
                ->onApply(function ($query, $value) {
                    return $value ? $query->where('name', 'like', "%{$value}%") : $query;
                }),

            DateRange::make('Дата создания (с/по)', 'created_at')
                ->format('d.m.Y')
                ->onApply(function ($query, $value) {
                    if (!empty($value['from'])) {
                        $fromDate = date('Y-m-d', strtotime($value['from'])) . ' 00:00:00';
                        $query->where('created_at', '>=', $fromDate);
                    }
                    if (!empty($value['to'])) {
                        $toDate = date('Y-m-d', strtotime($value['to'])) . ' 23:59:59';
                        $query->where('created_at', '<=', $toDate);
                    }
                    return $query;
                }),

            DateRange::make('Срок действия (с/по)', 'expires_at')
                ->format('d.m.Y')
                ->nullable()
                ->onApply(function ($query, $value) {
                    if (!empty($value['from'])) {
                        $fromDate = Carbon::parse($value['from'])->startOfDay();
                        $query->where('expires_at', '>=', $fromDate);
                    }
                    if (!empty($value['to'])) {
                        $toDate = Carbon::parse($value['to'])->endOfDay();
                        $query->where('expires_at', '<=', $toDate);
                    }
                    return $query;
                }),

            Select::make('Активен', 'is_active')
                ->options([
                    '' => 'Все',
                    'active' => 'Активен',
                    'inactive' => 'Неактивен',
                ])
                ->nullable()
                ->default('')
                ->native()
                ->onApply(function ($query, $value) {
                    if ($value === 'active') {
                        return $query->where('is_active', true);
                    } elseif ($value === 'inactive') {
                        return $query->where('is_active', false);
                    }
                    return $query;
                }),

            Select::make('Бессрочный', 'is_unlimited')
                ->options([
                    '' => 'Все',
                    'unlimited' => 'Бессрочные',
                    'limited' => 'Срочные',
                ])
                ->nullable()
                ->default('')
                ->native()
                ->onApply(function ($query, $value) {
                    if ($value === 'unlimited') {
                        return $query->where(function($q) {
                            $q->where('is_unlimited', true)
                                ->orWhereNull('expires_at');
                        });
                    } elseif ($value === 'limited') {
                        return $query->where('is_unlimited', false)
                            ->whereNotNull('expires_at');
                    }
                    return $query;
                }),
        ];
    }

    public function beforeRender(): void
    {
        parent::beforeRender();

        if (moonshineRequest()->isIndex()) {
            ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Принудительный сброс select при нажатии кнопки сброса
                    const resetBtn = document.querySelector('button[type="reset"]');
                    if (resetBtn) {
                        resetBtn.addEventListener('click', function() {
                            setTimeout(() => {
                                document.querySelectorAll('select[name^="filters["]').forEach(select => {
                                    select.value = '';
                                });
                            }, 50);
                        });
                    }
                });
            </script>
            <?php
        }
    }



    public function indexQuery(): \Illuminate\Contracts\Database\Eloquent\Builder
    {
        return parent::indexQuery()->orderBy('created_at', 'desc');
    }
}
