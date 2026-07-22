<?php

declare(strict_types=1);

namespace App\MoonShine\Fields;

use Closure;
use Illuminate\Contracts\Support\Renderable;
use MoonShine\UI\Fields\Field;

class DeviceField extends Field
{
    protected string $view = 'fields.device-field';

    protected Closure|string|null $textAccessor = null;

    protected Closure|string|null $subtextAccessor = null;

    protected Closure|string|null $iconValue = null;

    protected Closure|bool $deletedFlag = false;

    public function text(Closure|string $value): static
    {
        $this->textAccessor = $value;

        return $this;
    }

    public function subtext(Closure|string $value): static
    {
        $this->subtextAccessor = $value;

        return $this;
    }

    public function deviceIcon(Closure|string $value): static
    {
        $this->iconValue = $value;

        return $this;
    }

    public function deleted(Closure|bool $value): static
    {
        $this->deletedFlag = $value;

        return $this;
    }

    protected function resolveAccessor(Closure|string|null $accessor, mixed $item): ?string
    {
        if ($accessor === null) {
            return null;
        }

        if ($accessor instanceof Closure) {
            $value = $accessor($item);
        } else {
            $value = $item !== null ? data_get($item, $accessor) : null;
        }

        return $value === null || $value === '' ? null : (string) $value;
    }

    protected function buildData(): array
    {
        $item = $this->getData()?->getOriginal();

        $deleted = $this->deletedFlag instanceof Closure ? (bool) ($this->deletedFlag)($item) : $this->deletedFlag;

        $icon = null;
        if ($this->iconValue !== null) {
            $icon = $this->iconValue instanceof Closure ? ($this->iconValue)($item) : $this->iconValue;
        }

        return [
            'text' => $this->resolveAccessor($this->textAccessor, $item) ?? (string) ($this->toValue() ?? ''),
            'subtext' => $this->resolveAccessor($this->subtextAccessor, $item),
            'icon' => $icon,
            'deleted' => $deleted,
        ];
    }

    protected function resolvePreview(): Renderable|string
    {
        return view($this->view, $this->buildData());
    }

    protected function viewData(): array
    {
        return $this->buildData();
    }
}
