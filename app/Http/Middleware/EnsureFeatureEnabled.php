<?php

namespace App\Http\Middleware;

use App\Exceptions\PlanLimitReached;
use App\Support\Billing\Features;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureEnabled
{
    public function __construct(private Features $features) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $tenant = $request->user()->tenant;

        if (! $this->features->enabled($tenant, $feature)) {
            throw PlanLimitReached::featureMissing($feature);
        }

        return $next($request);
    }
}
