<?php

namespace App\MoonShine\Fields;

use MoonShine\UI\Fields\Text;

/**
 * Поле поиска организации через Dadata с автозаполнением реквизитов формы.
 * (бывш. DadataOrganizationField)
 *
 * Какая часть ответа Dadata попадает в какое поле формы — настраивается через
 * ->map([...]) либо ->field('input_name', 'dot.path'). Ключ = name инпута формы,
 * значение = путь внутри объекта подсказки Dadata (см. $defaultMap).
 *
 * Полный список доступных путей Dadata (data.*) для маппинга:
 *   data.inn, data.kpp, data.ogrn, data.ogrn_date, data.okpo, data.okved,
 *   data.okogu, data.okfs, data.okato, data.oktmo, data.hid, data.type,
 *   data.name.full_with_opf, data.name.short_with_opf, data.name.full, data.name.short, data.name.latin,
 *   data.opf.code, data.opf.full, data.opf.short, data.opf.type,
 *   data.address.value, data.address.unrestricted_value,
 *   data.address.data.postal_code, data.address.data.region, data.address.data.city,
 *   data.management.name, data.management.post, data.management.disqualified,
 *   data.state.status, data.state.code, data.state.registration_date, data.state.liquidation_date, data.state.actuality_date,
 *   data.phones.0.value, data.emails.0.value,
 *   data.capital.value, data.employee_count, data.branch_count,
 *   value, unrestricted_value
 */
final class OrganizationField extends Text
{
    protected string $view = 'fields.organization-field';

    /** Маппинг «name инпута формы» => «путь в подсказке Dadata». */
    protected array $fieldMap = [
        'inn'              => 'data.inn',
        'kpp'              => 'data.kpp',
        'ogrn'             => 'data.ogrn',
        'full_name'        => 'data.name.full_with_opf',
        'short_name'       => 'data.name.short_with_opf',
        'address'          => 'data.address.unrestricted_value',
        'contact_name'     => 'data.management.name',
        'contact_position' => 'data.management.post',
        'phone'            => 'data.phones.0.value',
    ];

    public function __construct(string $label, ?string $column = null)
    {
        parent::__construct($label, $column);

        $this->customAttributes([
            'data-dadata-field' => 'true',
            'autocomplete' => 'off',
        ]);
    }

    /**
     * Задать/дополнить маппинг целиком. Переданные ключи перезаписывают дефолтные.
     */
    public function map(array $map): static
    {
        $this->fieldMap = array_merge($this->fieldMap, $map);

        return $this;
    }

    /**
     * Задать один инпут: ->field('kpp', 'data.kpp').
     */
    public function field(string $input, string $dataPath): static
    {
        $this->fieldMap[$input] = $dataPath;

        return $this;
    }

    /**
     * Полностью заменить маппинг (в т.ч. отключить дефолт).
     */
    public function setMap(array $map): static
    {
        $this->fieldMap = $map;

        return $this;
    }

    public function isNowOnForm(): bool
    {
        return false;
    }

    public function save(mixed $value): mixed
    {
        return null;
    }

    public function resolveFill(array $raw = [], mixed $casted = null, int $index = 0): static
    {
        $this->setValue('');

        return $this;
    }

    protected function viewData(): array
    {
        return [
            'element'  => $this,
            'fieldMap' => $this->fieldMap,
        ];
    }
}
