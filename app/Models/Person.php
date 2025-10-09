<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Person extends Model
{
    use HasFactory;

    protected $table = 'person';

    protected $fillable = [
        'last_name',
        'first_name',
        'middle_name',
        'birth_date',
        'certificate_number',
        'photo',
        'organization_id',
        'comment',
        'grapesva_uuid',
        'key_uid',
        'face_vector',
        'vectorization_status',
        'vectorization_error',
        'vectorized_at',
        'frozen_start',
        'frozen_end',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'face_vector' => 'array',
        'vectorized_at' => 'datetime',
        'photo' => 'array',
        'frozen_start' => 'datetime',
        'frozen_end' => 'datetime',
    ];

//    /**
//     * Добавляет персону во все контроллеры СКУД
//     */
//    public static function addToSkud(Person $person): void
//    {
//        try {
//
//            $controllers = \GrapesLabs\PinvideoSkud\Models\SkudController::where("type", '!=', 'pingate')->get();
//            $images = [];
//
//            // 📸 ОБРАБАТЫВАЕМ ФОТОГРАФИИ КАК БИНАРНЫЕ ДАННЫЕ
//            if (!empty($person->photo) && is_array($person->photo)) {
//                foreach ($person->photo as $photoPath) {
//                    $fullPath = Storage::disk('public')->path($photoPath);
//                    if (file_exists($fullPath)) {
//                        // Читаем БИНАРНЫЕ данные файла (сырые байты)
//                        $binaryContent = file_get_contents($fullPath);
//
//                        // ✅ ПРАВИЛЬНО: передаем БИНАРНЫЕ данные (модуль сам закодирует в base64)
//                        $images[] = [
//                            'id' => pathinfo($photoPath, PATHINFO_FILENAME),
//                            'content' => $binaryContent // ⚡ БИНАРНЫЕ ДАННЫЕ
//                        ];
//
//                        Log::info("📸 Photo binary data prepared", [
//                            'path' => $photoPath,
//                            'binary_size_bytes' => strlen($binaryContent),
//                            'file_size' => filesize($fullPath)
//                        ]);
//                    }
//                }
//            }
//
//            Log::info("📋 Sending to SKUD controllers", [
//                'person_id' => $person->id,
//                'controllers_count' => $controllers->count(),
//                'photos_count' => count($images),
//                'photos_with_binary_data' => count(array_filter($images, fn($img) => !empty($img['content'])))
//            ]);
//
//            $person->getSkudUid();
//            $person->save();
//
//            foreach ($controllers as $controller) {
//                if ($controller->type == 'pingate') {
//                    continue;
//                }
//                try {
//                    // 🎯 СОЗДАЕМ FACEID КЛЮЧ С БИНАРНЫМИ ФОТОГРАФИЯМИ
//                    $key = new \GrapesLabs\PinvideoSkud\Keys\FaceIdKey(
//                        uid: $person->getSkudUid(),
//                        images: $images, // Бинарные данные фото
//                        name: $person->getFullName()
//                    );
//
//                    // ⚡ ПРЯМОЙ ВЫЗОВ КОНТРОЛЛЕРА
//                    $skudController = \GrapesLabs\PinvideoSkud\ControllerFactory::create($controller);
//                    $result = $skudController->writeKeys([$key]);
//
//                    Log::info("✅ Person with binary photos added to SKUD", [
//                        'person_id' => $person->id,
//                        'controller_sn' => $controller->serial_number,
//                        'photos_count' => count($images),
//                        'result' => $result
//                    ]);
//
//                } catch (\Exception $e) {
//                    Log::error("❌ Failed to add person with photos to SKUD", [
//                        'person_id' => $person->id,
//                        'controller_sn' => $controller->serial_number,
//                        'error' => $e->getMessage(),
//                        'error_trace' => $e->getTraceAsString()
//                    ]);
//                }
//            }
//
//            Log::info("✅ Person successfully added to all SKUD controllers", [
//                'person_id' => $person->id,
//                'controllers_count' => $controllers->count(),
//                'total_photos_processed' => count($images)
//            ]);
//
//        } catch (\Exception $e) {
//            Log::error("❌ SKUD integration with photos failed", [
//                'person_id' => $person->id,
//                'error' => $e->getMessage(),
//                'error_trace' => $e->getTraceAsString()
//            ]);
//        }
//    }
//
//    /**
//     * Удаляет персону из всех контроллеров СКУД
//     */
//    public static function removeFromSkud(Person $person): void
//    {
//        try {
//            $controllers = \GrapesLabs\PinvideoSkud\Models\SkudController::where("type", '!=', 'pingate')->get();
//
//            foreach ($controllers as $controller) {
//                try {
//                    $key = new \GrapesLabs\PinvideoSkud\Keys\FaceIdKey(
//                        uid: $person->getSkudUid(),
//                        images: [], // Пустой массив для удаления
//                        name: $person->getFullName()
//                    );
//
//                    $skudController = \GrapesLabs\PinvideoSkud\ControllerFactory::create($controller);
//                    $skudController->clearKeys([$key]);
//
//                    Log::info("✅ Person removed from SKUD controller", [
//                        'person_id' => $person->id,
//                        'controller_sn' => $controller->serial_number
//                    ]);
//
//                } catch (\Exception $e) {
//                    Log::error("❌ Failed to remove person from SKUD controller", [
//                        'person_id' => $person->id,
//                        'controller_sn' => $controller->serial_number,
//                        'error' => $e->getMessage()
//                    ]);
//                }
//            }
//
//            Log::info("✅ Person successfully removed from all SKUD controllers", [
//                'person_id' => $person->id,
//                'controllers_count' => $controllers->count()
//            ]);
//
//        } catch (\Exception $e) {
//            Log::error("❌ SKUD removal failed", [
//                'person_id' => $person->id,
//                'error' => $e->getMessage()
//            ]);
//        }
//    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'person_tag', 'person_id', 'tag_id');
    }

    public function keys(): HasMany
    {
        return $this->hasMany(Key::class);
    }

    public function photos()
    {
        return $this->hasMany(PersonPhoto::class);
    }

    public function getTagsListAttribute()
    {
        return $this->tags->pluck('name')->implode(', ');
    }

    public function cars(): BelongsToMany
    {
        return $this->belongsToMany(Car::class, 'car_person');
    }

    public function getFirstPhotoAttribute()
    {
        if ($this->photo && is_array($this->photo) && count($this->photo) > 0) {
            return $this->photo[0];
        }
        return null;
    }

    public function getSkudUid(): string
    {
        return 'person_' . $this->id;
    }

    public function getFullName(): string
    {
        return trim($this->last_name . ' ' . $this->first_name . ' ' . ($this->middle_name ?? ''));
    }
}
