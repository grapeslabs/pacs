<?php

namespace App\MoonShine\Resources;

use App\Models\Person;
use App\Models\Stream;
use App\Models\VideoAnalyticReport;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Support\Enums\SortDirection;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Image;

class PeopleReportResource extends BaseModelResource
{
    protected string $model = VideoAnalyticReport::class;
    protected string $title = 'Отчеты по персонам';
    protected string $column = 'name';
    protected string $sortColumn='id';
    protected SortDirection $sortDirection = SortDirection::DESC;
    protected int $oldPerson;

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->where('person_photobank_id', '<>', '');
    }

    protected function activeActions(): ListOf
    {
        return  parent::activeActions()->except(Action::UPDATE, Action::CREATE, Action::VIEW, Action::DELETE, Action::MASS_DELETE);
    }

    public function indexFields(): iterable
    {

        return [
            text::make('Персона', 'person_photobank_id', function($item) {
                if(empty($item->person_photobank_id)) { return 'Неизвестная персона'; }
                if(!empty($this->oldPerson) && $this->oldPerson == $item->person_photobank_id) {
                    return "";
                }
                return "[$item->person_photobank_id] " . Person::find($item->person_photobank_id)?->getfullname();
            })->sortable(),
            Date::make('Дата рождения', '', function($item) {
                if(empty($item->person_photobank_id)) { return 'Данные не найдены'; }
                if(!empty($this->oldPerson) && $this->oldPerson == $item->person_photobank_id) {
                    return "";
                }
                return person::find($item->person_photobank_id)?->birth_date;
            })
                ->format('d.m.Y'),
            Text::make('Теги', '', function($item) {
                if(empty($item->person_photobank_id)) { return null; }
                if(!empty($this->oldPerson) && $this->oldPerson == $item->person_photobank_id) {
                    return "";
                }
                $this->oldPerson = $item->person_photobank_id;
                return person::find($item->person_photobank_id)?->tags_list;
            }),

            Date::make('Дата и время', 'datetime')
                ->withTime()
                ->sortable(),
            Text::make('Камера', '', fn ($item) => Stream::where('uid', $item->camera_id)->first()->name??'Неизвестная камера')
                ->sortable()
                ->sortable(),
            Image::make('Фото', '', fn($item)=>basename($item->data['snapshot_path']))
                ->disk('analytic')
                ->dir('thumbnails')
                ->setLabel('Фото'),
        ];
    }

    public function search(): array
    {
        return [];
    }
    public function filters(): array
    {
        return [
            DateRange::make('Период отчётности',  'datetime')
                ->withTime(),
            Select::make('Видеопоток', 'camera_id')
                ->placeholder('Видеопоток')
                ->options(Stream::query()->pluck('name', 'uid')->toArray())
                ->nullable()
                ->searchable(),
            Select::make('Персона', 'person_photobank_id')
                ->placeholder('Персона')
                ->options(Person::query()->pluck('last_name', 'id')->toArray())
                ->nullable()
                ->searchable()
        ];
    }

}
