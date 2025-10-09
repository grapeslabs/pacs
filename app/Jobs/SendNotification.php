<?php

namespace App\Jobs;

use App\Models\Bot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $botId;
    private string $message;
    private array|null $files;
    public function __construct(int $botId, string $message, array|null $files = null)
    {
        $this->botId = $botId;
        $this->message = $message;
        $this->files = $files;
    }

    public function handle(): void
    {
        try {
            $bot = Bot::with('chats')->findOrFail($this->botId);
            $bot->sendBroadcast($this->message, $this->files);
        } catch (\Exception $e) {
            Log::error("Ошибка отправки ботом (ID: {$this->botId}) в очереди: " . $e->getMessage());
        }
    }
}
