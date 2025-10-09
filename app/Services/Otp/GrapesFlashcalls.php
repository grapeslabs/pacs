<?php

declare(strict_types=1);

namespace App\Services\Otp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GrapesFlashcalls implements Otp
{
    public function __construct(private string $url, private string $token)
    {
    }

    public function send(ClientId $clientId): void
    {
        cache()->forget('otp_' . $clientId->clientId);
        $response = Http::withToken($this->token)->accept('application/json')
            ->asForm()->post(
                $this->url . 'api/v1/send',
                [
                    'number' => $clientId->clientId,
                    'gateway' => 'flashcall'
                ]
            );

        if ($response->successful()) {
            cache()->put('otp_' . $clientId->clientId, $response->json('sms'));
        } else {
            Log::error('OTP send error', ['response' => $response]);
        }
    }

    public function check(ClientId $clientId, Password $password): bool
    {
        $messageId = cache()->get('otp_' . $clientId->clientId);
        if (!$messageId) {
            return false;
        }
        $response = Http::withToken($this->token)->accept('application/json')->get(
            $this->url . 'api/v1/flashcall/check_code',
            [
                'message_id' => $messageId,
                'code' => $password->password
            ]
        );

        if ($response->failed()) {
            Log::error('OTP check error', ['response' => $response]);
            return false;
        }

        cache()->forget('otp_' . $clientId->clientId);
        return true;
    }
}
