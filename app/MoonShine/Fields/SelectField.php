<?php

namespace App\MoonShine\Fields;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\UI\Fields\Field;

class SelectField extends Field
{
    protected string $view = 'fields.select-field';

    protected string $createUrl = '';
    protected array $options = [];
    protected bool $isCreatable = false;
    protected bool $isMultiple = false;
    protected string $placeholder = '';
    protected mixed $defaultValue = null;

    public function default(mixed $value): static
    {
        $this->defaultValue = $value;
        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function creatable(bool $condition = true, ?string $createUrl = null): static
    {
        $this->isCreatable = $condition;
        if ($createUrl) {
            $this->createUrl = $createUrl;
        }
        return $this;
    }

    public function multiple(bool $condition = true): static
    {
        $this->isMultiple = $condition;
        return $this;
    }

    public function options(mixed $options): static
    {
        $collection = collect($options);

        if ($collection->isEmpty()) {
            $this->options = [];
            return $this;
        }

        $first = $collection->first();

        if ($first instanceof \Illuminate\Database\Eloquent\Model) {
            $this->options = $collection->map(fn($m) => [
                'id'   => $m->getKey(),
                'name' => $m->getAttribute('name') ?? (string) $m->getKey(),
            ])->values()->toArray();
        } elseif (is_array($first) && array_key_exists('id', $first) && array_key_exists('name', $first)) {
            $this->options = $collection->values()->toArray();
        } else {
            $this->options = $collection->map(fn($name, $id) => [
                'id'   => $id,
                'name' => $name,
            ])->values()->toArray();
        }

        return $this;
    }

    protected function resolveValue(): mixed
    {
        $value = parent::resolveValue();

        if (is_null($value) && ! is_null($this->defaultValue)) {
            $value = $this->defaultValue;
        }

        if ($this->isMultiple) {
            if ($value instanceof Collection) {
                return $value->modelKeys();
            }
            return is_array($value) ? $value : ($value ? [$value] : []);
        } else {
            if ($value instanceof Model) {
                return [$value->getKey()];
            }
            return $value ? [$value] : [];
        }
    }

    public function apply(Closure $default, mixed $data): mixed
    {
        $column = $this->getColumn();

        if ($this->isBelongsToManyRelation($data, $column)) {
            return $data;
        }

        return parent::apply($default, $data);
    }

    public function afterApply(mixed $data): mixed
    {
        $column = $this->getColumn();

        if ($this->isBelongsToManyRelation($data, $column)) {
            $values = $this->getRequestValue() ?: [];
            $data->{$column}()->sync($values);
        }

        return parent::afterApply($data);
    }
    protected function isBelongsToManyRelation(mixed $data, string $column): bool
    {
        return $this->isMultiple
            && $data instanceof Model
            && method_exists($data, $column)
            && $data->{$column}() instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany;
    }

    public function resolveFill(array $raw = [], ?DataWrapperContract $casted = null, int $index = 0): static
    {
        parent::resolveFill($raw, $casted, $index);
        $value = $this->toValue();
        if ($this->isMultiple && $value instanceof Collection) {
            $this->setValue($value->modelKeys());
        }
        elseif (!$this->isMultiple && $value instanceof Model) {
            $this->setValue([$value->getKey()]);
        }

        return $this;
    }

    protected function viewData(): array
    {
        return [
            'element'    => $this,
            'createUrl'  => $this->createUrl,
            'options'    => $this->options,
            'selectedIds' => $this->toValue() ?? [],
            'isCreatable' => $this->isCreatable,
            'isMultiple' => $this->isMultiple,
            'placeholder' => $this->placeholder,
        ];
    }
}
