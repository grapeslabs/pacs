<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChatsController extends Controller
{
    public function test(Request $request): JsonResponse
    {
        $request->validate([
            'service' => 'required|string',
            'api_url' => 'nullable|url|max:500',
            'token' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($request) {
                    if (! Bot::validateToken($value, (string) $request->input('service'))) {
                        $fail('Неверный формат токена для выбранного сервиса');
                    }
                },
            ],
            'chat_id' => 'required|integer',
        ]);

        $message = "✅ Тестовое сообщение от бота\n";
        $message .= "🕒 Время: " . now()->format('d.m.Y H:i:s') . "\n";
        $message .= "Сервис: " . $request->service;

        $result = $this->sendMessage(
            $request->service,
            $request->token,
            $message,
            $request->chat_id,
            $request->api_url
        );

        return response()->json($result);
    }


    public function sendMessage($service, $token, $message, $chatId, $api_url): array
    {
        $params = array_filter([
            'token'   => $token,
            'chatId'  => $chatId,
            'message' => $message,
            'url' => $api_url ?: null
        ]);
        try {
            switch ($service) {
                case 'telegram':
                    return $this->sendTelegramMessage(...$params);
                default:
                    return [
                        'success' => false,
                        'message' => 'Сервис не поддерживается',
                    ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function sendTelegramMessage($token, $chatId, $message, $url="https://api.telegram.org", $options = []): array
    {
        try {
            $url = rtrim($url, '/') . "/bot$token/sendMessage";
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->post($url, [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                ]);

            $result = $response->json();

            if ($result['ok'] ?? false) {
                return [
                    'success' => true,
                    'message' => 'Сообщение успешно отправлено',
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
