<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CarPassageRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';
    protected $table = 'car_passage_rules';

    public const TYPE_ALLOW = 'allow';
    public const TYPE_DENY  = 'deny';

    public const TYPES = [
        self::TYPE_ALLOW => 'Разрешить',
        self::TYPE_DENY  => 'Запретить',
    ];

    public const DIRECTION_ENTRY = 'entry';
    public const DIRECTION_EXIT  = 'exit';
    public const DIRECTION_BOTH  = 'both';

    public const DIRECTIONS = [
        self::DIRECTION_ENTRY => 'Въезд',
        self::DIRECTION_EXIT  => 'Выезд',
        self::DIRECTION_BOTH  => 'Оба направления',
    ];

    protected $fillable = [
        'name',
        'type',
        'direction',
        'is_active',
        'comment',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function carTags(): BelongsToMany
    {
        return $this->belongsToMany(
            CarTag::class,
            'car_passage_rule_car_tag',
            'car_passage_rule_id',
            'car_tag_id',
        );
    }

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(
            Person::class,
            'car_passage_rule_person',
            'car_passage_rule_id',
            'person_id',
        );
    }

    public function cars(): BelongsToMany
    {
        return $this->belongsToMany(
            Car::class,
            'car_passage_rule_car',
            'car_passage_rule_id',
            'car_id',
        );
    }

    public function passages(): BelongsToMany
    {
        return $this->belongsToMany(
            Passage::class,
            'car_passage_rule_passage',
            'car_passage_rule_id',
            'passage_id',
        );
    }

    public function hasSubjects(): bool
    {
        return $this->cars->isNotEmpty()
            || $this->people->isNotEmpty()
            || $this->carTags->isNotEmpty();
    }

    public function subjectPlates(): array
    {
        $plates = collect();

        $plates = $plates->merge($this->cars->pluck('license_plate'));

        foreach ($this->people as $person) {
            $plates = $plates->merge($person->cars->pluck('license_plate'));
        }

        foreach ($this->carTags as $tag) {
            $plates = $plates->merge($tag->cars->pluck('license_plate'));
        }

        return $plates
            ->filter()
            ->map(fn ($p) => self::normalizePlate((string) $p))
            ->unique()
            ->values()
            ->all();
    }

    public function matchesPlate(string $plate): bool
    {
        if (! $this->hasSubjects()) {
            return true;
        }

        return in_array(self::normalizePlate($plate), $this->subjectPlates(), true);
    }

    public static function normalizePlate(string $plate): string
    {
        return strtoupper(preg_replace('/\s+/', '', $plate) ?? '');
    }
}
