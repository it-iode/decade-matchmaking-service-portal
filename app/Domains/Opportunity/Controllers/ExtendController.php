<?php

namespace App\Domains\Opportunity\Controllers;

use App\Domains\Opportunity\Models\Opportunity;
use App\Domains\Opportunity\Services\OpportunityService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExtendController extends Controller
{
    public function __construct(private readonly OpportunityService $opportunityService) {}

    public function __invoke(Request $request, Opportunity $opportunity)
    {
        if (! $request->user()->can('extend', [Opportunity::class, $opportunity])) {
            return back()->with('error', 'You do not have permission to extend this opportunity.');
        }

        $closingDate = $request->has('closing_date') ? \Carbon\Carbon::parse($request->input('closing_date')) : null;
        $opportunity = $this->opportunityService->extendOpportunityClosingDate($opportunity, $closingDate);

        return back()->with('success', 'Opportunity closing date extended successfully until '.$opportunity->closing_date->toFormattedDateString().'.');
    }
}
