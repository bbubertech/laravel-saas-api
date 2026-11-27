<?php

namespace Tests\Feature\Users;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_a_user_with_a_role(): void
    {
        $admin = $this->signIn('admin');
        $memberRole = Role::where('tenant_id', $admin->tenant_id)->where('slug', 'member')->firstOrFail();

        $this->postJson('/api/v1/users', [
            'name' => 'New Hire',
            'email' => 'new@acme.test',
            'password' => 'secret-password',
            'role_id' => $memberRole->id,
        ])->assertCreated()->assertJsonPath('data.role.slug', 'member');

        $this->assertDatabaseHas('users', ['email' => 'new@acme.test', 'tenant_id' => $admin->tenant_id]);
    }

    public function test_role_of_another_company_cannot_be_assigned(): void
    {
        $this->signIn('owner');
        $foreignRole = $this->userWithRole('owner', Tenant::factory()->create())->role_id;

        $this->postJson('/api/v1/users', [
            'name' => 'Intruder',
            'email' => 'x@acme.test',
            'password' => 'secret-password',
            'role_id' => $foreignRole,
        ])->assertUnprocessable()->assertJsonValidationErrors('role_id');
    }

    public function test_members_cannot_manage_users(): void
    {
        $member = $this->signIn('member');

        $this->getJson('/api/v1/users')->assertForbidden();
        $this->postJson('/api/v1/users', [])->assertForbidden();
        $this->assertFalse($member->can('users.manage'));
    }

    public function test_the_last_owner_cannot_be_demoted_or_deleted(): void
    {
        $owner = $this->signIn('owner');
        $admin = $this->userWithRole('admin', $owner->tenant);
        $adminRole = $admin->role_id;
        $this->actingAs($admin, 'api');

        $this->patchJson("/api/v1/users/{$owner->id}", ['role_id' => $adminRole])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role_id');

        $this->deleteJson("/api/v1/users/{$owner->id}")->assertUnprocessable();
    }

    public function test_users_cannot_delete_themselves(): void
    {
        $owner = $this->signIn('owner');

        $this->deleteJson("/api/v1/users/{$owner->id}")->assertUnprocessable();
    }

    public function test_users_of_another_company_are_not_listed(): void
    {
        $owner = $this->signIn('owner');
        $this->userWithRole('member', $owner->tenant);
        User::factory()->count(3)->create();

        $this->getJson('/api/v1/users')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_roles_are_listed_with_permissions(): void
    {
        $this->signIn('admin');

        $this->getJson('/api/v1/roles')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.slug', 'owner');
    }
}
