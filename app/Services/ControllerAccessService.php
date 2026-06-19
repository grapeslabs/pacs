<?php

namespace App\Services;

use App\Models\GrapeslabsSkudController;
use App\Models\Key;
use App\Models\Person;
use App\Models\PersonPassageRule;
use Illuminate\Support\Collection;

class ControllerAccessService
{
    private const FACE_TYPE = 'pinterm';
    private const NON_RFID_TYPES = ['pingate', 'pinterm'];
    private ?Collection $rulesCache = null;
    private ?Collection $managedIdsCache = null;
    private ?Collection $rfidControllersCache = null;
    private ?Collection $faceControllersCache = null;

    public function flush(): void
    {
        $this->rulesCache = null;
        $this->managedIdsCache = null;
        $this->rfidControllersCache = null;
        $this->faceControllersCache = null;
    }

    public function rfidControllersForKey(Key $key): Collection
    {
        $key->loadMissing('person.tags');

        return $this->resolve(
            fn (PersonPassageRule $rule) => $rule->matchesKey($key),
            isFace: false,
        );
    }

    public function faceControllersForPerson(Person $person): Collection
    {
        $person->loadMissing('tags');

        return $this->resolve(
            fn (PersonPassageRule $rule) => $rule->matchesPersonFace($person),
            isFace: true,
        );
    }

    public function rfidCardCommandsFor(Person $person): array
    {
        $person->loadMissing('keys');
        $map = [];

        foreach ($person->keys as $key) {
            foreach ($this->rfidControllersForKey($key) as $controller) {
                $map[$controller->id][] = ['card' => (string) $key->key, 'flags' => 0, 'tz' => 255];
            }
        }

        return $map;
    }

    public function rfidCardDeletesFor(Person $person): array
    {
        $person->loadMissing('keys');
        $map = [];

        foreach ($person->keys as $key) {
            foreach ($this->rfidControllersForKey($key) as $controller) {
                $map[$controller->id][] = ['card' => (string) $key->key];
            }
        }

        return $map;
    }

    private function resolve(callable $matches, bool $isFace): Collection
    {
        $controllers = $this->controllersByType($isFace);
        $rules = $this->rules();
        $managedIds = $this->managedIds();

        return $controllers->filter(function (GrapeslabsSkudController $controller) use ($rules, $managedIds, $matches) {
            if (! $managedIds->contains($controller->id)) {
                return true;
            }

            $rulesForController = $rules->filter(
                fn (PersonPassageRule $rule) => $rule->controllers->contains('id', $controller->id)
            );

            $denied = $rulesForController
                ->where('type', PersonPassageRule::TYPE_DENY)
                ->contains(fn (PersonPassageRule $rule) => $matches($rule));

            if ($denied) {
                return false;
            }

            return $rulesForController
                ->where('type', PersonPassageRule::TYPE_ALLOW)
                ->contains(fn (PersonPassageRule $rule) => $matches($rule));
        })->values();
    }

    private function rules(): Collection
    {
        return $this->rulesCache ??= PersonPassageRule::query()
            ->where('is_active', true)
            ->with(['keys:id,person_id', 'people:id', 'personTags:id', 'controllers:id,type'])
            ->get();
    }

    private function managedIds(): Collection
    {
        return $this->managedIdsCache ??= $this->rules()
            ->flatMap(fn (PersonPassageRule $rule) => $rule->controllers->pluck('id'))
            ->unique()
            ->values();
    }

    private function controllersByType(bool $isFace): Collection
    {
        if ($isFace) {
            return $this->faceControllersCache ??= GrapeslabsSkudController::query()
                ->where('type', self::FACE_TYPE)->get();
        }

        return $this->rfidControllersCache ??= GrapeslabsSkudController::query()
            ->whereNotIn('type', self::NON_RFID_TYPES)->get();
    }
}
