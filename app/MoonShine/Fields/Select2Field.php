<?php

namespace App\MoonShine\Fields;

use MoonShine\UI\Fields\Select;
use Illuminate\Database\Eloquent\Model;

final class Select2Field extends Select
{
    protected string $view = 'fields.select2-custom';
    
    protected string $column = 'tags_data';

    public function save(mixed $value): mixed
    {
        return null;
    }

    public function resolveFill(array $raw = [], mixed $casted = null, int $index = 0): static
    {
        $modelId = $raw['id'] ?? null;
        
        if ($modelId) {
            $person = \App\Models\Person::with('tags')->find($modelId);
            
            if ($person && $person->tags->count() > 0) {
                $tagIds = $person->tags->pluck('id')->toArray();
                $this->setValue($tagIds);
            } else {
                $this->setValue([]);
            }
        } else {
            $this->setValue([]);
        }
        
        return $this;
    }

    public function tags(): self
    {
        $this->attributes['data-tags'] = 'true';
        return $this;
    }

    public function createUrl(string $url): self
    {
        $this->attributes['data-create-url'] = $url;
        return $this;
    }
}