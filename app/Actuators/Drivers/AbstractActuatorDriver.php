<?php

namespace App\Actuators\Drivers;

use App\Actuators\Contracts\ActuatorDriver;
use App\Models\ActuatorDevice;
use MoonShine\Contracts\UI\FieldContract;

abstract class AbstractActuatorDriver implements ActuatorDriver
{
    public function capabilities(): array
    {
        return ['open', 'close'];
    }

    public function test(ActuatorDevice $device): bool
    {
        return true;
    }

    abstract protected function rawRules(): array;

    public function rules(): array
    {
        $rules = [];

        foreach ($this->rawRules() as $key => $rule) {
            $rules[$this->formColumn($key)] = $rule;
        }

        return $rules;
    }

    protected function formColumn(string $key): string
    {
        $prefix = preg_replace('/[^a-z0-9]+/i', '_', static::key());

        return $prefix . '_' . $key;
    }

    protected function setting(ActuatorDevice $device, string $key, mixed $default = null): mixed
    {
        return data_get($device->settings ?? [], $this->formColumn($key), $default);
    }

    protected function settingScalar(ActuatorDevice $device, string $key, mixed $default = null): mixed
    {
        $value = $this->setting($device, $key, $default);

        if (is_array($value)) {
            return $value[0] ?? $default;
        }

        return $value;
    }

    protected function wire(FieldContract $field): FieldContract
    {
        $column = $this->formColumn($field->getColumn());

        return $field
            ->setColumn($column)
            ->changeFill(fn(ActuatorDevice $model) => $model->getData($column))
            ->onApply(fn(ActuatorDevice $model, $value) => $model->setData($column, $value));
    }
}
