<?php

namespace App\MoonShine\Resources;

use App\Models\Organization;
use App\MoonShine\Fields\CountField;
use Illuminate\Database\Eloquent\Model;
use App\MoonShine\Fields\AvatarField;
use App\MoonShine\Fields\CustomText;
use App\MoonShine\Fields\OrganizationField;
use App\MoonShine\Pages\CustomIndexPage;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use App\MoonShine\Fields\CustomTextarea;

class OrganizationResource extends BaseModelResource
{
    protected string $model = Organization::class;

    protected string $title = 'Организации';

    public function indexSubtitle(): ?string
    {
        return $this->countSubtitle('организация', 'организации', 'организаций');
    }
    protected ?string $editModalTitle = 'Редактирование организации';
    protected bool $detailSidebar = true;
    protected ?string $createLabel = 'Добавить организацию';

    protected function pages(): array
    {
        return [
            CustomIndexPage::class,
            DetailPage::class,
            FormPage::class,
        ];
    }

    public function detailSidebarData(Model $item): array
    {
        $peopleCount = $item->people()->count();

        $icDoc = @file_get_contents(public_path('icons/doc.svg'))?:'';
        $icPhone = @file_get_contents(public_path('icons/phone.svg'))?:'';
        $icPerson = @file_get_contents(public_path('icons/person.svg'))?:'';
        $orgIcon = @file_get_contents(public_path('icons/organization.svg'))?:'';

        $personsUrl = $this->filteredIndexUrl(app(PersonResource::class), ['organization_id' => $item->getKey()]);

        $badges = [];
        if ($peopleCount > 0) {
            $badges[] = ['text' => $peopleCount . ' ' . $this->pluralPeople($peopleCount), 'icon' => $icPerson, 'url' => $personsUrl];
        }

        $sections = [
            [
                'icon' => $icDoc,
                'title' => 'Реквизиты',
                'fields' => [
                    ['label' => 'ИНН', 'value' => $item->inn],
                    ['label' => 'КПП', 'value' => $item->kpp],
                    ['label' => 'ОГРН / ОГРНИП', 'value' => $item->ogrn, 'full' => true],
                    ['label' => 'Полное название', 'value' => $item->full_name, 'full' => true],
                    ['label' => 'Юридический адрес', 'value' => $item->address, 'full' => true],
                    ['label' => 'Фактический адрес', 'value' => $item->actual_address, 'full' => true],
                ],
            ],
        ];

        $contactFields = array_values(array_filter([
            $item->contact_name ? ['label' => 'ФИО', 'value' => $item->contact_name] : null,
            $item->contact_position ? ['label' => 'Должность', 'value' => $item->contact_position] : null,
            $item->phone ? ['label' => 'Телефон', 'value' => $item->phone] : null,
        ]));

        if ($contactFields !== []) {
            $sections[] = [
                'icon' => $icPhone,
                'title' => 'Контактное лицо',
                'fields' => $contactFields,
            ];
        } elseif (! blank($item->contact_data)) {
            $sections[] = [
                'icon' => $icPhone,
                'title' => 'Контактные данные',
                'text' => $item->contact_data,
            ];
        }

        if (! blank($item->comment)) {
            $sections[] = [
                'icon' => $this->commentIcon(),
                'title' => 'Комментарий',
                'text' => $item->comment,
            ];
        }

        return [
            'title' => 'Карточка организации',
            'subtitle' => $item->inn ? 'ИНН ' . $item->inn : null,
            'hero' => [
                'title' => $item->short_name,
                'subtitle' => $item->full_name,
                'icon' => $orgIcon,
                'badges' => $badges,
            ],
            'sections' => $sections,
            'footer' => [
                ['spacer' => true],
                ['html' => $this->detailEditButtonHtml($item)],
            ],
        ];
    }

    private function pluralPeople(int $n): string
    {
        $m10 = $n % 10;
        $m100 = $n % 100;
        if ($m10 === 1 && $m100 !== 11) {
            return 'персона';
        }
        if ($m10 >= 2 && $m10 <= 4 && ! ($m100 >= 12 && $m100 <= 14)) {
            return 'персоны';
        }

        return 'персон';
    }

    public function formFields(): iterable
    {
        $locationIcon = file_get_contents(public_path('icons/location.svg'));
        $phoneIcon = file_get_contents(public_path('icons/phone.svg'));

        return [
            OrganizationField::make('Поиск организации', 'search_query')
                ->withoutWrapper(),
            Grid::make([
                Column::make([
                    CustomText::make('ИНН', 'inn')
                        ->pattern('/^(\d{10}|\d{12})$/', 'ИНН должен содержать 10 или 12 цифр')
                        ->mask('999999999999')
                        ->unique('organizations', 'inn')
                        ->hint('10 или 12 цифр'),
                ])->columnSpan(6),
                Column::make([
                    CustomText::make('КПП', 'kpp')
                        ->mask('999999999')
                        ->hint('9 цифр (юрлица)'),
                ])->columnSpan(6),
            ]),
            CustomText::make('ОГРН / ОГРНИП', 'ogrn')
                ->mask('999999999999999')
                ->hint('13 или 15 цифр'),
            CustomText::make('Полное название', 'full_name')->required(),
            CustomText::make('Сокращённое название', 'short_name')->required(),
            CustomText::make('Юридический адрес', 'address')
                ->leadingIcon($locationIcon),
            CustomText::make('Фактический адрес', 'actual_address')
                ->leadingIcon($locationIcon)
                ->hint('Если отличается от юридического'),
            Grid::make([
                Column::make([
                    CustomText::make('Должность', 'contact_position'),
                ])->columnSpan(6),
                Column::make([
                    CustomText::make('ФИО', 'contact_name'),
                ])->columnSpan(6),
            ]),
            CustomText::make('Телефон', 'phone')
                ->phone()
                ->leadingIcon($phoneIcon),
            CustomTextarea::make('Комментарий', 'comment'),
        ];
    }

    public function deleteConfirmation(\Illuminate\Database\Eloquent\Model $item): array
    {
        return [
            'title' => 'Удалить организацию?',
            'text'  => '«' . ($item->short_name ?: 'Организация') . '» будет удалена. Персоны останутся, но потеряют привязку.',
        ];
    }

    public function indexFields(): iterable
    {
        return [
            AvatarField::make('Организация', 'short_name')
                ->text('short_name')
                ->subtext('full_name')
                ->icon(file_get_contents(public_path('icons/organization.svg')), true)
                ->sortable(),
            Text::make('ИНН', 'inn')->sortable(),
            Text::make('Адрес', 'address')->sortable(),
            CountField::make('Персон', 'id', fn($item) => $item->people()->count())
                ->sortable(),
        ];
    }

    public function rules($item): array
    {
        return [
            'full_name' => ['required', 'string'],
            'short_name' => ['required', 'string'],
            'inn' => ['nullable', 'string'],
            'kpp' => ['nullable', 'string'],
            'ogrn' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'actual_address' => ['nullable', 'string'],
            'contact_position' => ['nullable', 'string'],
            'contact_name' => ['nullable', 'string'],
            'phone' => ['nullable', 'string'],
            'contact_data' => ['nullable', 'string'],
        ];
    }

    public function search(): array
    {
        return [
            'id',
            'inn',
            'full_name',
            'short_name',
            'comment',
            'address',
            'contact_data',
        ];
    }

    public function filters(): array
    {
        return [
            Text::make('ИНН', 'inn')
                ->placeholder('Фильтрация по ИНН'),

            Text::make('Название полное', 'full_name')
                ->placeholder('Фильтрация по полному названию'),

            Text::make('Название сокращенное', 'short_name')
                ->placeholder('Фильтрация по сокращенному названию'),

            Text::make('Адрес', 'address')
                ->placeholder('Фильтрация по адресу'),

            Text::make('Контактные данные', 'contact_data')
                ->placeholder('Фильтрация по контактным данным'),

            CustomTextarea::make('Комментарий', 'comment')
                ->placeholder('Фильтрация по комментарию'),
        ];
    }
}
