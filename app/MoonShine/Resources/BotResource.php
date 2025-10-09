<?php

namespace App\MoonShine\Resources;

use App\Models\Bot;
use App\MoonShine\Fields\BotChatsField;
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
            Text::make('Токен', 'token')
                ->sortable(),
            Url::make('API URL', 'api_url')
                ->sortable(),
        ];
    }

    // Поля для формы создания/редактирования
    public function formFields(): iterable
    {
        $item = $this->getItem();

        return [
            // Основные поля
            Text::make('Название', 'name')
                ->required(),

            Select::make('Сервис', 'service')
                ->options(Bot::SERVICES)
                ->required(),

            Text::make('Токен', 'token')
                ->required()
                ->hint('Для Telegram: получить у @BotFather'),

            Url::make('API URL', 'api_url')
                ->nullable()
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
            Select::make('Сервис', 'service')
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

            Select::make('Сервис', 'service')
                ->options(Bot::SERVICES)
                ->nullable()
                ->placeholder('Все сервисы'),

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

    // Количество элементов на странице
    protected int $itemsPerPage = 20;
    protected bool $simplePaginate = false;
    protected bool $isAsync = true;
}
