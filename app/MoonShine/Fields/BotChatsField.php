<?php

declare(strict_types=1);

namespace App\MoonShine\Fields;

use Closure;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use Illuminate\Database\Eloquent\Model;
use MoonShine\UI\Fields\Field;

class BotChatsField extends Field
{
    protected string $view = 'fields.bot-chats-field';
    protected string $relation = 'chats';
    protected string $serviceFieldSelector = '';
    protected string $tokenFieldSelector = '';
    protected string $apiUrlFieldSelector = '';

    public function __construct(string|Closure|null $label = null, ?string $column = null, ?Closure $formatted = null)
    {
        parent::__construct($label, $column, $formatted);
    }

    public function relation(string $relation): self
    {
        $this->relation = $relation;
        return $this;
    }

    public function serviceField(string $selector): self
    {
        $this->serviceFieldSelector = $selector;
        return $this;
    }

    public function tokenField(string $selector): self
    {
        $this->tokenFieldSelector = $selector;
        return $this;
    }

    public function apiUrlField(string $selector): self
    {
        $this->apiUrlFieldSelector = $selector;
        return $this;
    }

    protected function viewData(): array
    {
        return [
            'element' => $this,
            'serviceFieldSelector' => $this->serviceFieldSelector,
            'tokenFieldSelector' => $this->tokenFieldSelector,
            'apiUrlFieldSelector' => $this->apiUrlFieldSelector,
        ];
    }

    public function resolveFill(array $raw = [], ?DataWrapperContract $casted = null, int $index = 0): static
    {
        parent::resolveFill($raw, $casted, $index);
        $item = $casted?->getOriginal();
        if ($item instanceof Model && $item->exists) {
            if (!$item->relationLoaded($this->relation)) {
                $item->load($this->relation);
            }

            $items = $item->{$this->relation};

            if ($items) {
                $data = collect($items)->map(function ($chat) {
                    return [
                        'chat_id' => $chat->chat_id,
                        'name'    => $chat->name ?? '',
                    ];
                })->values()->toArray();
                $this->setValue($data);
            } else {
                $this->setValue([]);
            }
        } else {
            $this->setValue([]);
        }

        return $this;
    }
}
