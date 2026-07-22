<?php

declare(strict_types=1);

namespace App\MoonShine\Fields;

use Illuminate\Database\Eloquent\Model;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\UI\Fields\Field;

/**
 * Поле «Содержимое уведомления» триггера с двумя режимами:
 *  - чекбоксы (какие данные включить в сообщение);
 *  - шаблон сообщения с плейсхолдерами {…}.
 *
 * Пишет в JSON-колонку `data`: булевы флаги по ключам + `mode` (fields|template) + `template`.
 */
class TriggerContentField extends Field
{
    protected string $view = 'fields.trigger-content-field';

    /** Ключ в data => подпись/плейсхолдер. */
    public const OPTIONS = [
        'date'      => 'Дата',
        'time'      => 'Время',
        'stream'    => 'Видеопоток',
        'lfm'       => 'ФИО',
        'person_id' => 'ID персоны',
        'comment'   => 'Комментарий',
        'photobank' => 'Фото из фотобанка',
        'photo'     => 'Фото с камеры',
    ];

    protected function viewData(): array
    {
        $data = $this->toValue();
        if (! is_array($data)) {
            $data = [];
        }

        $values = [];
        foreach (array_keys(self::OPTIONS) as $key) {
            $values[$key] = ! empty($data[$key]);
        }

        return [
            'options'  => self::OPTIONS,
            'values'   => $values,
            'mode'     => (($data['mode'] ?? 'fields') === 'template') ? 'template' : 'fields',
            'template' => (string) ($data['template'] ?? ''),
            'column'   => $this->getColumn(),
        ];
    }

    public function resolveFill(array $raw = [], ?DataWrapperContract $casted = null, int $index = 0): static
    {
        parent::resolveFill($raw, $casted, $index);

        $item = $casted?->getOriginal();
        $this->setValue($item instanceof Model && $item->exists && is_array($item->data) ? $item->data : []);

        return $this;
    }

    /** Нормализованный массив data из запроса (для onApply в ресурсе). */
    public static function normalize(mixed $raw): array
    {
        $raw = is_array($raw) ? $raw : [];

        $data = [];
        foreach (array_keys(self::OPTIONS) as $key) {
            $data[$key] = ! empty($raw[$key]) && $raw[$key] !== '0';
        }
        $data['mode']     = (($raw['mode'] ?? 'fields') === 'template') ? 'template' : 'fields';
        $data['template'] = (string) ($raw['template'] ?? '');

        return $data;
    }
}
