<?php

namespace App\MoonShine\Fields;

use App\Models\Role;
use Closure;
use MoonShine\UI\Fields\Field;
use App\Services\PermissionService;

class PermissionMatrixField extends Field
{
    protected string $view = 'fields.permission-matrix';
    protected array $tree =[];
    protected  string $roleField = 'moonshine_user_role_id';
    public function __construct(Closure|string $label, ?string $column = null, ?Closure $formatted = null)
    {
        parent::__construct($label, $column, $formatted);

        $service = app(PermissionService::class);
        $this->tree = $service->getPermissionTree();
    }

    public function roleField(string $column): static
    {
        $this->roleField = $column;
        return $this;
    }

    public function tree(): array
    {
        return $this->tree;
    }

    public function resolveValue(): mixed
    {
        $value = parent::resolveValue();

        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }

        return is_array($value) ? $value :[];
    }

    public function apply(Closure $default, mixed $data): mixed
    {
        $isCustomized = request()->input('is_customized_permissions');
        if ($isCustomized === '0') {
            $data->{$this->getColumn()} =[];
            return $data;
        }
        $requestValue = request()->input($this->getColumn(), []);
        $cleanPermissions =[];

        if (is_array($requestValue)) {
            foreach ($requestValue as $resourceClass => $actions) {
                foreach ($actions as $actionKey => $value) {
                    if ($value) {
                        $cleanPermissions[$resourceClass][$actionKey] = true;
                    }
                }
            }
        }

        $data->{$this->getColumn()} = $cleanPermissions;
        return $data;
    }

    protected function viewData(): array
    {
        $roles = Role::all()->keyBy('id')->map(function($role) {
            return is_string($role->permissions) ? json_decode($role->permissions, true) : ($role->permissions ?? []);
        })->toArray();

        return[
            'element' => $this,
            'rolePermissions' => $roles,
            'roleField' => $this->roleField,
        ];
    }
}
