<?php

namespace App\Domains\Opportunity\Controllers;

use App\Domains\Opportunity\Enums\Status;
use App\Domains\Opportunity\Enums\ThematicAreas;
use App\Domains\Opportunity\Enums\Type;
use App\Domains\Opportunity\Models\Opportunity;
use App\Domains\Opportunity\Services\OpportunityService;
use App\Shared\Http\HasPageActions;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ListController extends BaseOpportunitiesController
{
    use HasPageActions;

    public function __construct(private readonly OpportunityService $opportunityService) {}

    /**
     * Get context-specific configuration
     */
    private function getContextConfiguration(string $context): array
    {
        return match ($context) {
            'admin' => [
                'component' => 'admin/Opportunity/List',
                'title' => 'Opportunities',
                'searchFields' => [
                    ['name' => 'user', 'label' => 'User', 'type' => 'text'],
                    ['name' => 'title', 'label' => 'Title or keywords', 'type' => 'text'],
                    ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'options' => Opportunity::getTypeOptions()],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => Status::getOptions()],
                ],
                'currentSearchFields' => ['user', 'title'],
                'listRouteName' => 'admin.opportunity.list',
                'showRouteName' => 'admin.opportunity.show',
                'actions' => $this->buildActions([
                    $this->createSecondaryAction(
                        'Export Opportunities CSV',
                        route('admin.opportunity.export.csv'),
                        'ArrowDownTrayIcon',
                        'DOWNLOAD'
                    ),
                ]),
            ],
            'user_own' => [
                'component' => 'opportunity/List',
                'title' => 'Opportunities',
                'banner' => [
                    'title' => 'List of My submitted Opportunities',
                    'description' => 'Manage your submitted opportunities here.',
                ],
                'searchFields' => [
                    ['name' => 'title', 'label' => 'Title or keywords', 'type' => 'text'],
                    ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'options' => Type::getOptions()],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => Status::getOptions()],
                ],
                'currentSearchFields' => ['type', 'status', 'title'],
                'listRouteName' => 'me.opportunity.list',
                'showRouteName' => 'me.opportunity.show',
                'actions' => $this->buildActions([
                    $this->createPrimaryAction(
                        'Create New Opportunity',
                        route('opportunity.create'),
                        'PlusIcon',
                    ),
                ]),
            ],
            'public' => [
                'component' => 'opportunity/List',
                'title' => 'Opportunities',
                'banner' => [
                    'title' => 'List of Opportunities',
                    'description' => 'Browse and view opportunities submitted by CDF partners here.',
                ],
                'searchFields' => [
                    ['name' => 'title', 'label' => 'Title or keywords', 'type' => 'text'],
                    ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'options' => Type::getOptions()],
                    ['name' => 'thematic_areas', 'label' => 'Thematic areas', 'type' => 'select', 'options' => ThematicAreas::getOptions()],
                ],
                'currentSearchFields' => ['title', 'type', 'thematic_areas'],
                'routeName' => 'opportunity.list',
                'listRouteName' => 'opportunity.list',
                'showRouteName' => 'opportunity.show',
            ],
        };
    }

    /**
     * Get opportunities based on context
     *
     * @throws \Throwable
     */
    private function getOpportunitiesForContext(string $context, $user, array $searchFilters, array $sortFilters)
    {
        return match ($context) {
            'admin' => $this->opportunityService->getAllOpportunitiesPaginated($searchFilters, $sortFilters),
            'user_own' => $this->opportunityService->getUserOpportunitiesPaginated($user, $searchFilters, $sortFilters),
            default => $this->opportunityService->getActiveOpportunitiesPaginated($searchFilters, $sortFilters),
        };
    }

    /**
     * Build current search array based on context
     */
    private function buildCurrentSearch(array $searchFilters, array $fields): array
    {
        $currentSearch = [];
        foreach ($fields as $field) {
            $currentSearch[$field] = $searchFilters[$field] ?? '';
        }

        return $currentSearch;
    }

    /**
     * @throws \Throwable
     */
    public function __invoke(Request $httpRequest): Response
    {
        $context = $this->getRouteContext();
        $config = $this->getContextConfiguration($context);

        $searchFilters = $this->buildSearchFilters($httpRequest, $config['searchFields']);
        $sortFilters = $this->buildSortFilters($httpRequest);

        // Call appropriate service method based on context
        $opportunities = $this->getOpportunitiesForContext(
            $context,
            $httpRequest->user(),
            $searchFilters,
            $sortFilters
        );

        return Inertia::render($config['component'], [
            'opportunities' => $opportunities,
            'title' => $config['title'],
            'banner' => isset($config['banner']) ? $this->buildBanner(
                $config['banner']['title'],
                $config['banner']['description']
            ) : null,
            'searchFields' => $config['searchFields'],
            'currentSort' => [
                'field' => $sortFilters['field'],
                'order' => $sortFilters['order'],
            ],
            'listRouteName' => $config['listRouteName'],
            'showRouteName' => $config['showRouteName'],
            'currentSearch' => $this->buildCurrentSearch($searchFilters, $config['currentSearchFields']),
            'actions' => $config['actions'] ?? [],
            'context' => $this->getRouteContext(),
        ]);
    }
}
