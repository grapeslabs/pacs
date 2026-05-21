<?php

namespace App\MoonShine\Resources;

use App\Models\Setting;
use App\Models\Stream;
use App\MoonShine\Decorations\FeatureBox;
use App\MoonShine\Fields\CustomNumber;
use App\MoonShine\Fields\CustomText;
use App\MoonShine\Fields\FeatureCheckbox;
use App\MoonShine\Fields\FeatureField;
use App\MoonShine\Fields\FeatureSlider;
use App\MoonShine\Fields\FeatureSpoiler;
use App\MoonShine\Fields\ZonePreviewField;
use App\MoonShine\Pages\SettingsPage;
use App\MoonShine\Pages\StreamPlayer;
use App\MoonShine\Pages\Streams;
use App\MoonShine\Pages\StreamZoneEditorPage;
use App\Services\VideoAnalyticService;
use Illuminate\Support\Facades\Http;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Laravel\Http\Responses\MoonShineJsonResponse;
use MoonShine\Laravel\MoonShineRequest;
use MoonShine\Support\Enums\ToastType;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Layout\Divider;
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
        $pages[] = StreamZoneEditorPage::class;
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
            CustomNumber::make('Время хранения архива(Час)', 'archive_time')
                ->default(24)
                ->integer("Время хранения архива должно быть числом")
                ->minValue(1, 'Время хранения архива не может быть меньше часа')
                ->maxValue(87600, 'Время хранения архива не может быть больше года')
                ->required(),
            ...config('services.va.enabled')?[
                FeatureBox::make('AI аналитика')
                    ->icon(file_get_contents(public_path('icons/icon-feature.svg')))
                    ->fields([
                        FeatureSpoiler::make('Поиск лица', 'va_options->is_face_detection')
                            ->nested([
                                FeatureCheckbox::make('Задать зону поиска лица', 'va_options->has_face_detection_zone'),
                                ZonePreviewField::make('Превью зоны', 'va_options->face_detection_zone')
                                    ->setupUrl($this->getPageUrl(StreamZoneEditorPage::class, ['resourceItem' => $this->getItem()?->getKey()]))
                                    ->showWhen('va_options->has_face_detection_zone', '=', true),
                                FeatureCheckbox::make('Рsаспознание персоны', 'va_options->is_face_recognition'),
                                FeatureSlider::make('Чувствительность', 'va_options->face_recognition_sensitivity')
                                    ->showWhen('va_options->is_face_recognition', '=', true)
                                    ->default(75)
                                    ->min(0)
                                    ->max(100)
                                    ->step(1),
                            ]),
                        Divider::make(),
                        FeatureSpoiler::make('Детекция движения', 'va_options->is_motion_detection')
                            ->nested([
                                FeatureCheckbox::make('Детекция человека', 'va_options->is_human_motion_detection'),
                                FeatureCheckbox::make('Задать зону детекции движения', 'va_options->has_human_detection_zone'),
                            ]),
                    ]),
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

    public function saveZone(MoonShineRequest $request): MoonShineJsonResponse
    {
        $item = $this->getItem();

        $zoneData = $request->input('va_options->face_detection_zone');

        if (is_string($zoneData)) {
            $zoneData = json_decode($zoneData, true);
        }

        $options = $item->va_options ?? [];
        $options['face_detection_zone'] = $zoneData;

        $videoWidth  = (int) $request->input('video_width', 0);
        $videoHeight = (int) $request->input('video_height', 0);
        if ($videoWidth > 0 && $videoHeight > 0) {
            $options['video_width']  = $videoWidth;
            $options['video_height'] = $videoHeight;
        }

        $item->va_options = $options;
        $item->save();

        return MoonShineJsonResponse::make()
            ->toast('Зона поиска сохранена!', ToastType::SUCCESS)
            ->redirect($this->getIndexPageUrl());
    }
}
