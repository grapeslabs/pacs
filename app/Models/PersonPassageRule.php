<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonPassageRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';
    protected $table = 'person_passage_rules';

    public const TYPE_ALLOW = 'allow';
    public const TYPE_DENY  = 'deny';

    public const TYPES = [
        self::TYPE_ALLOW => 'Разрешить',
        self::TYPE_DENY  => 'Запретить',
    ];

    protected $fillable = [
        'name',
        'type',
        'is_active',
        'comment',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(
            Person::class,
            'person_passage_rule_person',
            'person_passage_rule_id',
            'person_id',
        );
    }

    public function personTags(): BelongsToMany
    {
        return $this->belongsToMany(
            Tag::class,
            'person_passage_rule_tag',
            'person_passage_rule_id',
            'tag_id',
        );
    }

    public function keys(): BelongsToMany
    {
        return $this->belongsToMany(
            Key::class,
            'person_passage_rule_key',
            'person_passage_rule_id',
            'key_id',
        );
    }

    public function controllers(): BelongsToMany
    {
        return $this->belongsToMany(
            GrapeslabsSkudController::class,
            'person_passage_rule_controller',
            'person_passage_rule_id',
            'controller_id',
        );
    }

    public function hasSubjects(): bool
    {
        return $this->keys->isNotEmpty()
            || $this->people->isNotEmpty()
            || $this->personTags->isNotEmpty();
    }

    public function matchesKey(Key $key): bool
    {
        if (! $this->hasSubjects()) {
            return true;
        }

        if ($this->keys->contains('id', $key->id)) {
            return true;
        }

        if ($key->person_id !== null && $this->people->contains('id', $key->person_id)) {
            return true;
        }

        $owner = $key->person;
        if ($owner) {
            return $this->personTags->pluck('id')
                ->intersect($owner->tags->pluck('id'))
                ->isNotEmpty();
        }

        return false;
    }

    public function matchesPersonFace(Person $person): bool
    {
        if (! $this->hasSubjects()) {
            return true;
        }

        if ($this->people->isEmpty() && $this->personTags->isEmpty()) {
            return false;
        }

        if ($this->people->contains('id', $person->id)) {
            return true;
        }

        return $this->personTags->pluck('id')
            ->intersect($person->tags->pluck('id'))
            ->isNotEmpty();
    }
}
