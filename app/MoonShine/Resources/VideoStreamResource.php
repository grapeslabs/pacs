<?php

namespace App\MoonShine\Resources;

use App\Models\Setting;
use App\Models\Stream;
use App\MoonShine\Fields\ArchiveDuration;
use App\MoonShine\Fields\CustomNumber;
use App\MoonShine\Fields\CustomText;
use App\MoonShine\Fields\FeatureField;
use App\MoonShine\Pages\SettingsPage;
use App\MoonShine\Pages\StreamPlayer;
use App\MoonShine\Pages\Streams;
use App\Services\VideoAnalyticService;
use Illuminate\Support\Facades\Http;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Checkbox;
use Log;
use Exception;

class VideoStreamResource extends BaseModelResource
{
    protected string $model = Stream::class;
    protected string $title = 'Видеопотоки';
    protected string $column = 'name';

    public function getRedirectAfterDelete(): string
    {
        return route('moonshine.resource.page', ['video-stream-resource', 'custom-index-page']);
    }

    public function getRedirectAfterSave(): string
    {
        return route('moonshine.resource.page', ['video-stream-resource', 'custom-index-page']);
    }

    protected function indexButtons(): ListOf
    {
        return parent::indexButtons()->prepend(
            ActionButton::make('', fn($item) => route('moonshine.resource.page',['video-stream-resource','stream-player', $item->id]))
                ->icon(file_get_contents(public_path('icons/btn-fullscreen.svg')), true)
                ->class('btn js-player-button')
        );
    }

    public function pages(): array
    {
        $pages = parent::pages();
        $pages[] = Streams::class;
        $pages[] = StreamPlayer::class;
        return $pages;
    }

    public function indexFields(): array
    {
        return [
            ID::make('ID', 'id'),
            Text::make('UID', 'uid'),
            Text::make('ID хранилища', 'storage_id'),
            Checkbox::make('Включено', 'is_active'),
            Text::make('Название', 'name'),
            Text::make('Локация', 'location'),
            Text::make('Адрес потока(RTSP)', 'rtsp'),
            Number::make('Время хранения архива(Час)', 'archive_time'),
            Checkbox::make('Распознавание личности', 'is_recognize'),
        ];
    }

    public function formFields(): iterable
    {
        return [
            ID::make('ID', 'id'),
            Checkbox::make('Включено', 'is_active'),
            CustomText::make('Название', 'name')
                ->required()
                ->unique('streams', 'name'),
            CustomText::make('Локация', 'location')->nullable(),
            CustomText::make('Адрес потока(RTSP)', 'rtsp')->required()
                ->pattern('^rtsp:\/\/([^\s\/:]+)(?::([0-9]+))?(\/.*)?$'),
            ArchiveDuration::make('Архив', 'archive_time'),
            ...config('services.va.enabled')?[
                FeatureField::make('Распознание личности', 'is_recognize')
                    ->locked(!Setting::where('key', 'face_recognition')->value('value'))
                    ->unlockUrl(app(SettingsPage::class)->getUrl()),
            ]:[],
        ];
    }

    public function detailFields(): iterable
    {
        return [
            ID::make('ID', 'id'),
            Text::make('UID', 'uid'),
            Text::make('ID хранилища', 'storage_id'),
            Checkbox::make('Включено', 'is_active'),
            Text::make('Название', 'name'),
            Text::make('Локация', 'location'),
            Text::make('Адрес потока(RTSP)', 'rtsp'),
            Text::make('Время хранения архива(Час)', 'archive_time'),
            ...config('services.va.enabled')?[
                Checkbox::make('Распознание личности', 'is_recognize'),
            ]:[],
        ];
    }

    public function rules($item): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable','string', 'max:255'],
            'archive_time' => ['required', 'array'],
            'archive_time.value' => ['bail', 'required', 'integer', 'min:0', 'max:100'],
            'archive_time.unit' => ['bail', 'required', 'integer'],
            'rtsp' => ['string', 'max:255'],
        ];
    }

    public function validationMessages(): array
    {
        return [
            'name.required' => 'Поле «Название» обязательно для заполнения.',
            'name.string'   => 'Название должно быть строкой.',
            'name.max'      => 'Название не может превышать 255 символов.',

            'location.string' => 'Местоположение должно быть строкой.',
            'location.max'    => 'Местоположение не может превышать 255 символов.',

            'archive_time.required' => 'Параметры времени архивации обязательны.',
            'archive_time.value.required' => 'Значение времени архивации обязательно.',
            'archive_time.value.integer'  => 'Значение времени архивации должно быть целым числом.',
            'archive_time.value.min'      => 'Значение времени архивации не может быть меньше 0.',
            'archive_time.value.max' => 'Максимальное значение - 100. Для больших интервалов измените единицу времени.',

            'archive_time.unit.required' => 'Единица измерения времени архивации обязательна.',
            'archive_time.unit.integer'  => 'Единица измерения должна быть указана корректно.',

            'rtsp.string' => 'RTSP-ссылка должна быть строкой.',
            'rtsp.max'    => 'RTSP-ссылка не может превышать 255 символов.',
        ];
    }

    public function filters(): iterable
    {
        return [
            Checkbox::make('Включено', 'is_active'),
            Text::make('Название', 'name'),
            Text::make('Локация', 'location'),
            ...config('services.va.enabled')?[
                Checkbox::make('Распознание личности', 'is_recognize'),
            ]:[],
        ];
    }

    public function search():array
    {
        return ['name','location', 'uid', 'rtsp', 'storage_id'];
    }
}
