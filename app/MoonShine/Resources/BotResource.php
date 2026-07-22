<?php

namespace App\MoonShine\Resources;

use App\Models\Bot;
use App\MoonShine\Fields\BotChatsField;
use App\MoonShine\Fields\CustomText;
use App\MoonShine\Fields\SelectField;
use App\MoonShine\Pages\CustomIndexPage;
use App\MoonShine\Pages\BotFormPage;
use MoonShine\Contracts\Core\DependencyInjection\FieldsContract;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Url;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Support\ListOf;
use MoonShine\Laravel\Enums\Action;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\UI\Components\ActionButton;
use MoonShine\Laravel\Notifications\MoonShineNotification;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use MoonShine\Laravel\Http\Responses\MoonShineJsonResponse;
use MoonShine\Laravel\MoonShineRequest;
use App\Models\BotChat;

class BotResource extends BaseModelResource
{
    protected string $model = Bot::class;
    protected string $title = 'Боты';
    protected bool $detailSidebar = true;
    protected ?string $createLabel = 'Добавить бота';

    public function detailSidebarData(Model $item): array
    {
        $item->loadMissing('chats');

        $icBot = '<svg viewBox="0 0 24 24" fill="none"><rect x="4" y="8" width="16" height="11" rx="2.5" stroke="currentColor" stroke-width="1.6"/><path d="M12 8V4.5M9.5 13h.01M14.5 13h.01" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M2.5 12.5v3M21.5 12.5v3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';
        $icPlug = '<svg viewBox="0 0 24 24" fill="none"><path d="M9 3v5M15 3v5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M6 8h12v3a6 6 0 0 1-12 0z" stroke="currentColor" stroke-width="1.6"/><path d="M12 17v4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';
        $icUsers = '<svg viewBox="0 0 24 24" fill="none"><circle cx="9" cy="8" r="3.2" stroke="currentColor" stroke-width="1.6"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M16 5.2a3.2 3.2 0 0 1 0 5.6M17 13.5a5.5 5.5 0 0 1 3.5 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';
        $icBolt = '<svg viewBox="0 0 24 24" fill="none"><path d="M13 3 4 14h7l-1 7 9-11h-7z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>';

        $service = Bot::SERVICES[$item->service] ?? $item->service;

        $sections = [
            [
                'icon' => $icPlug,
                'title' => 'Параметры подключения',
                'single' => true,
                'fields' => [
                    ['label' => 'Сервис', 'value' => $service],
                    ['label' => 'Токен', 'value' => $this->maskToken((string) $item->token)],
                    ['label' => 'API URL', 'value' => blank($item->api_url) ? 'Стандартный' : $item->api_url],
                ],
            ],
        ];

        $recipients = $item->chats
            ->map(fn($c) => trim(($c->name ?: 'Чат') . ' · ' . $c->chat_id))
            ->all();
        if (! empty($recipients)) {
            $sections[] = ['icon' => $icUsers, 'title' => 'Получатели', 'tags' => $recipients];
        }

        $triggers = \App\Models\Trigger::query()->where('bot_id', $item->getKey())->get();
        if ($triggers->isNotEmpty()) {
            $triggerRes = app(TriggerResource::class);
            $sections[] = [
                'icon' => $icBolt,
                'title' => 'Связанные триггеры · ' . $triggers->count(),
                'items' => $triggers->map(fn($t) => [
                    'title' => $t->name,
                    'subtitle' => $this->eventLabel($t->event_type),
                    'actions' => [$this->detailChevronTo($triggerRes, $t->getKey())],
                ])->all(),
            ];
        }

        return [
            'title' => 'Карточка бота',
            'subtitle' => 'Бот · ' . $service,
            'hero' => [
                'title' => $item->name,
                'subtitle' => 'Бот · ' . $service,
                'icon' => $icBot,
            ],
            'sections' => $sections,
            'footer' => [
                ['spacer' => true],
                ['html' => $this->detailEditButtonHtml($item)],
            ],
        ];
    }

    private function maskToken(string $token): string
    {
        if (mb_strlen($token) <= 12) {
            return $token;
        }

        return mb_substr($token, 0, 6) . '••••••' . mb_substr($token, -4);
    }

    private function eventLabel(?string $type): string
    {
        return [
            'knowed' => 'Появление известного лица',
            'unknowed' => 'Появление неизвестного лица',
            'income' => 'Вход',
            'outcome' => 'Выход',
            'manual' => 'Открыто вручную',
            'denied' => 'Отказано',
        ][$type] ?? (string) $type;
    }

    protected function pages(): array
    {
        return [
            CustomIndexPage::class,
            DetailPage::class,
            FormPage::class,
        ];
    }

    protected function afterSave(mixed $item, FieldsContract $fields): mixed
    {
        $this->syncChats($item);
        return parent::afterSave($item, $fields);
    }

    private function syncChats(Model $item): void
    {
        $request = request()->all();
        $chatsData = collect($request['chats'])
            ->filter(fn($row) => !empty($row['chat_id']));
        $newChatIds = $chatsData->pluck('chat_id')->toArray();
        $item->chats()->whereNotIn('chat_id', $newChatIds)->delete();
        foreach ($chatsData as $chat) {
            $item->chats()->updateOrCreate(
                ['chat_id' => $chat['chat_id']],
                ['name' => $chat['name'] ?? '']
            );
        }
    }

    // Поля для индексной страницы
    public function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Название', 'name')
                ->sortable(),
            Select::make('Сервис', 'service')
                ->options(Bot::SERVICES)
                ->sortable(),
            \App\MoonShine\Fields\ApiKeyField::make('Токен', 'token'),
            \App\MoonShine\Fields\RecipientsField::make('Получатели', 'chats', fn($item) => $item->chats->pluck('name')->all()),
            \App\MoonShine\Fields\CountField::make('Триггеры', 'id', fn($item) => \App\Models\Trigger::query()->where('bot_id', $item->id)->count()),
        ];
    }

    // Поля для формы создания/редактирования
    public function formFields(): iterable
    {
        $item = $this->getItem();

        return [
            // Основные поля
            CustomText::make('Название', 'name')
                ->unique('bots', 'name', 'Бот с таким названием уже существует')
                ->required(),

            SelectField::make('Сервис', 'service')
                ->options(Bot::SERVICES)
                ->required(),

            CustomText::make('Токен', 'token')
                ->pattern('/^[0-9]{8,10}:[a-zA-Z0-9_-]{35}$/')
                ->required()
                ->hint('Для Telegram: получить у @BotFather'),

            CustomText::make('API URL', 'api_url')
                ->pattern('/^https?:\/\//i')
                ->hint('Оставьте пустым для использования стандартного API'),

            BotChatsField::make('Чаты', 'chats')
                ->serviceField('select[name="service"]')
                ->tokenField('input[name="token"]')
                ->apiUrlField('input[name="api_url"]')
                ->onApply(function ($item) { return $item; }),
        ];
    }

    public function detailFields(): iterable
    {
        return [
            Text::make('Название', 'name'),
            SelectField::make('Сервис', 'service')
                ->options(Bot::SERVICES),
            Text::make('Токен', 'token'),
            Url::make('API URL', 'api_url'),
        ];
    }

    // Настройка активных действий
    protected function activeActions(): ListOf
    {
        return parent::activeActions()
            ->except(Action::VIEW);
    }

    // Кнопки индексной страницы
    protected function indexButtons(): ListOf
    {
        return parent::indexButtons();

    }

    // Правила валидации
    public function rules(mixed $item): array
    {
        return [
            'name' => 'required|string|max:255',
            'service' => 'required|in:' . implode(',', array_keys(Bot::SERVICES)),
            'token' => [
                'required',
                'string',
                'max:500',
                'min:10',
                function ($attribute, $value, $fail) use ($item) {
                    $service = request()->input('service') ?? $item?->service;

                    if (!$service) {
                        $fail('Не указан сервис для проверки токена');
                        return;
                    }

                    if (!Bot::validateToken($value, $service)) {
                        switch ($service) {
                            case 'telegram':
                                $fail('Неверный формат токена Telegram. Пример: 1234567890:ABCdefGHIjklMNOpqrSTUvwxYZ');
                                break;
                            default:
                                $fail('Неверный формат токена для выбранного сервиса');
                        }
                    }
                },
            ],
            'api_url' => 'nullable|url|max:500',
        ];
    }

    // Сообщения валидации
    public function validationMessages(): array
    {
        return [
            'token.required' => 'Токен обязателен',
            'token.min' => 'Токен должен быть не менее 10 символов',
            'token.max' => 'Токен не должен превышать 500 символов',
            'name.required' => 'Название обязательно',
            'service.in' => 'Выбранный сервис не поддерживается',
            'api_url.url' => 'API URL должен быть корректным URL',
        ];
    }

    // Поля для поиска
    public function search(): array
    {
        return ['name', 'token'];
    }

    // Фильтры
    public function filters(): array
    {
        return [
            Text::make('Название', 'name')
                ->placeholder('Поиск по названию'),

            SelectField::make('Сервис', 'service')
                ->options(Bot::SERVICES)
                ->nullable(),

            Text::make('Токен', 'token')
                ->placeholder('Фильтр по токену (частичное совпадение)')
                ->hint('Ищет по части токена'), // Добавляем подсказку

            Text::make('API URL', 'api_url')
                ->placeholder('Фильтр по API URL')
                ->hint('Ищет по части URL'),
        ];
    }

    // Запрос для индексной страницы
    public function indexQuery(): Builder
    {
        return parent::indexQuery()->orderBy('name');
    }

    public function testChat(MoonShineRequest $request): JsonResponse
    {
        try {
            // Получаем ID чата из параметров
            $chatId = $request->get('chatResourceItem');

            if (!$chatId) {
                return response()->json([
                    'message' => '❌ ID чата не указан',
                    'messageType' => 'error'
                ]);
            }

            // Находим чат
            $chat = BotChat::find($chatId);

            if (!$chat) {
                return response()->json([
                    'message' => '❌ Чат не найден в базе данных',
                    'messageType' => 'error'
                ]);
            }

            // Отправляем тестовое сообщение
            $result = $chat->sendTestMessage();

            if ($result['success'] ?? false) {
                $message = '✅ Тестовое сообщение успешно отправлено!';
                if (isset($result['message_id'])) {
                    $message .= " ID сообщения: {$result['message_id']}";
                }

                return response()->json([
                    'message' => $message,
                    'messageType' => 'success'
                ]);
            } else {
                $errorMessage = $this->formatTelegramError($result['message'] ?? 'Неизвестная ошибка');

                return response()->json([
                    'message' => '❌ Ошибка отправки: ' . $errorMessage,
                    'messageType' => 'error'
                ]);
            }

        } catch (\Exception $e) {
            $errorMessage = $this->formatExceptionMessage($e->getMessage());

            return response()->json([
                'message' => '❌ Ошибка: ' . $errorMessage,
                'messageType' => 'error'
            ]);
        }
    }

    /**
     * Форматирование ошибок от Telegram API
     */
    private function formatTelegramError(string $error): string
    {
        // Распространенные ошибки Telegram API
        $telegramErrors = [
            'Chat not found' => 'Чат не найден. Проверьте правильность Chat ID',
            'chat not found' => 'Чат не найден. Проверьте правильность Chat ID',
            'Forbidden: bot was blocked by the user' => 'Бот заблокирован пользователем',
            'Forbidden: user is deactivated' => 'Пользователь деактивирован',
            'Bad Request: chat not found' => 'Чат не найден. Проверьте Chat ID',
            'Bad Request: group chat was upgraded to a supergroup chat' => 'Группа была преобразована в супергруппу, нужен новый Chat ID',
            'Bad Request: have no rights to send a message' => 'Нет прав на отправку сообщений в этот чат',
            'Bad Request: message to send not found' => 'Сообщение для отправки не найдено',
            'Bad Request: message text is empty' => 'Текст сообщения пуст',
            'Unauthorized' => 'Неверный токен бота',
            'Not Found' => 'Бот не найден (неверный токен)',
            'Too Many Requests' => 'Слишком много запросов. Попробуйте позже',
            'Gateway Timeout' => 'Таймаут соединения с Telegram',
            'Connection timed out' => 'Таймаут соединения',
            'Failed to connect' => 'Не удалось подключиться к Telegram API',
        ];

        // Ищем совпадение в ошибке
        foreach ($telegramErrors as $key => $message) {
            if (stripos($error, $key) !== false) {
                return $message;
            }
        }

        // Если не нашли совпадение, возвращаем оригинальную ошибку
        return $error;
    }

    /**
     * Форматирование исключений
     */
    private function formatExceptionMessage(string $error): string
    {
        // Обработка Guzzle исключений
        if (str_contains($error, 'Client error: `POST') && str_contains($error, 'resulted in a')) {
            // Извлекаем код ошибки и сообщение
            if (preg_match('/resulted in a \'(\d+) Bad Request\' response:\s*(.+)/', $error, $matches)) {
                $code = $matches[1] ?? '';
                $message = $matches[2] ?? $error;

                // Парсим JSON ответ если есть
                if (preg_match('/\{"ok":false,"error_code":(\d+),".+?"description":"([^"]+)"\}/', $message, $jsonMatches)) {
                    $telegramCode = $jsonMatches[1] ?? '';
                    $telegramMessage = $jsonMatches[2] ?? $message;

                    return $this->formatTelegramError($telegramMessage) . " (код: {$telegramCode})";
                }

                return "Ошибка Telegram API ({$code}): " . $this->formatTelegramError($message);
            }
        }

        // Обработка SSL ошибок
        if (str_contains($error, 'SSL') || str_contains($error, 'certificate')) {
            return 'Ошибка SSL сертификата. Проверьте настройки сервера';
        }

        // Обработка таймаутов
        if (str_contains($error, 'timed out') || str_contains($error, 'timeout')) {
            return 'Таймаут соединения. Проверьте интернет-подключение';
        }

        return $error;
    }
}
