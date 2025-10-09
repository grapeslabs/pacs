<?php
// app/Console/Commands/ResetDemoData.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetDemoData extends Command
{
    protected $signature = 'demo:reset {--force : Принудительный сброс без подтверждения}';
    protected $description = 'Сброс демо-данных в исходное состояние';

    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('Все текущие данные будут удалены. Продолжить?')) {
            $this->info('Операция отменена');
            return 0;
        }

        $this->info('🔄 Начинаем сброс демо-данных...');

        try {
            // Для PostgreSQL отключаем проверку внешних ключей
            DB::statement('SET session_replication_role = replica;');

            $this->info('🗑️ Очистка таблиц...');


            DB::table('skud_event_persons')->truncate();
            DB::table('skud_event_car_plates')->truncate();
            DB::table('grapeslabs_skud_events')->truncate(); //события
            DB::table('equipment')->truncate();       // Оборудование доступа
            DB::table('grapeslabs_skud_controllers')->truncate(); // Зарегистрированные контроллеры
            DB::table('car_person')->truncate();      // Связи машин с людьми
            DB::table('cars')->truncate();             // Машины
            DB::table('car_colors')->truncate();       // Цвета
            DB::table('car_brands')->truncate();       // Марки
            DB::table('bot_chats')->truncate();        // Чаты ботов
            DB::table('bots')->truncate();             // Боты
            DB::table('person_tag')->truncate();       // Связи персон с тегами
            DB::table('person')->truncate();           // Персоны
            DB::table('tags')->truncate();             // Теги
            DB::table('organizations')->truncate();    // Организации
            DB::table('trigger_tag')->truncate();      //Триггеры
            DB::table('triggers')->truncate();
            DB::table('api_keys')->truncate();         //Ключи апи

            DB::statement('SET session_replication_role = origin;');

            $this->info('✅ Демо-данные успешно сброшены!');

        } catch (\Exception $e) {
            $this->error('❌ Ошибка при сбросе данных: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
