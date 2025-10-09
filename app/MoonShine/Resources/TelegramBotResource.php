<?php

namespace App\MoonShine\Resources;

use App\Models\TelegramBot;
use App\MoonShine\Pages\CustomIndexPage;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Url;
use MoonShine\UI\Components\ActionButton;
use MoonShine\Support\ListOf;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class TelegramBotResource extends BaseModelResource
{
    protected string $model = TelegramBot::class;
    protected string $title = 'Telegram Боты';
    protected function pages(): array
    {
        return [
            CustomIndexPage::class,
            DetailPage::class,
            FormPage::class,
        ];
    }

    public function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Название', 'name')
                ->sortable()
                ->badge('blue'),
            Text::make('ID чата', 'chat_id')
                ->sortable()
                ->copy(),
        ];
    }

    public function formFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Название', 'name')
                ->required()
                ->placeholder('Мой Telegram бот'),
            Text::make('API токен бота', 'bot_token')
                ->required()
                ->placeholder('1234567890:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsaw')
                ->hint('Получить у @BotFather'),
            Url::make('URL Telegram API', 'api_url')
                ->nullable()
                ->placeholder('https://api.telegram.org')
                ->hint('Оставьте пустым для использования стандартного API'),
            Text::make('ID чата', 'chat_id')
                ->required()
                ->placeholder('-1001234567890 или 123456789')
                ->hint('ID группы, канала или пользователя'),
        ];
    }

    protected function indexButtons(): ListOf
    {
        return parent::indexButtons()
            ->prepend(
                ActionButton::make('Проверить')
                    ->method('testBot')
                    ->secondary()
                    ->withConfirm(
                        'Проверить бота?',
                        function($item) {
                            // Безопасно получаем ID
                            if (is_array($item)) {
                                $item = $item['id'] ?? null;
                            }

                            $botId = is_object($item) ? ($item->getKey() ?? $item) : $item;

                            if (!$botId) {
                                return "Бот не найден";
                            }

                            $bot = TelegramBot::find($botId);

                            if (!$bot) {
                                return "Бот с ID {$botId} не найден";
                            }

                            $name = ($bot->name ?? '');
                            $chatId = ($bot->chat_id ?? '');

                            return "Будет отправлено тестовое сообщение боту: {$name} В чат: {$chatId}";
                        },
                        'Отправить'
                    )
                    ->showInLine()
            );
    }

    /**
     * Проверка существующего бота (из таблицы)
     */
    public function testBot(): JsonResponse
    {
        try {
            // Получаем ID из разных источников
            $id = $this->getBotIdFromRequest();

            if (!$id) {
                return response()->json([
                    'message' => '❌ ID бота не указан',
                    'messageType' => 'error'
                ]);
            }

            // Получаем бота по ID
            $telegramBot = TelegramBot::find($id);

            if (!$telegramBot) {
                return response()->json([
                    'message' => '❌ Бот не найден',
                    'messageType' => 'error'
                ]);
            }

            // Используем сохраненные данные из базы
            $result = $telegramBot->sendTestMessage();

            if ($result['success']) {
                $message = '✅ Тестовое сообщение успешно отправлено в указанный чат!';
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

    /**
     * Получаем ID бота из запроса
     */
    private function getBotIdFromRequest()
    {
        // Пробуем разные варианты получения ID
        $id = request('resourceItem')
            ?? request('id')
            ?? request()->route('resourceItem')
            ?? request()->route('id');

        // Если пришел массив, берем первый элемент или id
        if (is_array($id)) {
            $id = $id['id'] ?? $id[0] ?? null;
        }

        return $id;
    }

    public function rules($item): array
    {
        return [
            'name' => 'required|string|max:255',
            'bot_token' => 'required|string|regex:/^\d+:[a-zA-Z0-9_-]+$/',
            'api_url' => 'nullable|url',
            'chat_id' => 'required|string|max:100',
        ];
    }

    public function validationMessages(): array
    {
        return [
            'bot_token.regex' => 'Неверный формат токена. Пример: 1234567890:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsaw',
            'chat_id.required' => 'ID чата обязателен',
            'name.required' => 'Название бота обязательно',
        ];
    }

    public function search(): array
    {
        return ['name', 'chat_id'];
    }

    public function filters(): array
    {
        return [
            Text::make('Название', 'name')
                ->placeholder('Поиск по названию'),
            Text::make('ID чата', 'chat_id')
                ->placeholder('Поиск по ID чата'),
        ];
    }

    public function indexQuery(): Builder
    {
        return parent::indexQuery()->orderBy('name');
    }
}
