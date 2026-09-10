<?php

namespace App\Jobs;

use App\Models\Bot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class SendNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [10, 30, 60, 300];
    public int $timeout = 60;

    public function __construct(
        private int $botId,
        private string $chatId,
        private string $message,
        private array|null $files = null,
    ) {
    }

    public function handle(): void
    {
        $bot = Bot::query()->findOrFail($this->botId);

        $result = $bot->sendMessage($this->chatId, $this->message, $this->files);

        if ($result['success'] ?? false) {
            return;
        }

        if (($result['status'] ?? null) === 429) {
            $this->release($result['retry_after'] ?? 30);

            return;
        }

        throw new RuntimeException(sprintf(
            'Бот %d не отправил сообщение в чат %s: %s',
            $this->botId,
            $this->chatId,
            $result['message'] ?? 'неизвестная ошибка'
        ));
    }
}
