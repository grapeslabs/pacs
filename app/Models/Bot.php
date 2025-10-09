<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Bot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'service',
        'token',
        'api_url',
    ];

    const SERVICES = [
        'telegram' => 'Telegram',
    ];

    const DEFAULT_API_URLS = [
        'telegram' => 'https://api.telegram.org',
    ];

    /**
     * Отношение к чатам
     */
    public function chats(): HasMany
    {
        return $this->hasMany(BotChat::class, 'bot_id', 'id');

    }

    public function sendBroadcast(string $text, string|array|null $files = null)
    {
        return $this->chats->map(function ($chat) use ($text, $files) {
            return [
                'chat_name' => $chat->name,
                'chat_id'   => $chat->chat_id,
                'result'    => $this->sendMessage($chat->chat_id, $text, $files)
            ];
        });
    }

    public function sendMessage(string $chatId, string $text, string|array|null $files = null): array
    {
        return match ($this->service) {
            'telegram' => $this->sendTelegram($this, $chatId, $text, $files),
            default    => ['success' => false, 'message' => 'Сервис не поддерживается'],
        };
    }

    private function sendTelegram(Bot $bot, $chatId, $text, string|array|null $files): array
    {
        $baseUrl = rtrim($bot->api_url ?: "https://api.telegram.org", '/');
        $request = Http::timeout(15);
        $filePaths = is_array($files) ? $files : ($files ? [$files] : []);
        $filePaths = array_filter($filePaths, fn($path) => file_exists($path));

        $filePaths = array_slice($filePaths, 0, 10);
        $count = count($filePaths);

        if ($count > 1) {
            $method = 'sendMediaGroup';
            $mediaGroup = [];
            foreach (array_values($filePaths) as $index => $filePath) {
                $attachName = "file_{$index}";

                $mediaItem = [
                    'type'  => 'photo',
                    'media' => "attach://{$attachName}"
                ];
                if ($index === 0) {
                    $mediaItem['caption'] = $text;
                    $mediaItem['parse_mode'] = 'HTML';
                }

                $mediaGroup[] = $mediaItem;
                $request = $request->attach($attachName, file_get_contents($filePath), basename($filePath));
            }
            $params = [
                'chat_id' => $chatId,
                'media'   => json_encode($mediaGroup)
            ];

        } elseif ($count === 1) {
            $method = 'sendPhoto';
            $filePath = reset($filePaths);
            $request = $request->attach('photo', file_get_contents($filePath), basename($filePath));
            $params = ['chat_id' => $chatId, 'caption' => $text, 'parse_mode' => 'HTML'];

        } else {
            $method = 'sendMessage';
            $params = ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'HTML'];
        }

        $url = "{$baseUrl}/bot{$bot->token}/{$method}";

        $response = $request->post($url, $params);
        $result = $response->json();

        return [
            'success' => $result['ok'] ?? false,
            'message' => $result['description'] ?? 'OK',
            'data'    => $result['result'] ?? null
        ];
    }

    public static function validateTelegramToken(string $token): bool
    {
        // Стандартный формат токена Telegram: 1234567890:ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghi
        // Минимум 30 символов, начинается с цифр, затем двоеточие, затем буквы/цифры
        return preg_match('/^\d{9,10}:[a-zA-Z0-9_-]{35}$/', $token);
    }

    /**
     * Проверка токена в зависимости от сервиса
     */
    public static function validateToken(string $token, string $service): bool
    {
        switch ($service) {
            case 'telegram':
                return self::validateTelegramToken($token);
            // Добавьте проверки для других сервисов
            default:
                // Общая проверка: не пустая строка
                return !empty($token) && strlen($token) >= 10 && strlen($token) <= 500;
        }
    }

}
