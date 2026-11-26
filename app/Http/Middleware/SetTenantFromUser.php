<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantFromUser
{
    public function __construct(private TenantContext $context) {}

    /**
     * Scope the rest of the request to the authenticated user's tenant.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->user()?->tenant_id;

        abort_if($tenantId === null, Response::HTTP_FORBIDDEN, 'This account is not linked to a company.');

        $this->context->set($tenantId);

        return $next($request);
    }
}
