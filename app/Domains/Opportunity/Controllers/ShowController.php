<?php

namespace App\Domains\Opportunity\Controllers;

use App\Domains\Opportunity\Models\Opportunity;
use App\Domains\Opportunity\Resources\OpportunityResource;
use App\Shared\Http\HasPageActions;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowController extends BaseOpportunitiesController
{
    use HasPageActions;

    /**
     * @throws \Throwable
     */
    public function __invoke(Request $request, Opportunity $opportunity): Response
    {
        $actions = [];

        if ($this->getRouteContext() === 'user_own' && $request->user()->can('create', Opportunity::class)) {
            $actions[] = $this->createPrimaryAction(
                'Create a new Opportunity',
                route('opportunity.create')
            );
        }

        if ($this->getRouteContext() === 'user_own' && $request->user()->can('edit', [Opportunity::class, $opportunity])) {
            $actions[] = $this->createAction(
                'Edit Opportunity',
                route('opportunity.edit', $opportunity->id),
            );
        }
        if ($this->getRouteContext() === 'public' && $request->user()->can('apply', [Opportunity::class, $opportunity])) {
            $actions[] = $this->createLink(
                'Apply for opportunity',
                route('opportunity.go', ['identifier' => $opportunity->public_id]),
                'primary'
            );
        }

        return Inertia::render('opportunity/Show', [
            'banner' => $this->buildBanner(
                'List of Opportunities',
                'Browse and view opportunities submitted by CDF partners here.'
            ),
            'opportunity' => $opportunity->toResource(OpportunityResource::class),
            'actions' => $this->buildActions($actions),
        ]);
    }
}
