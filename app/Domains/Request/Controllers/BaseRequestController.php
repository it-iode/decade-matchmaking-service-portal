<?php

declare(strict_types=1);

namespace App\Domains\Request\Controllers;

use App\Domains\Request\Actions\RequestActionProvider;
use App\Domains\Request\Models\Request as OCDRequest;
use App\Domains\Request\Services\RequestContextService;
use App\Domains\User\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

abstract class BaseRequestController extends Controller
{
    public function __construct(
        protected readonly RequestContextService $contextService,
        protected readonly ?RequestActionProvider $actionProvider = null
    ) {}

    /**
     * Get actions for a request with optional exclusions.
     *
     * @param  OCDRequest  $request  The request entity
     * @param  User|null  $user  The current user
     * @param  string  $context  The UI context
     * @param  array<string>  $exclude  Action keys to exclude
     * @return array<int, array<string, mixed>>
     */
    protected function getActions(
        OCDRequest $request,
        ?User $user,
        string $context,
        array $exclude = []
    ): array {
        $actions = $this->actionProvider->getActions($request, $user, $context);

        if (empty($exclude)) {
            return $actions;
        }

        return array_values(
            array_filter(
                $actions,
                fn (array $action) => ! in_array($action['key'], $exclude, true)
            )
        );
    }

    /**
     * Get the context based on route name.
     * Supports both list and detail (show) route names.
     */
    protected function getRouteContext(): string
    {
        $routeName = request()->route()?->getName() ?? '';

        // Admin context (both list and show routes)
        if (str_starts_with($routeName, 'admin.')) {
            return RequestContextService::CONTEXT_ADMIN;
        }

        // User own requests (both list and show)
        if ($routeName === 'request.me.list' || $routeName === 'request.me.show') {
            return RequestContextService::CONTEXT_USER_OWN;
        }

        // Matched requests (both list and show)
        if ($routeName === 'request.me.matched-requests' || $routeName === 'request.matched.show') {
            return RequestContextService::CONTEXT_MATCHED;
        }

        // Subscribed requests (both list and show)
        if ($routeName === 'request.me.subscribed-requests' || $routeName === 'request.subscribed.show') {
            return RequestContextService::CONTEXT_SUBSCRIBED;
        }

        // Public show route
        if ($routeName === 'request.public.show') {
            return RequestContextService::CONTEXT_PUBLIC;
        }

        // Public list route (partners viewing all requests)
        if ($routeName === 'request.list') {
            return RequestContextService::CONTEXT_PUBLIC;
        }

        // Default fallback
        return RequestContextService::CONTEXT_PUBLIC;
    }

    /**
     * Build search filters from request based on field configurations.
     */
    protected function buildSearchFilters(Request $request, array $fields): array
    {
        $filters = [];

        foreach ($fields as $field) {
            if (! is_array($field) || ! isset($field['name'])) {
                continue;
            }

            $fieldName = $field['name'];
            $value = $request->get($fieldName);

            if ($value !== null && $value !== '') {
                $filters[$fieldName] = $value;
            }
        }

        return $filters;
    }

    /**
     * Build sort filters with defaults
     */
    protected function buildSortFilters(
        Request $request,
        string $defaultField = 'created_at',
        string $defaultOrder = 'desc',
        int $perPage = 10
    ): array {
        return [
            'field' => $request->get('sort', $defaultField),
            'order' => $request->get('order', $defaultOrder),
            'per_page' => (int) $request->get('per_page', $perPage),
        ];
    }

    /**
     * Build current search array based on context
     */
    protected function buildCurrentSearch(array $searchFilters, array $fields): array
    {
        $currentSearch = [];
        foreach ($fields as $field) {
            $currentSearch[$field] = $searchFilters[$field] ?? '';
        }

        return $currentSearch;
    }
}
