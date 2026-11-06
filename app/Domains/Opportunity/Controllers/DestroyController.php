<?php

namespace App\Domains\Opportunity\Controllers;

use App\Domains\Opportunity\Models\Opportunity;
use App\Domains\Opportunity\Services\OpportunityService;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;

class DestroyController extends Controller
{
    public function __construct(private OpportunityService $opportunityService) {}

    public function __invoke(Request $request, Opportunity $opportunity)
    {
        try {
            $this->opportunityService->deleteOpportunity($opportunity, $request->user());

            return back()->with('success', 'Opportunity deleted successfully.');
        } catch (Exception $e) {
            return back()->with('error', 'Opportunity not deleted. Error was : '.$e->getMessage());
        }
    }
}
