<?php

namespace App\Support\Authorization;

use App\Models\Role;
use App\Models\Tenant;

class DefaultRoles
{
    /**
     * Create the built-in roles for a new company.
     *
     * @return array<string, Role> keyed by slug
     */
    public function createFor(Tenant $tenant): array
    {
        $roles = [];

        foreach (config('permissions.default_roles') as $slug => $definition) {
            $roles[$slug] = Role::create([
                'tenant_id' => $tenant->id,
                'slug' => $slug,
                'name' => $definition['name'],
                'permissions' => $definition['permissions'],
                'is_system' => true,
            ]);
        }

        return $roles;
    }
}
