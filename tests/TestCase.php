<?php

namespace Tests;

use App\Models\Tenant;
use App\Models\User;
use App\Support\Authorization\DefaultRoles;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create a user with one of the default roles, in a new or given company.
     */
    protected function userWithRole(string $role = 'owner', ?Tenant $tenant = null): User
    {
        $tenant ??= Tenant::factory()->create();
        $roles = $tenant->roles()->exists()
            ? $tenant->roles()->get()->keyBy('slug')->all()
            : app(DefaultRoles::class)->createFor($tenant);

        return User::factory()->for($tenant)->create(['role_id' => $roles[$role]->id]);
    }

    protected function signIn(string $role = 'owner', ?Tenant $tenant = null): User
    {
        $user = $this->userWithRole($role, $tenant);
        $this->actingAs($user, 'api');

        return $user;
    }
}
