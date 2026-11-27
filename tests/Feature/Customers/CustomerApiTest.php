<?php

namespace Tests\Feature\Customers;

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->signIn('owner');
    }

    public function test_index_lists_only_own_customers(): void
    {
        Customer::factory()->count(3)->for($this->user->tenant)->create();
        Customer::factory()->count(2)->for(Tenant::factory())->create();

        $this->getJson('/api/v1/customers')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_index_can_be_searched(): void
    {
        Customer::factory()->for($this->user->tenant)->create(['name' => 'Ada Lovelace']);
        Customer::factory()->for($this->user->tenant)->create(['name' => 'Alan Turing']);

        $this->getJson('/api/v1/customers?search=ada')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Ada Lovelace');
    }

    public function test_customer_can_be_created(): void
    {
        $this->postJson('/api/v1/customers', [
            'name' => 'Grace Hopper',
            'email' => 'grace@example.test',
        ])->assertCreated()->assertJsonPath('data.name', 'Grace Hopper');

        $this->assertDatabaseHas('customers', [
            'email' => 'grace@example.test',
            'tenant_id' => $this->user->tenant_id,
        ]);
    }

    public function test_email_must_be_unique_within_the_tenant_only(): void
    {
        Customer::factory()->for($this->user->tenant)->create(['email' => 'taken@example.test']);
        Customer::factory()->for(Tenant::factory())->create(['email' => 'other@example.test']);

        $this->postJson('/api/v1/customers', ['name' => 'A', 'email' => 'taken@example.test'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->postJson('/api/v1/customers', ['name' => 'B', 'email' => 'other@example.test'])
            ->assertCreated();
    }

    public function test_customer_can_be_updated_and_deleted(): void
    {
        $customer = Customer::factory()->for($this->user->tenant)->create();

        $this->patchJson("/api/v1/customers/{$customer->id}", ['company' => 'Initech'])
            ->assertOk()
            ->assertJsonPath('data.company', 'Initech');

        $this->deleteJson("/api/v1/customers/{$customer->id}")->assertNoContent();
        $this->assertModelMissing($customer);
    }

    public function test_members_cannot_delete_customers(): void
    {
        $member = $this->signIn('member', $this->user->tenant);
        $customer = Customer::factory()->for($member->tenant)->create();

        $this->deleteJson("/api/v1/customers/{$customer->id}")->assertForbidden();
        $this->getJson('/api/v1/customers')->assertOk();
    }

    public function test_customers_of_another_tenant_are_not_reachable(): void
    {
        $foreign = Customer::factory()->for(Tenant::factory())->create();

        $this->getJson("/api/v1/customers/{$foreign->id}")->assertNotFound();
        $this->patchJson("/api/v1/customers/{$foreign->id}", ['name' => 'x'])->assertNotFound();
        $this->deleteJson("/api/v1/customers/{$foreign->id}")->assertNotFound();
    }
}
