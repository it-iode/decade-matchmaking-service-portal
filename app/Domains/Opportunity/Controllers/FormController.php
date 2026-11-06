<?php

namespace App\Domains\Opportunity\Controllers;

use App\Domains\Opportunity\Enums\CoverageActivity;
use App\Domains\Opportunity\Enums\TargetAudience;
use App\Domains\Opportunity\Enums\ThematicAreas;
use App\Domains\Opportunity\Enums\Type;
use App\Domains\Opportunity\Models\Opportunity;
use App\Domains\Opportunity\Requests\OpportunityPostRequest;
use App\Domains\Opportunity\Resources\OpportunityResource;
use App\Domains\Opportunity\Services\OpportunityService;
use App\Shared\Enums\Country;
use App\Shared\Enums\Language;
use App\Shared\Enums\Ocean;
use App\Shared\Enums\Region;
use App\Shared\Enums\YesNo;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class FormController extends BaseOpportunitiesController
{
    public function __construct(private readonly OpportunityService $opportunityService) {}

    /**
     * @throws Throwable
     */
    public function form(?Opportunity $opportunity = null): Response
    {
        $pageProps = [];

        if ($opportunity !== null && $opportunity->exists) {
            $pageProps['title'] = 'Edit Opportunity: '.$opportunity->title;
            $pageProps['banner'] = $this->buildBanner('Edit Opportunity', 'Edit the details of your opportunity.');
            $pageProps['opportunity'] = $opportunity->toResource(OpportunityResource::class);
        } else {
            $pageProps['title'] = 'Create a new Opportunity';
            $pageProps['banner'] = $this->buildBanner(
                'Create a new Opportunity',
                'Create a new Opportunity to get started.'
            );
        }
        $pageProps['formOptions'] = [
            'countries' => Country::getOptions(),
            'regions' => Region::getOptions(),
            'oceans' => Ocean::getOptions(),
            'target_audience' => TargetAudience::getOptions(),
            'opportunity_types' => Type::getOptions(),
            'coverage_activity' => CoverageActivity::getOptions(),
            'thematic_areas' => ThematicAreas::getOptions(),
            'target_languages' => Language::getOptions(),
            'yes_no' => YesNo::getOptions(),
        ];

        return Inertia::render('opportunity/Create', $pageProps);
    }

    public function store(OpportunityPostRequest $request, ?Opportunity $opportunity = null): \Illuminate\Http\RedirectResponse
    {
        $validatedData = $request->validated();
        $isUpdate = $opportunity !== null && $opportunity->exists;

        try {
            $this->opportunityService->storeOpportunity(
                $request->user(),
                $validatedData,
                $isUpdate ? $opportunity : null
            );

            if ($isUpdate) {
                return to_route('me.opportunity.list')->with('success', 'Opportunity updated successfully');
            } else {
                return to_route('me.opportunity.list')->with('success', 'Opportunity submitted successfully');
            }
        } catch (Throwable $e) {
            return back()->with(
                'error',
                'An error occurred. Error message :'.$e->getMessage()
            );
        }
    }
}
