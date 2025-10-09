<?php

// namespace App\Console\Commands;

// use App\Models\CarColor;
// use Illuminate\Console\Command;
// use League\Csv\Reader;

// class ImportCarColors extends Command
// {
//     protected $signature = 'import:car-colors {file?}';
//     protected $description = 'Импорт цветов автомобилей из CSV файла';

//     public function handle()
//     {
//         $filename = $this->argument('file') ?? 'car_colors.csv';
//         $filePath = storage_path('app/imports/' . $filename);

//         if (!file_exists($filePath)) {
//             $this->error("Файл {$filePath} не найден!");
//             $this->info("Разместите CSV файл в: storage/app/imports/");
//             return 1;
//         }

//         $csv = Reader::createFromPath($filePath, 'r');
//         $csv->setHeaderOffset(0);

//         $records = $csv->getRecords();
//         $imported = 0;
//         $updated = 0;

//         foreach ($records as $record) {
//             $name = trim($record['name']);
            
//             if (empty($name)) {
//                 continue;
//             }

//             $existingColor = CarColor::where('name', $name)->first();

//             if ($existingColor) {
//                 $this->info("Цвет уже существует: {$name}");
//             } else {
//                 CarColor::create(['name' => $name]);
//                 $imported++;
//                 $this->info("Добавлен цвет: {$name}");
//             }
//         }

//         $this->info("Импорт завершен! Добавлено новых цветов: {$imported}");
//         return 0;
//     }
// }