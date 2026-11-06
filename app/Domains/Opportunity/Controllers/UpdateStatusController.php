<?php

namespace App\Domains\Opportunity\Controllers;

use App\Domains\Opportunity\Models\Opportunity;
use App\Domains\Opportunity\Services\OpportunityService;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;

class UpdateStatusController extends Controller
{
    public function __construct(private OpportunityService $opportunityService) {}

    public function __invoke(Request $request, Opportunity $opportunity)
    {
        try {
            $statusCode = (int) $request->input('status');
            $this->opportunityService->updateOpportunityStatus($opportunity, $statusCode, $request->user());

            return back()->with('success', 'Opportunity status updated successfully.');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to update opportunity status: '.$e->getMessage());
        }
    }
}
