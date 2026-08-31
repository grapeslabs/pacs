<?php

declare(strict_types=1);

namespace App\MoonShine\Fields;

use Closure;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Storage;
use MoonShine\UI\Fields\Field;

class AvatarField extends Field
{
    protected string $view = 'fields.avatar-field';

    protected Closure|string|null $textAccessor = null;

    protected Closure|string|null $subtextAccessor = null;

    protected Closure|string|null $photoAccessor = null;

    protected Closure|string|null $iconValue = null;

    protected Closure|string|null $badgeValue = null;

    protected Closure|bool $deletedFlag = false;

    protected bool $hideAvatar = false;

    protected string $disk = 'public';

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

    public function photo(Closure|string $value): static
    {
        $this->photoAccessor = $value;

        return $this;
    }

    public function icon(Closure|string $svg): static
    {
        $this->iconValue = $svg;

        return $this;
    }

    /**
     * Маленький значок-индикатор рядом с заголовком (напр. снежинка при заморозке).
     * Closure получает модель строки и должен вернуть SVG-строку либо null.
     */
    public function indicator(Closure|string $svg): static
    {
        $this->badgeValue = $svg;

        return $this;
    }

    /** Пометка «удалено» (оборудование/камера пропали из системы): зачёркивание + красный значок. */
    public function deleted(Closure|bool $value): static
    {
        $this->deletedFlag = $value;

        return $this;
    }

    /** Не показывать кружок-аватар — только текст и подпись (двухстрочная ячейка). */
    public function hideAvatar(bool $value = true): static
    {
        $this->hideAvatar = $value;

        return $this;
    }

    public function disk(string $disk): static
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * Строка = имя атрибута модели, Closure = вычисляемое значение (получает модель строки).
     */
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

    protected function resolvePhotoUrl(mixed $item): ?string
    {
        $value = $this->resolveAccessor($this->photoAccessor, $item);

        if ($value === null) {
            return null;
        }

        // Готовый URL или абсолютный путь оставляем как есть, иначе — берём из Storage.
        if (str_contains($value, '://') || str_starts_with($value, '/')) {
            return $value;
        }

        return Storage::disk($this->disk)->url($value);
    }

    protected function resolveInitials(?string $text): string
    {
        if ($text === null || trim($text) === '') {
            return '';
        }

        $parts = preg_split('/\s+/', trim($text)) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $initials;
    }

    protected function buildData(): array
    {
        $item = $this->getData()?->getOriginal();

        $text = $this->resolveAccessor($this->textAccessor, $item)
            ?? (string) ($this->toValue() ?? '');

        $icon = null;
        if ($this->iconValue !== null) {
            $icon = $this->iconValue instanceof Closure
                ? ($this->iconValue)($item)
                : $this->iconValue;
        }

        $badge = null;
        if ($this->badgeValue !== null) {
            $badge = $this->badgeValue instanceof Closure
                ? ($this->badgeValue)($item)
                : $this->badgeValue;
        }

        $deleted = $this->deletedFlag instanceof Closure
            ? (bool) ($this->deletedFlag)($item)
            : $this->deletedFlag;

        return [
            'text' => $text,
            'subtext' => $this->resolveAccessor($this->subtextAccessor, $item),
            'photoUrl' => $deleted ? null : $this->resolvePhotoUrl($item),
            'icon' => $icon,
            'badge' => $badge,
            'initials' => $this->resolveInitials($text),
            'deleted' => $deleted,
            'hideAvatar' => $this->hideAvatar,
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
