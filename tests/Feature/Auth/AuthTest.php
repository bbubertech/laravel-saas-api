<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_creates_a_company_and_its_owner(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'company_name' => 'Acme Logistics',
            'name' => 'Jane Doe',
            'email' => 'jane@acme.test',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        $response->assertCreated()->assertJsonStructure(['data' => ['access_token', 'token_type', 'expires_in']]);

        $tenant = Tenant::where('slug', 'acme-logistics')->firstOrFail();
        $owner = $tenant->roles()->where('slug', 'owner')->firstOrFail();
        $this->assertDatabaseHas('users', [
            'email' => 'jane@acme.test',
            'tenant_id' => $tenant->id,
            'role_id' => $owner->id,
        ]);
        $this->assertSame(3, $tenant->roles()->count());
    }

    public function test_registration_is_validated(): void
    {
        $this->postJson('/api/v1/auth/register', ['email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['company_name', 'name', 'email', 'password']);
    }

    public function test_user_can_log_in_and_read_their_profile(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertOk()->json('data.access_token');

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.tenant.id', $user->tenant_id);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnauthorized();
    }

    public function test_token_can_be_refreshed(): void
    {
        $token = $this->loginAs(User::factory()->create(['password' => 'secret-password']));

        $fresh = $this->withToken($token)->postJson('/api/v1/auth/refresh')->assertOk()->json('data.access_token');

        $this->assertNotSame($token, $fresh);
    }

    public function test_logout_revokes_the_token(): void
    {
        $token = $this->loginAs(User::factory()->create(['password' => 'secret-password']));

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

        // Each HTTP request in production starts with a fresh guard; do the same here.
        $this->flushHeaders();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_protected_routes_require_a_token(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->getJson('/api/v1/customers')->assertUnauthorized();
    }

    private function loginAs(User $user): string
    {
        return $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->json('data.access_token');
    }
}
