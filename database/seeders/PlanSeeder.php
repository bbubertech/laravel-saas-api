<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Plans and what each one unlocks. A null limit means unlimited.
     */
    public const PLANS = [
        'starter' => [
            'name' => 'Starter',
            'price_cents' => 0,
            'features' => [
                'customers' => ['enabled' => true, 'limit' => 100],
                'users' => ['enabled' => true, 'limit' => 3],
                'audit_log' => ['enabled' => false, 'limit' => null],
                'bulk_import' => ['enabled' => false, 'limit' => null],
                'webhooks' => ['enabled' => false, 'limit' => null],
            ],
        ],
        'growth' => [
            'name' => 'Growth',
            'price_cents' => 4900,
            'features' => [
                'customers' => ['enabled' => true, 'limit' => 5000],
                'users' => ['enabled' => true, 'limit' => 15],
                'audit_log' => ['enabled' => true, 'limit' => null],
                'bulk_import' => ['enabled' => true, 'limit' => null],
                'webhooks' => ['enabled' => true, 'limit' => 3],
            ],
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price_cents' => 19900,
            'features' => [
                'customers' => ['enabled' => true, 'limit' => null],
                'users' => ['enabled' => true, 'limit' => null],
                'audit_log' => ['enabled' => true, 'limit' => null],
                'bulk_import' => ['enabled' => true, 'limit' => null],
                'webhooks' => ['enabled' => true, 'limit' => null],
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::PLANS as $slug => $definition) {
            $plan = Plan::updateOrCreate(
                ['slug' => $slug],
                ['name' => $definition['name'], 'price_cents' => $definition['price_cents']],
            );

            foreach ($definition['features'] as $key => $feature) {
                $plan->features()->updateOrCreate(['key' => $key], $feature);
            }
        }
    }
}
