<?php

namespace App\MoonShine\Resources;

use App\Models\Setting;
use App\Models\Stream;
use App\MoonShine\Decorations\FeatureBox;
use App\MoonShine\Fields\ArchiveDuration;
use App\MoonShine\Fields\CustomNumber;
use App\MoonShine\Fields\CustomText;
use App\MoonShine\Fields\ToggleField;
use App\MoonShine\Fields\FeatureCheckbox;
use App\MoonShine\Fields\FeatureField;
use App\MoonShine\Fields\FeatureSlider;
use App\MoonShine\Fields\FeatureSpoiler;
use App\MoonShine\Fields\SingleZonePreviewField;
use App\MoonShine\Fields\MultiZonePreviewField;
use App\MoonShine\Pages\SettingsPage;
use App\MoonShine\Pages\StreamLprZoneEditorPage;
use App\MoonShine\Pages\StreamPlayer;
use App\MoonShine\Pages\VideoStreamIndexPage;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use App\Services\VideoAnalyticService;
use Illuminate\Support\Facades\Http;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use App\MoonShine\Pages\StreamFaceZoneEditorPage;
use App\MoonShine\Pages\StreamMotionZonesEditorPage;
use MoonShine\Laravel\Http\Responses\MoonShineJsonResponse;
use MoonShine\Laravel\MoonShineRequest;
use MoonShine\Support\Enums\ToastType;
use MoonShine\Support\ListOf;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Divider;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Preview;
use Closure;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Checkbox;
use Log;
use Exception;

class VideoStreamResource extends BaseModelResource
{
    protected string $model = Stream::class;
    protected string $title = 'Видеопотоки';

    public function indexSubtitle(): ?string
    {
        return $this->countSubtitle('видеопоток', 'видеопотока', 'видеопотоков');
    }
    protected ?string $editModalTitle = 'Редактирование видеопотока';
    protected string $column = 'name';
    protected bool $softDelete = false;

    public function getRedirectAfterDelete(): string
    {
        return route('moonshine.resource.page', ['video-stream-resource', 'video-stream-index-page']);
    }

    public function getRedirectAfterSave(): string
    {
        return route('moonshine.resource.page', ['video-stream-resource', 'video-stream-index-page']);
    }

    protected function indexButtons(): ListOf
    {
        return parent::indexButtons()->prepend(
            ActionButton::make('', fn($item) => route('moonshine.resource.page', ['video-stream-resource', 'stream-player', $item->id]))
                ->class('js-player-button')
                ->customView('components.action-icon-button', ['variant' => 'play'])
        );
    }

    public function modifyFormComponent(ComponentContract $component): ComponentContract
    {
        $component = parent::modifyFormComponent($component);
        $item = $this->getItem();
        if ($component instanceof FormBuilderContract && $item?->exists) {
            $component->customAttributes([
                'data-sidebar-title' => (string) $item->name,
                'data-sidebar-subtitle' => 'Настройки камеры',
            ]);
        }

        return $component;
    }

    public function pages(): array
    {
        return [
            VideoStreamIndexPage::class,
            DetailPage::class,
            FormPage::class,
            StreamPlayer::class,
            StreamFaceZoneEditorPage::class,
            StreamMotionZonesEditorPage::class,
            StreamLprZoneEditorPage::class,
        ];
    }

    public function deleteConfirmation(\Illuminate\Database\Eloquent\Model $item): array
    {
        return [
            'title' => 'Удалить камеру?',
            'text'  => '«' . ($item->name ?: 'Камера') . '» будет снята с мониторинга. Архив записей сохранится 30 дней.',
        ];
    }

    public function indexFields(): array
    {
        return [
            Text::make('UID', 'uid'),
            Text::make('Название', 'name'),
            Preview::make('Локация', 'location')
                ->changeFill(fn($item) => $item->location ?: '<span style="color:#B7B7C9;">—</span>'),
            Preview::make('AI аналитика', 'va_options')
                ->changeFill(fn($item) => $this->renderAiChips($item)),
            Preview::make('Архив', 'archive_time')
                ->changeFill(fn($item) => $item->archive_time
                    ? e($item->archive_time) . ' ч'
                    : '<span style="color:#B7B7C9;">—</span>'),
            Preview::make('Статус', 'is_active')
                ->changeFill(fn($item) => $this->renderStatusBadge($item)),
        ];
    }

    public function search(): array
    {
        return [
            'uid',
            'name',
            'location',
        ];
    }

    private function renderAiChips($item): string
    {
        $o = $item->va_options ?? [];
        if (empty($o['global_enable'])) {
            return '<span style="color:#B7B7C9;">—</span>';
        }

        $chip = fn(string $text, string $bg, string $color): string =>
            '<span style="display:inline-flex;align-items:center;padding:2px 10px;border-radius:9999px;font-size:12px;font-weight:600;background:' . $bg . ';color:' . $color . ';">' . $text . '</span>';

        $chips = [];
        if (!empty($o['is_face_detection']) || !empty($o['is_face_recognition'])) {
            $chips[] = $chip('Лица', '#EAF2FF', '#3B82F6');
        }
        if (!empty($o['is_motion_detection'])) {
            $chips[] = $chip('Движение', '#FEF3E7', '#F59E0B');
        }
        if (!empty($o['is_plate_recognition'])) {
            $chips[] = $chip('ГРЗ', '#EAF7EE', '#22C55E');
        }

        if (empty($chips)) {
            return '<span style="color:#B7B7C9;">—</span>';
        }

        return '<div style="display:flex;gap:6px;flex-wrap:wrap;">' . implode('', $chips) . '</div>';
    }

    private function renderStatusBadge($item): string
    {
        $active = '<span style="display:inline-flex;align-items:center;gap:6px;padding:3px 12px;border-radius:9999px;font-size:12px;font-weight:600;background:#E7F7EE;color:#1FA463;"><span style="width:6px;height:6px;border-radius:50%;background:#1FA463;"></span>Активна</span>';
        $error = '<span style="display:inline-flex;align-items:center;gap:6px;padding:3px 12px;border-radius:9999px;font-size:12px;font-weight:600;background:#FDECEC;color:#E5484D;"><span style="width:6px;height:6px;border-radius:50%;background:#E5484D;"></span>Не отвечает</span>';

        if (empty($item->is_active)) {
            return $error;
        }

        $uid = e($item->uid);

        return '<div x-data="{ ok:false, done:false, _t:null, '
            . 'probe(){ let img=new Image(); img.onload=()=>{this.ok=true;this.done=true}; img.onerror=()=>{this.ok=false;this.done=true}; img.src=\'/media/' . $uid . '/\'+Math.floor(Date.now()/1000)+\'.jpg\'; } }" '
            . 'x-init="probe(); _t=setInterval(() => probe(), 15000)" '
            . 'x-on:destroy="clearInterval(_t)">'
            . '<span x-show="ok" x-cloak>' . $active . '</span>'
            . '<span x-show="done && !ok" x-cloak>' . $error . '</span>'
            . '<span x-show="!done" style="color:#B7B7C9;font-size:12px;">проверка…</span>'
            . '</div>';
    }

    public function trAttributes(): ?Closure
    {
        return function (mixed $item, int $index): array {
            return [
                '@click' => "if(\$event.target.closest('a,button,input,label')) return; \$event.currentTarget.querySelector('.js-player-button')?.click();",
                'style' => 'cursor: pointer;',
            ];
        };
    }

    public function formFields(): iterable
    {
        $locationIcon = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 21s7-5.686 7-11a7 7 0 1 0-14 0c0 5.314 7 11 7 11z" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.6"/></svg>';

        return [
            ID::make('ID', 'id'),
            Grid::make([
                Column::make([
                    CustomText::make('Название', 'name')
                        ->required()
                        ->unique('streams', 'name'),
                ])->columnSpan(12),
                Column::make([
                    CustomText::make('Локация', 'location')
                        ->icon($locationIcon)
                        ->nullable(),
                ])->columnSpan(6),
                Column::make([
                    ArchiveDuration::make('Длина архива', 'archive_time'),
                ])->columnSpan(6),
                Column::make([
                    CustomText::make('Адрес потока(RTSP)', 'rtsp')->required()
                        ->pattern('^rtsp:\/\/(([^\s\/:@]+)(:([^\s\/@]+))?@)?([^\s\/:]+)(?::([0-9]+))?(\/.*)?$'),
                ])->columnSpan(12),
            ]),
            ToggleField::make('Видеопоток включён', 'is_active')
                ->subtitle('Запись и трансляция активны'),
            ...config('services.va.enabled')?[
                FeatureField::make('AI-аналитика', 'va_options->global_enable')
                    ->description('Распознавание лиц, движения и ГРЗ')
                    ->offValue(0)
                    ->onValue(1)
                    ->locked(!(boolean)Setting::where('key', 'face_recognition')->value('value'))
                    ->unlockUrl(app(SettingsPage::class)->getUrl()),
                FeatureBox::make('Настройка AI аналитики')
                    ->icon(file_get_contents(public_path('icons/icon-feature.svg')))
                    ->showWhen('va_options->global_enable', '=', true)
                    ->fields([
                        FeatureSpoiler::make('Поиск лица', 'va_options->is_face_detection')
                            ->nested([
                                FeatureCheckbox::make('Задать зону поиска лица', 'va_options->has_face_detection_zone'),
                                SingleZonePreviewField::make('Превью зоны', 'va_options->face_detection_zone')
                                    ->setupUrl($this->getPageUrl(StreamFaceZoneEditorPage::class, ['resourceItem' => $this->getItem()?->getKey()]))
                                    ->showWhen('va_options->has_face_detection_zone', '=', true),
                                FeatureCheckbox::make('Распознавание персоны', 'va_options->is_face_recognition'),
                                FeatureSlider::make('Чувствительность', 'va_options->face_recognition_sensitivity')
                                    ->showWhen('va_options->is_face_recognition', '=', true)
                                    ->default(75)
                                    ->min(0)
                                    ->max(100)
                                    ->step(1)
                                    ->wrapperStyle('padding-left: 30px'),
                            ]),
                        Divider::make(),
                        FeatureSpoiler::make('Детекция движения', 'va_options->is_motion_detection')
                            ->nested([
                                FeatureCheckbox::make('Детекция человека', 'va_options->is_human_motion_detection'),
                                FeatureCheckbox::make('Задать зону детекции движения', 'va_options->has_motion_detection_zone'),
                                MultiZonePreviewField::make('Превью зон', 'va_options->motion_detection_zones')
                                    ->colorLines('#ef4444')
                                    ->colorRectangles('#ef4444')
                                    ->setupUrl($this->getPageUrl(StreamMotionZonesEditorPage::class, ['resourceItem' => $this->getItem()?->getKey()]))
                                    ->showWhen('va_options->has_motion_detection_zone', '=', true),
                            ]),
                        ...config('services.lpr.enabled')?[
                            Divider::make(),
                            FeatureSpoiler::make('Распознавание ГРЗ', 'va_options->is_plate_recognition')
                                ->nested([
                                    FeatureCheckbox::make('Задать зону распознавания ГРЗ', 'va_options->has_plate_recognition_zone'),
                                    SingleZonePreviewField::make('Превью зоны', 'va_options->plate_recognition_zone')
                                        ->setupUrl($this->getPageUrl(StreamLprZoneEditorPage::class, ['resourceItem' => $this->getItem()?->getKey()]))
                                        ->showWhen('va_options->has_plate_recognition_zone', '=', true),
                                    FeatureSlider::make('Чувствительность', 'va_options->plate_recognition_sensitivity')
                                        ->default(75)
                                        ->min(0)
                                        ->max(100)
                                        ->step(1),
                                ]),
                            ]:[]
                    ]),
            ] : [],
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


    public function saveFaceDetectionZone(MoonShineRequest $request): MoonShineJsonResponse
    {
        $item = $this->getItem();

        $zoneData = $request->input('va_options->face_detection_zone');

        if (is_string($zoneData)) {
            $zoneData = json_decode($zoneData, true);
        }

        $options = $item->va_options ?? [];
        $options['global_enable'] = true;
        $options['is_face_detection'] = true;
        $options['has_face_detection_zone'] = true;
        $options['face_detection_zone'] = $zoneData;

        $videoWidth = (int)$request->input('video_width', 0);
        $videoHeight = (int)$request->input('video_height', 0);
        if ($videoWidth > 0 && $videoHeight > 0) {
            $options['video_width'] = $videoWidth;
            $options['video_height'] = $videoHeight;
        }

        $item->va_options = $options;
        $item->save();

        return MoonShineJsonResponse::make()
            ->toast('Зона поиска сохранена!', ToastType::SUCCESS)
            ->redirect($this->getIndexPageUrl());
    }

    public function saveMotionDetectionZones(MoonShineRequest $request): MoonShineJsonResponse
    {
        $item = $this->getItem();
        $zoneData = $request->input('va_options->motion_detection_zones');
        if (is_string($zoneData)) {
            $zoneData = json_decode($zoneData, true);
        }
        $options = $item->va_options ?? [];
        $options['global_enable'] = true;
        $options['is_motion_detection'] = true;
        $options['has_motion_detection_zones'] = true;
        $options['motion_detection_zones'] = $zoneData;

        $videoWidth = (int)$request->input('video_width', 0);
        $videoHeight = (int)$request->input('video_height', 0);
        if ($videoWidth > 0 && $videoHeight > 0) {
            $options['video_width'] = $videoWidth;
            $options['video_height'] = $videoHeight;
        }

        $item->va_options = $options;
        $item->save();

        return MoonShineJsonResponse::make()
            ->toast('Зоны детекции движения сохранены!', ToastType::SUCCESS)
            ->redirect($this->getIndexPageUrl());
    }

    public function savePlateDetectionZone(MoonShineRequest $request): MoonShineJsonResponse
    {
        $item = $this->getItem();

        $zoneData = $request->input('va_options->plate_recognition_zone');
        if (is_string($zoneData)) {
            $zoneData = json_decode($zoneData, true);
        }

        $options = $item->va_options ?? [];
        $options['global_enable'] = true;
        $options['is_plate_recognition'] = true;
        $options['has_plate_recognition_zone'] = true;
        $options['plate_recognition_zone'] = $zoneData;

        $videoWidth = (int)$request->input('video_width', 0);
        $videoHeight = (int)$request->input('video_height', 0);
        if ($videoWidth > 0 && $videoHeight > 0) {
            $options['video_width'] = $videoWidth;
            $options['video_height'] = $videoHeight;
        }

        $item->va_options = $options;
        $item->save();

        return MoonShineJsonResponse::make()
            ->toast('Зона распознавания ГРЗ сохранена!', ToastType::SUCCESS)
            ->redirect($this->getIndexPageUrl());
    }
}
