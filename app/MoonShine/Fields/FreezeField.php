<?php

declare(strict_types=1);

namespace App\MoonShine\Fields;

use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Model;
use MoonShine\UI\Fields\Field;

class FreezeField extends Field
{
    protected string $view = 'fields.freeze-field';

    protected array $reasons = [
        'Отпуск'      => 'calendar',
        'Отстранение' => 'suspension',
    ];

    protected function iconSvg(string $name): string
    {
        $path = public_path('icons/' . $name . '.svg');

        return is_file($path) ? trim((string) file_get_contents($path)) : '';
    }

    protected function viewData(): array
    {
        $item = $this->getData()?->getOriginal();

        $reasons = [];
        foreach ($this->reasons as $label => $icon) {
            $reasons[] = ['label' => $label, 'icon' => $this->iconSvg($icon)];
        }

        return [
            'element'    => $this,
            'reasons'    => $reasons,
            'reasonKeys' => array_keys($this->reasons),
            'otherIcon'  => $this->iconSvg('other'),
            'start'      => $item?->frozen_start?->format('Y-m-d'),
            'end'        => $item?->frozen_end?->format('Y-m-d'),
            'reason'     => $item?->frozen_reason,
            'enabled'    => $item?->frozen_start !== null,
        ];
    }

    public function apply(Closure $default, mixed $data): mixed
    {
        if (! $data instanceof Model) {
            return parent::apply($default, $data);
        }

        $request = request();
        $enabled = $request->boolean('frozen_enabled');

        if (! $enabled) {
            $data->frozen_start  = null;
            $data->frozen_end    = null;
            $data->frozen_reason = null;

            return $data;
        }

        $start = $request->input('frozen_start');
        $end   = $request->input('frozen_end');

        $data->frozen_start  = $start ? Carbon::parse($start)->startOfDay() : Carbon::now();
        $data->frozen_end    = $end ? Carbon::parse($end)->endOfDay() : null;
        $data->frozen_reason = $request->input('frozen_reason') ?: null;

        return $data;
    }
}
