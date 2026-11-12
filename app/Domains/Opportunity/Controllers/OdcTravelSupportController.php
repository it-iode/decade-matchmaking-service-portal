<?php

declare(strict_types=1);

namespace App\Domains\Opportunity\Controllers;

use App\Domains\Opportunity\Enums\TargetAudience;
use App\Domains\Opportunity\Requests\OdcTravelSupportPostRequest;
use App\Domains\Opportunity\Services\OpportunityService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * ODC travel support submissions from the dedicated one-page form (issue #218).
 *
 * Unlike FormController::store, submitting is open to any signed-in user and
 * redirects back to the home page, which non-partners can reach. The page
 * itself is open to guests: their entries are kept client-side (a per-tab
 * draft in the browser) while they sign in from the same page, and url.intended
 * brings them back here after any sign-in method completes.
 */
final class OdcTravelSupportController extends Controller
{
    public function __construct(private readonly OpportunityService $opportunityService) {}

    /**
     * The one-page submission form (opportunity details + sign-in for guests).
     */
    public function create(Request $request): Response
    {
        if ($request->user() === null) {
            // Every sign-in method (credentials, OAuth, OTP) honors
            // url.intended, so a guest who signs in from this page lands back
            // on it, with the client-side draft of their entries intact.
            $request->session()->put('url.intended', route('opportunity.odc-travel-support.create'));
        }

        return Inertia::render('opportunity/OdcTravelSupportCreate', [
            'formOptions' => [
                'target_audience' => TargetAudience::getOdcTravelSupportOptions(),
            ],
            // Breadcrumbs come from routes/breadcrumbs.php via HandleInertiaRequests.
            'banner' => $this->buildBanner(
                'Upload a 2027 ODC travel support opportunity',
                'Upload a 2027 ODC travel support opportunity'
            )
        ]);
    }

    public function store(OdcTravelSupportPostRequest $request): RedirectResponse
    {
        $user = $request->user();

        try {
            $this->opportunityService->storeOpportunity($user, $request->validated(), null);

            return to_route('index')->with(
                'success',
                '2027 ODC travel support opportunity submitted successfully. It will be reviewed before publication.'
            );
        } catch (Throwable $e) {
            Log::error('ODC travel support submission failed', [
                'user_id' => $user->id,
                'exception' => $e,
            ]);

            return to_route('index')->with('error', 'An error occurred while submitting. Please try again.');
        }
    }
}
