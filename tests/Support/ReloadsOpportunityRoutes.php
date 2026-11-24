<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * routes/web.php loads sub-route files with require_once, so PHP's include-once
 * tracking prevents them from being re-loaded when the application is
 * re-bootstrapped between tests. Re-require the opportunity routes into the
 * fresh router when a named route is missing, then refresh the lookups so
 * route('...') resolves regardless of test execution order.
 */
trait ReloadsOpportunityRoutes
{
    protected function reloadOpportunityRoutes(string $routeName = 'opportunity.submit'): void
    {
        $router = $this->app->make('router');

        if ($router->has($routeName)) {
            return;
        }

        require base_path('routes/opportunity.php');
        $router->getRoutes()->refreshNameLookups();
        $router->getRoutes()->refreshActionLookups();
    }
}
