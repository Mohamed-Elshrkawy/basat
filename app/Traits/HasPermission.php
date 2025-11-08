<?php

namespace App\Traits;

trait HasPermission
{
    public function permissions(): array
    {
        return $this->role?->permissions()->pluck('back_route_name')->toArray() ?? [];
    }

    public function hasRole(string $role): bool
    {
        return $this->role?->name === $role;
    }

    public function hasPermissions(string $route, ?string $method = null): bool
    {
        if (in_array($this->user_type, ['super_admin', 'super-admin'], true)) {
            return true;
        }

        if (!$this->role) {
            return false;
        }

        $query = $this->role->permissions();

        if ($method) {
            return $query->where('route_name', "{$route}.{$method}")->exists();
        }

        $crud = ['index', 'store', 'update', 'destroy', 'show', 'wallet'];

        return $query->whereIn(
            'route_name',
            array_map(fn ($perm) => "{$route}.{$perm}", $crud)
        )->exists();
    }

    public function getPermissions(array $permissions): array
    {
        $grouped = [];

        foreach ($permissions as $permission) {
            if (!str_contains($permission, '.')) {
                $grouped[$permission][] = '*';
                continue;
            }

            [$resource, $action] = explode('.', $permission, 2);
            $grouped[$resource][] = $action;
        }

        return $grouped;
    }

}
