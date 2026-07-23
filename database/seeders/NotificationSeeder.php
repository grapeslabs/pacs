<?php

namespace Database\Seeders;

use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $command = $this->command ?? null;

        $users = User::all();

        if ($users->isEmpty()) {
            $command?->warn('   ⚠ Нет пользователей — уведомления не созданы.');

            return;
        }

        $templates = [
            ['error',   'Видеопотоки отключены — недостаточно места на диске. Освободите место, чтобы восстановить работу.', '-3 hours',  false],
            ['warning', 'Заканчивается место на диске. Видеопотоки могут быть отключены, освободите место для стабильной работы.', '-4 hours', false],
            ['error',   'Доступ запрещён · А 482 ОР 77. КПП Восток.', '-5 hours', false],
            ['info',    'Видеопотоки включены — место на диске освобождено. Запись возобновлена.', '-7 hours', true],
            ['info',    'Камера не отвечает. «Серверная» · cam-04.', '-1 days', true],
            ['warning', 'Контроллер СКУД недоступен. Главный вход · cnt-02.', '-1 days', true],
            ['info',    'Резервное копирование завершено. База данных сохранена успешно.', '-3 days', true],
            ['error',   'Сбой распознавания лиц. Перезапустите модуль аналитики.', '-5 days', true],
        ];

        $created = 0;

        foreach ($users as $user) {
            foreach ($templates as [$type, $message, $when, $read]) {
                $at = Carbon::now()->modify($when);

                DB::table('notifications')->insert([
                    'id' => (string) Str::uuid(),
                    'type' => SystemNotification::class,
                    'notifiable_type' => User::class,
                    'notifiable_id' => $user->id,
                    'data' => json_encode([
                        'type' => $type,
                        'message' => $message,
                        'links' => [],
                    ], JSON_UNESCAPED_UNICODE),
                    'read_at' => $read ? $at->copy()->addMinutes(5) : null,
                    'created_at' => $at,
                    'updated_at' => $at,
                ]);

                $created++;
            }
        }

        $command?->line("   Уведомления: {$created} (для {$users->count()} польз.)");
    }
}
