<?php

declare(strict_types=1);

namespace App\Domains\Opportunity\Controllers;

use App\Domains\Opportunity\Requests\OdcTravelSupportPostRequest;
use App\Domains\Opportunity\Services\OpportunityService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Throwable;

/**
 * Handles ODC travel support submissions from the home page (issue #218).
 *
 * Unlike FormController::store this is open to any signed-in user and redirects
 * back to the home page, which non-partners can reach.
 */
final class OdcTravelSupportController extends Controller
{
    public function __construct(private readonly OpportunityService $opportunityService) {}

    public function __invoke(OdcTravelSupportPostRequest $request): RedirectResponse
    {
        try {
            $this->opportunityService->storeOpportunity($request->user(), $request->validated(), null);

            return to_route('index')->with(
                'success',
                '2027 ODC travel support opportunity submitted successfully. It will be reviewed before publication.'
            );
        } catch (Throwable $e) {
            return back()->with('error', 'An error occurred. Error message :'.$e->getMessage());
        }
    }
}
