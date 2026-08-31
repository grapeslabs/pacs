<?php

declare(strict_types=1);

namespace App\MoonShine\Fields;

use Closure;
use Illuminate\Contracts\Support\Renderable;
use MoonShine\UI\Fields\Field;

/**
 * Ячейка таблицы «иконка + текст» (колонка «Камера» в отчётах).
 * Если запись помечена удалённой — иконка меняется на error.svg.
 */
class IconTextField extends Field
{
    protected string $view = 'fields.icon-text-field';

    protected Closure|string|null $textAccessor = null;

    protected Closure|bool $deletedFlag = false;

    protected ?string $iconFile = 'camera';

    public function text(Closure|string $value): static
    {
        $this->textAccessor = $value;

        return $this;
    }

    /** Имя svg-файла из public/icons без расширения (по умолчанию camera). */
    public function iconName(string $name): static
    {
        $this->iconFile = $name;

        return $this;
    }

    /** Пометка «удалено» — иконка меняется на error.svg. */
    public function deleted(Closure|bool $value): static
    {
        $this->deletedFlag = $value;

        return $this;
    }

    protected function buildData(): array
    {
        $item = $this->getData()?->getOriginal();

        if ($this->textAccessor instanceof Closure) {
            $text = (string) (($this->textAccessor)($item) ?? '');
        } elseif ($this->textAccessor !== null) {
            $text = (string) ($item !== null ? data_get($item, $this->textAccessor) : '');
        } else {
            $text = (string) ($this->toValue() ?? '');
        }

        $deleted = $this->deletedFlag instanceof Closure
            ? (bool) ($this->deletedFlag)($item)
            : $this->deletedFlag;

        $iconPath = public_path('icons/' . ($deleted ? 'error' : $this->iconFile) . '.svg');

        return [
            'text' => $text,
            'deleted' => $deleted,
            'icon' => is_file($iconPath) ? (string) file_get_contents($iconPath) : '',
        ];
    }

    /**
     * Index-таблицы и detail рендерятся через preview, а не через $view.
     */
    protected function resolvePreview(): Renderable|string
    {
        return view($this->view, $this->buildData());
    }

    protected function viewData(): array
    {
        return $this->buildData();
    }
}
