<?php

namespace App\Support\Billing;

use App\Exceptions\PlanLimitReached;
use App\Models\PlanFeature;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

/**
 * Answers "may this company use X, and how much of it" from its plan.
 *
 * Lookups are cached per tenant and plan, so a plan change produces
 * a new cache key and the old entry simply expires.
 *
 * A company without a plan (an internal account) is not restricted.
 */
class Features
{
    private const TTL_SECONDS = 3600;

    public function enabled(Tenant $tenant, string $feature): bool
    {
        if ($tenant->plan_id === null) {
            return true;
        }

        return $this->all($tenant)[$feature]['enabled'] ?? false;
    }

    public function limit(Tenant $tenant, string $feature): ?int
    {
        return $this->all($tenant)[$feature]['limit'] ?? null;
    }

    /**
     * Throw when creating one more item would go over the plan limit.
     */
    public function ensureWithinLimit(Tenant $tenant, string $feature, int $currentCount): void
    {
        if (! $this->enabled($tenant, $feature)) {
            throw PlanLimitReached::featureMissing($feature);
        }

        $limit = $this->limit($tenant, $feature);

        if ($limit !== null && $currentCount >= $limit) {
            throw PlanLimitReached::limit($feature, $limit);
        }
    }

    /**
     * @return array<string, array{enabled: bool, limit: int|null}>
     */
    public function all(Tenant $tenant): array
    {
        if ($tenant->plan_id === null) {
            return [];
        }

        return Cache::remember(
            $this->cacheKey($tenant),
            self::TTL_SECONDS,
            fn () => PlanFeature::where('plan_id', $tenant->plan_id)
                ->get()
                ->mapWithKeys(fn (PlanFeature $f) => [$f->key => ['enabled' => $f->enabled, 'limit' => $f->limit]])
                ->all(),
        );
    }

    public function forget(Tenant $tenant): void
    {
        Cache::forget($this->cacheKey($tenant));
    }

    private function cacheKey(Tenant $tenant): string
    {
        return "tenant:{$tenant->id}:plan:{$tenant->plan_id}:features";
    }
}
