<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Authorization\DefaultRoles;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed plans and one demo company to log in with.
     */
    public function run(DefaultRoles $defaultRoles): void
    {
        $this->call(PlanSeeder::class);

        $tenant = Tenant::factory()->create([
            'name' => 'Demo Company',
            'slug' => 'demo-company',
            'plan_id' => Plan::where('slug', 'growth')->value('id'),
        ]);

        $roles = $defaultRoles->createFor($tenant);

        User::factory()->for($tenant)->create([
            'name' => 'Demo Owner',
            'email' => 'owner@demo.test',
            'role_id' => $roles['owner']->id,
        ]);

        Customer::factory()->count(40)->for($tenant)->create();
    }
}
