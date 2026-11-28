<?php

namespace Tests\Feature\Billing;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\Tenant;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PlanLimitsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanSeeder::class);
        $this->tenant = Tenant::factory()->create(['plan_id' => Plan::where('slug', 'starter')->value('id')]);
    }

    public function test_new_companies_start_on_the_starter_plan(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'company_name' => 'Fresh Co',
            'name' => 'Owner',
            'email' => 'owner@fresh.test',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertCreated();

        $this->assertSame('starter', Tenant::where('slug', 'fresh-co')->first()->plan->slug);
    }

    public function test_creating_customers_stops_at_the_plan_limit(): void
    {
        $this->setLimit('customers', 2);
        $this->signIn('owner', $this->tenant);
        Customer::factory()->count(2)->for($this->tenant)->create();

        $this->postJson('/api/v1/customers', ['name' => 'One too many'])
            ->assertForbidden()
            ->assertJsonPath('code', 'plan_limit_reached')
            ->assertJsonPath('feature', 'customers');
    }

    public function test_feature_middleware_blocks_features_outside_the_plan(): void
    {
        Route::middleware(['api', 'auth:api', 'tenant', 'feature:bulk_import'])
            ->get('/api/v1/_test/bulk', fn () => ['ok' => true]);

        $this->signIn('owner', $this->tenant);

        $this->getJson('/api/v1/_test/bulk')
            ->assertForbidden()
            ->assertJsonPath('code', 'feature_not_in_plan');
    }

    public function test_plan_overview_shows_limits_and_usage(): void
    {
        $this->signIn('owner', $this->tenant);
        Customer::factory()->count(4)->for($this->tenant)->create();

        $this->getJson('/api/v1/billing/plan')
            ->assertOk()
            ->assertJsonPath('data.plan.slug', 'starter')
            ->assertJsonPath('data.features.customers.limit', 100)
            ->assertJsonPath('data.features.customers.used', 4)
            ->assertJsonPath('data.features.bulk_import.enabled', false);
    }

    public function test_upgrading_unlocks_features_right_away(): void
    {
        $this->signIn('owner', $this->tenant);
        $this->getJson('/api/v1/billing/plan')->assertJsonPath('data.features.bulk_import.enabled', false);

        $this->putJson('/api/v1/billing/plan', ['plan' => 'growth'])
            ->assertOk()
            ->assertJsonPath('data.plan.slug', 'growth')
            ->assertJsonPath('data.features.bulk_import.enabled', true);
    }

    public function test_only_owners_can_change_the_plan(): void
    {
        $this->signIn('admin', $this->tenant);

        $this->putJson('/api/v1/billing/plan', ['plan' => 'growth'])->assertForbidden();
    }

    private function setLimit(string $feature, int $limit): void
    {
        PlanFeature::where('plan_id', $this->tenant->plan_id)->where('key', $feature)->update(['limit' => $limit]);
    }
}
