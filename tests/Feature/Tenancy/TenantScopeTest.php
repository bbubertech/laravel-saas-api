<?php

namespace Tests\Feature\Tenancy;

use App\Models\Customer;
use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_queries_only_return_rows_of_the_current_tenant(): void
    {
        [$acme, $globex] = Tenant::factory()->count(2)->create();
        Customer::factory()->count(3)->for($acme)->create();
        Customer::factory()->count(2)->for($globex)->create();

        app(TenantContext::class)->set($acme->id);

        $this->assertSame(3, Customer::count());
        $this->assertTrue(Customer::all()->every(fn (Customer $c) => $c->tenant_id === $acme->id));
    }

    public function test_a_record_of_another_tenant_cannot_be_found_by_id(): void
    {
        [$acme, $globex] = Tenant::factory()->count(2)->create();
        $foreign = Customer::factory()->for($globex)->create();

        app(TenantContext::class)->set($acme->id);

        $this->assertNull(Customer::find($foreign->id));
    }

    public function test_tenant_id_is_filled_from_the_context_on_create(): void
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->set($tenant->id);

        $customer = Customer::create(['name' => 'Jane Doe']);

        $this->assertSame($tenant->id, $customer->tenant_id);
    }

    public function test_context_can_switch_tenant_temporarily(): void
    {
        [$acme, $globex] = Tenant::factory()->count(2)->create();
        Customer::factory()->for($globex)->create();
        $context = app(TenantContext::class);
        $context->set($acme->id);

        $count = $context->run($globex->id, fn () => Customer::count());

        $this->assertSame(1, $count);
        $this->assertSame($acme->id, $context->id());
    }

    public function test_without_a_tenant_context_the_scope_is_not_applied(): void
    {
        Customer::factory()->count(2)->create();

        $this->assertSame(2, Customer::count());
    }
}
