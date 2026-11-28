<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\User;
use App\Support\Billing\Features;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PlanController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('can:billing.manage', only: ['update'])];
    }

    /**
     * The current plan with what it allows and how much of it is used.
     */
    public function show(Request $request, Features $features): JsonResponse
    {
        $tenant = $request->user()->tenant->load('plan');
        $usage = ['customers' => Customer::count(), 'users' => User::count()];

        return response()->json([
            'data' => [
                'plan' => $tenant->plan?->only(['slug', 'name', 'price_cents', 'currency']),
                'features' => collect($features->all($tenant))
                    ->map(fn (array $feature, string $key) => $feature + ['used' => $usage[$key] ?? null])
                    ->all(),
            ],
        ]);
    }

    /**
     * Switch plans. Payment is out of scope here; this only changes what is unlocked.
     */
    public function update(Request $request, Features $features): JsonResponse
    {
        $data = $request->validate(['plan' => ['required', 'string', 'exists:plans,slug']]);

        $tenant = $request->user()->tenant;
        $features->forget($tenant);
        $tenant->update(['plan_id' => Plan::where('slug', $data['plan'])->value('id')]);

        return $this->show($request, $features);
    }
}
