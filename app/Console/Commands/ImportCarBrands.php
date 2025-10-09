<?php

// namespace App\Console\Commands;

// use App\Models\CarBrand;
// use Illuminate\Console\Command;
// use League\Csv\Reader;

// class ImportCarBrands extends Command
// {
//     protected $signature = 'import:car-brands {file?}';
//     protected $description = 'Импорт марок автомобилей из CSV файла';

//     public function handle()
//     {
//         $filename = $this->argument('file') ?? 'car_brands.csv';
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

//             $existingBrand = CarBrand::where('name', $name)->first();

//             if ($existingBrand) {
//                 $this->info("Марка уже существует: {$name}");
//             } else {
//                 CarBrand::create(['name' => $name]);
//                 $imported++;
//                 $this->info("Добавлена марка: {$name}");
//             }
//         }

//         $this->info("Импорт завершен! Добавлено новых марок: {$imported}");
//         return 0;
//     }
// }