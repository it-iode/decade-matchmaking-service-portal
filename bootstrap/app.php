<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Domain modules live under app/Domains/<Domain>/ and app/Infrastructure/<Name>/.
    // Laravel only auto-discovers app/Listeners and app/Console/Commands, so the
    // per-module Listeners/ and Console/ folders are registered here.
    ->withEvents(discover: [
        ...(glob(app_path('Domains/*/Listeners')) ?: []),
        ...(glob(app_path('Infrastructure/*/Listeners')) ?: []),
    ])
    ->withCommands([
        app_path('Console/Commands'),
        ...(glob(app_path('Domains/*/Console')) ?: []),
        ...(glob(app_path('Infrastructure/*/Console')) ?: []),
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
        $middleware->trustProxies(at: '*');
        $middleware->redirectGuestsTo(fn (Request $request) => route('sign.in'));
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (UnauthorizedException $e, Request $request) {

            // Handle API requests with JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Access denied. Please contact administrator for the required role(s).',
                    'required_roles' => $e->getRequiredRoles(),
                    'contact_email' => 'cdf@unesco.org',
                ], 403);
            }

            // Handle web requests with Inertia page
            return Inertia::render('auth/AccessDenied', [
                'requiredRoles' => $e->getRequiredRoles(),
                'contactEmail' => 'cdf@unesco.org',
                'attemptedRoute' => $request->route()?->getName(),
            ])->toResponse($request)->setStatusCode(403);
        });
    })->create();
