<?php

namespace App\MoonShine\Resources;

use App\Models\Setting;
use App\Models\Stream;
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
            CustomNumber::make('Время хранения архива(Час)', 'archive_time')
                ->default(24)
                ->integer("Время хранения архива должно быть числом")
                ->minValue(1, 'Время хранения архива не может быть меньше часа')
                ->maxValue(87600, 'Время хранения архива не может быть больше года')
                ->required(),
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
            'archive_time' => ['required','integer', 'min:0'],
            'rtsp' => ['string', 'max:255'],
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
