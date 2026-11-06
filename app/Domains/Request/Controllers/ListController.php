<?php

declare(strict_types=1);

namespace App\Domains\Request\Controllers;

use App\Domains\Request\Actions\RequestActionProvider;
use App\Domains\Request\Enums\PublicRequestStatus;
use App\Domains\Request\Resources\PublicRequestResource;
use App\Domains\Request\Resources\RequestResource;
use App\Domains\Request\Services\RequestContextService;
use App\Domains\Request\Services\RequestService;
use App\Shared\Http\HasPageActions;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ListController extends BaseRequestController
{
    use HasPageActions;

    public function __construct(
        private readonly RequestService $service,
        RequestActionProvider $actionProvider,
        RequestContextService $contextService
    ) {
        parent::__construct($contextService, $actionProvider);
    }

    /**
     * Get context-specific configuration
     */
    private function getContextConfiguration(string $context): array
    {
        return match ($context) {
            RequestContextService::CONTEXT_ADMIN => [
                'component' => $this->getViewPrefix().'request/List',
                'title' => 'Requests',
                'searchFields' => [
                    ['name' => 'user', 'label' => 'User', 'type' => 'text'],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                ],
                'currentSearchFields' => ['user', 'title'],
                'listRouteName' => 'admin.request.list',
                'showRouteName' => 'admin.request.show',
                'pageActions' => $this->buildActions([
                    $this->createSecondaryAction(
                        'Export CSV',
                        route('admin.request.export.csv'),
                        'ArrowDownTrayIcon',
                        'DOWNLOAD'),
                ]),
                'resourceClass' => RequestResource::class,
                'serviceMethod' => 'getPaginatedRequests',
                'additionalData' => ['availableStatuses' => $this->service->getAvailableStatuses()],
                'context' => $context,
            ],
            RequestContextService::CONTEXT_USER_OWN => [
                'component' => 'request/List',
                'title' => 'My requests',
                'banner' => [
                    'title' => 'List of my requests',
                    'description' => 'Manage your requests here.',
                ],
                'searchFields' => [
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                ],
                'currentSearchFields' => ['title'],
                'listRouteName' => 'request.me.list',
                'showRouteName' => 'request.show',
                'routeName' => 'request.me.list',
                'pageActions' => $this->buildActions([
                    $this->createPrimaryAction('Submit new request', route('request.create'), 'PlusIcon'),
                ]),
                'resourceClass' => RequestResource::class,
                'serviceMethod' => 'getUserRequests',
                'requiresUser' => true,
                'context' => $context,
            ],
            RequestContextService::CONTEXT_PUBLIC => [
                'component' => 'request/List',
                'title' => 'View Request for Training workshops',
                'banner' => [
                    'title' => 'View Request for Training workshops',
                    'description' => 'View requests for training and workshops.',
                ],
                'searchFields' => [
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                    ['name' => 'public_status', 'label' => 'Status', 'type' => 'select', 'options' => PublicRequestStatus::getOptions()],
                ],
                'currentSearchFields' => ['title', 'public_status'],
                'listRouteName' => 'request.list',
                'showRouteName' => 'public.request.show',
                'routeName' => 'request.list',
                'resourceClass' => PublicRequestResource::class,
                'serviceMethod' => 'getPublicRequests',
                'context' => $context,
            ],
            RequestContextService::CONTEXT_MATCHED => [
                'component' => 'request/List',
                'title' => 'View my matched requests',
                'banner' => [
                    'title' => 'View my matched requests',
                    'description' => 'View and browse my matched Request.',
                ],
                'searchFields' => [
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                ],
                'currentSearchFields' => ['title'],
                'listRouteName' => 'request.me.matched-requests',
                'showRouteName' => 'request.show',
                'routeName' => 'request.me.matched-requests',
                'resourceClass' => RequestResource::class,
                'serviceMethod' => 'getMatchedRequests',
                'requiresUser' => true,
                'context' => $context,
            ],
            RequestContextService::CONTEXT_SUBSCRIBED => [
                'component' => 'request/List',
                'title' => 'View my subscribed requests',
                'banner' => [
                    'title' => 'View my subscribed requests',
                    'description' => 'View and browse my subscribed Request.',
                ],
                'searchFields' => [
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                ],
                'currentSearchFields' => ['title'],
                'listRouteName' => 'request.me.subscribed-requests',
                'showRouteName' => 'request.show',
                'routeName' => 'request.me.subscribed-requests',
                'resourceClass' => RequestResource::class,
                'serviceMethod' => 'getSubscribedRequests',
                'requiresUser' => true,
                'context' => $context,
            ],
        };
    }

    /**
     * Get requests based on context
     *
     * @throws \Throwable
     */
    private function getRequestsForContext(
        string $context,
        array $config,
        $user,
        array $searchFilters,
        array $sortFilters
    ) {
        $serviceMethod = $config['serviceMethod'];
        $requiresUser = $config['requiresUser'] ?? false;

        // Call service method dynamically
        if ($requiresUser) {
            return $this->service->$serviceMethod($user, $searchFilters, $sortFilters);
        }

        return $this->service->$serviceMethod($searchFilters, $sortFilters);
    }

    /**
     * Unified invokable method handling all contexts
     *
     * @throws \Throwable
     */
    public function __invoke(Request $httpRequest): Response
    {
        $context = $this->getRouteContext();
        $config = $this->getContextConfiguration($context);
        $user = $httpRequest->user();

        $searchFilters = $this->buildSearchFilters($httpRequest, $config['searchFields']);
        $sortFilters = $this->buildSortFilters($httpRequest);

        // Fetch requests based on context
        $requests = $this->getRequestsForContext(
            $context,
            $config,
            $user,
            $searchFilters,
            $sortFilters
        );

        // Transform each entity and attach its actions
        $resourceClass = $config['resourceClass'];
        $requests->getCollection()->transform(function ($request) use ($user, $context, $resourceClass) {
            $data = (new $resourceClass($request))->toArray(request());
            $data['actions'] = $this->getActions($request, $user, $context);

            return $data;
        });

        // Build response data
        $responseData = [
            'requests' => $requests,
            'title' => $config['title'],
            'currentSort' => [
                'field' => $sortFilters['field'],
                'order' => $sortFilters['order'],
            ],
            'currentSearch' => $this->buildCurrentSearch($searchFilters, $config['currentSearchFields']),
            'listRouteName' => $config['listRouteName'],
            'showRouteName' => $config['showRouteName'] ?? null,
            'searchFields' => $config['searchFields'] ?? [],
            'actions' => $config['pageActions'] ?? [],
            'context' => $context,
        ];

        // Add banner if configured
        if (isset($config['banner'])) {
            $responseData['banner'] = $this->buildBanner(
                $config['banner']['title'],
                $config['banner']['description']
            );
        }

        // Add routeName for user contexts (backward compatibility)
        if (isset($config['routeName'])) {
            $responseData['routeName'] = $config['routeName'];
        }

        // Add additional data (e.g., availableStatuses for admin)
        if (isset($config['additionalData'])) {
            $responseData = array_merge($responseData, $config['additionalData']);
        }

        return Inertia::render($config['component'], $responseData);
    }
}
