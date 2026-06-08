<?php

namespace App\MoonShine\Resources;

use App\Models\Person;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\Stream;
use App\Models\VideoAnalyticReport;
use App\MoonShine\Fields\CustomDate;
use App\MoonShine\Fields\CustomText;
use App\MoonShine\Fields\PhotoField;
use App\MoonShine\Fields\SelectField;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\ImportExport\Contracts\HasImportExportContract;
use MoonShine\ImportExport\Traits\ImportExportConcern;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Laravel\Handlers\Handler;
use Illuminate\Support\Facades\Storage;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\MoonShineRequest;
use MoonShine\Laravel\Http\Responses\MoonShineJsonResponse;
use MoonShine\Support\Enums\SortDirection;
use MoonShine\Support\Enums\ToastType;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Fields\Hidden;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use App\MoonShine\Fields\CustomTextarea;

class UnknownReportResource extends BaseModelResource
{
    protected string $model = VideoAnalyticReport::class;
    protected string $title = 'Отчеты по неизвестным';
    protected string $column = 'name';
    protected string $sortColumn = 'id';
    protected SortDirection $sortDirection = SortDirection::DESC;
    protected ?string $oldPerson = null;

    protected function modifyQueryBuilder(Builder $builder): Builder
    {
        return $builder->where('is_unknown', true);
    }

    public function query(): Builder
    {
        return parent::query()->with(['stream']);
    }

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->except(Action::UPDATE, Action::CREATE, Action::VIEW, Action::DELETE, Action::MASS_DELETE);
    }

    public function indexFields(): iterable
    {
        return [
            Text::make('Персона', 'person_photobank_id', function($item) {
                if(!empty($this->oldPerson) && $this->oldPerson == $item->person_photobank_id) {
                    return "";
                }
                return $this->oldPerson = $item->person_photobank_id;
            })->sortable(),

            Date::make('Дата и время', 'datetime')
                ->withTime()
                ->sortable(),

            BelongsTo::make('Камера', 'stream', fn($item) => $item->name ?? 'Камера удалена', VideoStreamResource::class)
                ->sortable(),

            Image::make('Фото', 'snapshot_path_display', fn($item) => basename($item->data['snapshot_path'] ?? ''))
                ->disk('analytic')
                ->dir('thumbnails')
        ];
    }

    protected function indexButtons(): ListOf
    {
        return parent::indexButtons()->prepend($this->getIdentificationButton());
    }

    protected function getIdentificationButton(): ActionButton
    {
        return ActionButton::make(
            '',
            fn($item) => $this->getAsyncMethodUrl(
                method: 'getIdentifyForm',
                params: [
                    'item_id' => $item->getKey(),
                    '_component_name' => $this->getListComponentName(),
                    '_async_form' => true,
                ]
            )
        )
            ->icon('pencil')
            ->class('js-identify-button')
            ->customAttributes([
                '@click.prevent' => "\$dispatch('modal-toggled', { id: '{$this->safeModalName}', title: 'Идентификация персоны' })",
            ])
            ->async(selector: "#{$this->safeModalName}_content");
    }


    public function getIdentifyForm(MoonShineRequest $request): string
    {
        $itemId = $request->get('item_id');
        $item = VideoAnalyticReport::find($itemId);
        if (!$item) {
            return "Запись #{$itemId} не найдена в базе данных";
        }

        return $this->identifyFormBuilder($item)->render();
    }

    protected function identifyFormBuilder(VideoAnalyticReport $item): FormBuilder
    {
        $photoPath = $item->data['snapshot_path'] ?? null;
        $photoValue = $photoPath ? ['thumbnails/' . basename($photoPath)] : [];

        return FormBuilder::make()
            ->action($this->getAsyncMethodUrl('savePerson', null, ['item_id' => $item->getKey()]))
            ->async()
            ->fill(['photo' => $photoValue])
            ->fields([
                Hidden::make('grapesva_uuid')->default($item->person_photobank_id),
                Grid::make([
                    Column::make([
                        CustomText::make('Фамилия', 'last_name')
                            ->min(2,'Минимум 2 символа')
                            ->max(50, 'Максимум 50 символов')
                            ->pattern('/^[А-Яа-яA-Za-zЁё\s\-]+$/u', 'Допустимы только буквы, пробел и дефис')
                            ->nameFormat('Фамилия должна содержать буквы')
                            ->placeholder('Иванов')
                            ->required(),
                    ])->columnSpan(6),
                    Column::make([
                        CustomText::make('Имя', 'first_name')
                            ->min(2,'Минимум 2 символа')
                            ->max(50, 'Максимум 50 символов')
                            ->pattern('/^[А-Яа-яA-Za-zЁё\s\-]+$/u', 'Допустимы только буквы, пробел и дефис')
                            ->nameFormat()
                            ->required()
                            ->placeholder('Иван'),
                    ])->columnSpan(6),
                ]),
                Grid::make([
                    Column::make([
                        CustomText::make('Отчество', 'middle_name')
                            ->min(2,'Минимум 2 символа')
                            ->max(50, 'Максимум 50 символов')
                            ->pattern('/^[А-Яа-яA-Za-zЁё\s\-]+$/u', 'Допустимы только буквы, пробел и дефис')
                            ->nameFormat('Отчество должно содержать буквы')
                            ->placeholder('Иванович'),
                    ])->columnSpan(6),
                    Column::make([
                        CustomDate::make('Дата рождения', 'birth_date')
                            ->before(Carbon::now(), 'Дата рождения не может быть будущим')
                            ->after(Carbon::now()->subYears(120), 'Дата рождения не может быть более 120 лет назад')
                            ->format('d.m.Y')
                            ->sortable(),
                    ])->columnSpan(6),
                ]),
                Grid::make([
                    Column::make([
                        CustomText::make('Номер удостоверения', 'certificate_number')
                            ->unique('person', 'certificate_number', 'Номер удостоверения должен быть уникальным'),
                    ])->columnSpan(6),
                    Column::make([
                        SelectField::make('Организация', 'organization_id')
                            ->placeholder('Выберите организацию')
                            ->options(Organization::query()->get()->pluck('short_name', 'id')->toArray())
                            ->nullable(),
                    ])->columnSpan(6),
                ]),

                PhotoField::make('Фото', 'photo')
                    ->multiple()
                    ->disk('analytic')
                    ->dir('thumbnails')
                    ->allowedExtensions(['jpg', 'png', 'jpeg', 'gif']),
                SelectField::make('Теги', 'tags')
                    ->options(Tag::select('id', 'name')->get())
                    ->multiple(true)
                    ->creatable(true, route('tags.store')),
                CustomTextarea::make('Комментарий', 'comment'),
                Grid::make([
                    Column::make([
                        CustomDate::make('Заморозить с', 'frozen_start')
                            ->withTime()
                            ->nullable(),
                    ])->columnSpan(6),
                    Column::make([
                        CustomDate::make('Заморозить до', 'frozen_end')
                            ->withTime()
                            ->nullable()
                    ])->columnSpan(6)
                ])
            ])
            ->submit('Сохранить', ['class' => 'btn-primary']);
    }

    public function savePerson(MoonShineRequest $request)
    {
        $data = $request->all();
        $person = Person::create([
            'grapesva_uuid' => $data['grapesva_uuid'],
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'],
            'birth_date' => $data['birth_date'],
            'certificate_number' => $data['certificate_number'],
            'organization_id' => $data['organization_id'] ?? null,
            'comment' => $data['comment'] ?? null,
        ]);

        if ($request->has('tags')) {
            $person->tags()->sync($request->get('tags'));
        }

        $finalPhotos = [];

        if ($request->has('hidden_photo')) {
            foreach ($request->input('hidden_photo') as $filename) {
                $sourcePath = 'thumbnails/' . basename($filename);
                $newPath = 'person/photos/' . basename($filename);

                if (Storage::disk('analytic')->exists($sourcePath)) {
                    $content = Storage::disk('analytic')->get($sourcePath);
                    Storage::disk('public')->put($newPath, $content);
                    $finalPhotos[] = $newPath;
                }
                elseif (Storage::disk('public')->exists($newPath)) {
                    $finalPhotos[] = $newPath;
                }
            }
        }

        if ($request->hasFile('photo')) {
            $files = $request->file('photo');
            $files = is_array($files) ? $files : [$files];

            foreach ($files as $file) {
                $path = $file->store('person/photos', 'public');
                $finalPhotos[] = $path;
            }
        }

        $person->photo = $finalPhotos;
        $person->save();

        VideoAnalyticReport::query()->toBase()
            ->where('person_photobank_id', $person->grapesva_uuid)
            ->update(['is_unknown' => false]);

        return MoonShineJsonResponse::make()
            ->toast('Персона успешно идентифицирована!', ToastType::SUCCESS)
            ->redirect($this->getUrl());
    }

    public function search(): array
    {
        return [];
    }

    public function filters(): array
    {
        return [
            DateRange::make('Период отчётности',  'datetime')->withTime(),
            SelectField::make('Видеопоток', 'camera_id')
                ->options(Stream::query()->pluck('name', 'uid')->toArray())
                ->nullable(),
        ];
    }

    public function exportFields(): iterable
    {
        return [
            Text::make('Персона', 'data')
                ->changeFill(function($item) {
                    $data = is_string($item->data) ? json_decode($item->data, true) : $item->data;

                    return empty($data['unknown_uuid']) ? 'Неизвестная персона' : $data['unknown_uuid'];
                }),

            Date::make('Дата и время', 'datetime')
                ->format('d.m.Y H:i:s'),

            Text::make('Камера', 'camera_id')
                ->changeFill(function ($item) {
                    if (empty($item->camera_id)) return 'Неизвестная камера';
                    static $cameraCache = [];

                    if (!array_key_exists($item->camera_id, $cameraCache)) {
                        $cameraCache[$item->camera_id] = Stream::where('uid', $item->camera_id)->first()?->name;
                    }

                    return $cameraCache[$item->camera_id] ?? 'Неизвестная камера';
                }),

            Text::make('Имя файла фото', 'data')
                ->changeFill(function($item) {
                    $data = is_string($item->data) ? json_decode($item->data, true) : $item->data;

                    return !empty($data['snapshot_path']) ? basename($data['snapshot_path']) : '';
                }),
        ];
    }
}
