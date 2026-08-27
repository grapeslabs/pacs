<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Models\Person;
use App\Models\Stream;
use App\Models\Tag;
use App\MoonShine\Fields\CustomDate;
use App\MoonShine\Fields\CustomNumber;
use App\MoonShine\Components\PresenceReportComponent;
use App\MoonShine\Fields\SelectField;
use MoonShine\Laravel\Enums\Ability;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\Laravel\Pages\Page;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\Heading;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Flex;
use MoonShine\UI\Components\Layout\Grid;

class PresenceReportPage extends Page
{
    protected string $title = 'Отчёт пребывания';
    protected ?array $breadcrumbs = ['Отчёт пребывания'];

    public function getTitle(): string
    {
        return $this->title;
    }

    protected function components(): iterable
    {
        abort_if(
            !MoonShineAuth::getGuard()->user()?->hasPermission(self::class, Ability::VIEW),
            403
        );
        $cameras = Stream::query()->orderBy('name')->get()
            ->mapWithKeys(fn ($s) => [$s->uid => $s->name])
            ->toArray();

        $persons = Person::query()->orderBy('last_name')->get()
            ->mapWithKeys(fn ($p) => [$p->id => $p->getFullName()])
            ->toArray();

        $tags = Tag::query()->orderBy('name')->pluck('name', 'id')->toArray();

        $form = FormBuilder::make(route('presence-report.generate'))
            ->name('presence-report-form')
            ->fields([
                Grid::make([
                    Column::make([
                        CustomDate::make('Дата от', 'date_from')
                            ->beforeField('date_to', 'Дата от должна быть раньше «Дата до»')
                            ->required(),
                    ])->columnSpan(6),
                    Column::make([
                        CustomDate::make('Дата до', 'date_to')
                            ->afterField('date_from', 'Дата до должна быть позже «Дата от»')
                            ->required(),
                    ])->columnSpan(6),
                ]),
                Grid::make([
                    Column::make([
                        SelectField::make('Камера на вход', 'entry_camera')
                            ->options($cameras)
                            ->placeholder('Выберите камеру')
                            ->required(),
                    ])->columnSpan(6),
                    Column::make([
                        SelectField::make('Камера на выход', 'exit_camera')
                            ->options($cameras)
                            ->placeholder('Выберите камеру')
                            ->required(),
                    ])->columnSpan(6),
                ]),
                Grid::make([
                    Column::make([
                        SelectField::make('Тип отчёта', 'type')
                            ->options([
                                'day' => 'По дням',
                                'week' => 'По неделям',
                                'month' => 'По месяцам',
                                'year' => 'По годам',
                            ])
                            ->default('day')
                            ->required(),
                    ])->columnSpan(6),
                    Column::make([
                        CustomNumber::make('Время обрыва (мин)', 'break_minutes')
                            ->default(30)
                            ->minValue(1, 'Минимум 1 минута')
                            ->integer()
                            ->required(),
                    ])->columnSpan(6),
                ]),
                Grid::make([
                    Column::make([
                        SelectField::make('Персоны', 'person_id')
                            ->options($persons)
                            ->multiple(true)
                            ->placeholder('Все'),
                    ])->columnSpan(6),
                    Column::make([
                        SelectField::make('Теги', 'tag_id')
                            ->options($tags)
                            ->multiple(true)
                            ->placeholder('Все'),
                    ])->columnSpan(6),
                ]),
            ])
            ->submit('Сформировать', ['class' => 'btn-primary']);

        $exportButton = ActionButton::make('Экспорт', '#')
            ->customView('components.export-button')
            ->customAttributes([
                'data-excel-url' => '#',
                'data-csv-url' => '#',
            ]);

        $formButton = ActionButton::make('Сформировать отчёт', '#')
            ->primary()
            ->icon('plus')
            ->customAttributes([
                'onclick' => "window.dispatchEvent(new CustomEvent('presence-open-form')); return false;",
            ]);

        return [
            Flex::make([
                Heading::make($this->getTitle())->class('truncate text-md font-medium'),
                Flex::make([
                    $exportButton,
                    $formButton,
                ])->class('gap-4'),
            ])->justifyAlign('between')->itemsAlign('center')->class('mb-6'),

            PresenceReportComponent::make(
                formHtml: (string) $form->render(),
                generateUrl: route('presence-report.generate'),
                statusUrlTpl: route('presence-report.status', ['report' => '__ID__']),
                exportUrlTpl: route('presence-report.export', ['report' => '__ID__']),
                exportCsvUrlTpl: route('presence-report.export', ['report' => '__ID__', 'format' => 'csv']),
            ),
        ];
    }
}
