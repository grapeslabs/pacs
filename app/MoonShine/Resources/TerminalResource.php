<?php

namespace App\MoonShine\Resources;

use App\MoonShine\Pages\CustomIndexPage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use GrapesLabs\PinvideoSkud\Models\SkudController;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Support\ListOf;

class TerminalResource extends BaseModelResource
{
    protected string $model = SkudController::class;
    protected string $title = 'Терминалы доступа';
    protected string $column = 'serial_number';
    protected function pages(): array
    {
        return [
            CustomIndexPage::class,
            DetailPage::class,
            FormPage::class,
        ];
    }

    public function menuGroup(): string
    {
        return 'Оборудование';
    }

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        $allowedTypes = ['pinterm'];
        return $builder->whereiN('type', $allowedTypes);
    }

    protected function activeActions(): ListOf
    {
        return new ListOf(Action::class, [Action::VIEW, Action::DELETE]);
    }

    public function indexFields(): iterable
    {
        return [
            ID::make('ID', 'id')->sortable(),
            Text::make('Серийный номер', 'serial_number')->sortable(),
            Text::make('IP адрес', 'ip')->sortable(),
        ];
    }


    public function detailFields(): iterable
    {
        return [
            Text::make('Серийный номер', 'serial_number'),
            Text::make('IP адрес', 'ip'),
        ];
    }

    public function search(): array
    {
        return ['serial_number', 'ip'];
    }

    public function filters(): iterable
    {
        return [
            Text::make('Серийный номер', 'serial_number'),
            Text::make('IP адрес', 'ip'),
        ];
    }
}
