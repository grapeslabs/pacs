<?php

declare(strict_types=1);

namespace App\MoonShine\Fields;

use MoonShine\UI\Fields\Field;

/**
 * Мини-карточка персоны для форм (например, «Выдать ключ» из карточки персоны):
 * аватар/инициалы + ФИО + подпись. Данные не отправляет — только отображение.
 */
class PersonBindingField extends Field
{
    protected string $view = 'fields.person-binding-field';

    protected string $personName = '';
    protected string $personSubtitle = 'Ключ привяжется к этой персоне';
    protected ?string $personPhoto = null;
    protected string $personInitials = '';

    public function person(string $name, ?string $photoUrl = null, ?string $subtitle = null): static
    {
        $this->personName = $name;
        $this->personPhoto = $photoUrl;

        if ($subtitle !== null) {
            $this->personSubtitle = $subtitle;
        }

        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }
        $this->personInitials = $initials;

        return $this;
    }

    protected function viewData(): array
    {
        return [
            'name'     => $this->personName,
            'subtitle' => $this->personSubtitle,
            'photoUrl' => $this->personPhoto,
            'initials' => $this->personInitials,
        ];
    }
}
