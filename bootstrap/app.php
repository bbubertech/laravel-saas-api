<?php

use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\SetTenantFromUser;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [ForceJsonResponse::class]);

        $middleware->alias([
            'tenant' => SetTenantFromUser::class,
        ]);

        // The tenant has to be known before route model binding runs,
        // otherwise {customer} would resolve without the tenant scope.
        $middleware->prependToPriorityList(SubstituteBindings::class, SetTenantFromUser::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn () => true);
    })->create();
