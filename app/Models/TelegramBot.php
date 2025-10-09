<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TelegramBot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'bot_token',
        'api_url',
        'chat_id',
    ];


    /**
     * Отправить тестовое сообщение
     */
    public function sendTestMessage(): array
    {
        try {
            $apiUrl = $this->api_url ?: 'https://api.telegram.org';
            $url = rtrim($apiUrl, '/') . "/bot{$this->bot_token}/sendMessage";

            $message = "Тестовое сообщение от бота: {$this->name}\n";
            $message .= "Время: " . now()->format('d.m.Y H:i:s') . "\n";
            $message .= "Статус: Активен ✅";

            $data = [
                'chat_id' => $this->chat_id,
                'text' => $message,
                'parse_mode' => 'HTML',
            ];

            $client = new \GuzzleHttp\Client([
                'timeout' => 10,
                'connect_timeout' => 5,
                'verify' => false, // Отключаем проверку SSL для тестов
            ]);

            $response = $client->post($url, [
                'form_params' => $data,
                'http_errors' => false, // Не выбрасывать исключения при HTTP ошибках
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            $result = json_decode($body, true);

            // Логирование для отладки
            \Log::info('Telegram API Response', [
                'status' => $statusCode,
                'response' => $result,
                'bot_id' => $this->id,
                'chat_id' => $this->chat_id,
            ]);

            if ($statusCode === 200 && ($result['ok'] ?? false)) {
                return [
                    'success' => true,
                    'message' => 'Сообщение успешно отправлено!',
                    'message_id' => $result['result']['message_id'] ?? null,
                    'response' => $result,
                ];
            }

            // Обработка ошибок Telegram API
            $errorMessage = $result['description'] ?? 'Неизвестная ошибка';

            // Добавляем код ошибки если есть
            if (isset($result['error_code'])) {
                $errorMessage = "Код {$result['error_code']}: {$errorMessage}";
            }

            return [
                'success' => false,
                'message' => $errorMessage,
                'status_code' => $statusCode,
                'response' => $result,
            ];

        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            return [
                'success' => false,
                'message' => 'Ошибка подключения: ' . $e->getMessage(),
            ];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return [
                'success' => false,
                'message' => 'Ошибка запроса: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Получить информацию о боте
     */
    public function getBotInfo(): array
    {
        try {
            $apiUrl = $this->api_url ?: 'https://api.telegram.org';
            $url = rtrim($apiUrl, '/') . "/bot{$this->bot_token}/getMe";

            $client = new \GuzzleHttp\Client();
            $response = $client->get($url, [
                'timeout' => 10,
                'connect_timeout' => 5,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if ($result['ok'] ?? false) {
                return [
                    'success' => true,
                    'bot_info' => $result['result'],
                ];
            }

            return [
                'success' => false,
                'message' => $result['description'] ?? 'Неизвестная ошибка',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
