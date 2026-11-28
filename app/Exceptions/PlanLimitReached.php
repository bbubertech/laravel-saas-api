<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class PlanLimitReached extends RuntimeException
{
    public function __construct(string $message, public readonly string $feature, public readonly string $reason)
    {
        parent::__construct($message);
    }

    public static function limit(string $feature, int $limit): self
    {
        return new self("Your plan allows up to {$limit} {$feature}. Upgrade to add more.", $feature, 'plan_limit_reached');
    }

    public static function featureMissing(string $feature): self
    {
        return new self("Your plan does not include {$feature}.", $feature, 'feature_not_in_plan');
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->reason,
            'feature' => $this->feature,
        ], Response::HTTP_FORBIDDEN);
    }
}
