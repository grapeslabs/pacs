<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class BotChat extends Model
{
    use HasFactory;

    protected $table = "bot_chats";

    protected $fillable = [
        'bot_id',
        'chat_id',
        'name',
    ];

    /**
     * Проверка формата Chat ID
     */
    public static function validateChatId(string $chatId): bool
    {
        // Формат @username
        if (preg_match('/^@[a-zA-Z0-9_]{5,32}$/', $chatId)) {
            return true;
        }

        // Целое число (личный чат)
        if (preg_match('/^\d{1,20}$/', $chatId)) {
            return true;
        }

        // Отрицательное целое число (групповой чат/канал)
        if (preg_match('/^-\d{1,20}$/', $chatId)) {
            return true;
        }

        return false;
    }

    public static function duplicateChatId(string $botId, string $chatId, ?int $excludeId = null): bool
    {
        $query = static::where('bot_id', $botId)
            ->where('chat_id', $chatId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Отношение к боту
     */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    /**
     * Отправить тестовое сообщение в этот чат
     */
    public function sendTestMessage(): array
    {
        return $this->bot->sendTestMessage($this->chat_id);
    }
}
