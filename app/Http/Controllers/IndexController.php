<?php

namespace App\Http\Controllers;

use App\Domains\Opportunity\Enums\TargetAudience;
use App\Domains\Opportunity\Enums\Type;
use App\Domains\Opportunity\Resources\OpportunityResource;
use App\Domains\Opportunity\Services\OpportunityService;
use App\Domains\Settings\Models\Setting;
use App\Domains\Settings\Services\SettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IndexController extends Controller
{
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly OpportunityService $opportunityService
    ) {}

    /**
     * @throws \Throwable
     */
    public function __invoke(Request $request)
    {
        $recentOpportunities = $this->opportunityService->getRecentActiveOpportunities(200);

        $showOdcTravelSupport = filter_var(
            $this->settingsService->getSetting(Setting::SHOW_ODC_TRAVEL_SUPPORT_SECTION, true),
            FILTER_VALIDATE_BOOLEAN
        );

        // Only fetch and serialize the ODC travel support data when the section is shown.
        $odcTravelSupport = $showOdcTravelSupport
            ? $this->opportunityService
                ->getActiveOpportunitiesByType(Type::ODC_TRAVEL_SUPPORT)
                ->toResourceCollection(OpportunityResource::class)
            : [];

        return Inertia::render('Index', [
            'title' => 'Welcome',
            'description' => '',
            'banner' => [
                'title' => 'Connect for a Sustainable Ocean',
                'description' => 'The Ocean Decade Capacity Development Platform',
                'image' => '/assets/img/sidebar.png',
            ],
            'YoutubeEmbed' => [
                'src' => $this->settingsService->getSetting(Setting::HOMEPAGE_YOUTUBE_VIDEO),
                'title' => 'Connect for a Sustainable Ocean',
            ],
            'portalGuide' => [
                'description' => 'A user guide to help you navigate the platform.',
                'url' => $this->settingsService->getSetting(Setting::PORTAL_GUIDE),
            ],
            'metrics' => [
                'number_of_open_partner_opportunities' => $this->settingsService->getSetting(Setting::OPEN_PARTNER_OPPORTUNITIES_METRIC) ?? 0,
                'number_successful_matches' => $this->settingsService->getSetting(Setting::SUCCESSFUL_MATCHES_METRIC) ?? 0,
                'number_fully_closed_matches' => $this->settingsService->getSetting(Setting::FULLY_CLOSED_MATCHES_METRIC) ?? 0,
                'number_user_requests_in_implementation' => $this->settingsService->getSetting(Setting::REQUEST_IN_IMPLEMENTATION_METRIC) ?? 0,
                'committed_funding_amount' => $this->settingsService->getSetting(Setting::COMMITTED_FUNDING_METRIC) ?? 0,
            ],
            'recentOpportunities' => $recentOpportunities->toResourceCollection(OpportunityResource::class),
            'odcTravelSupport' => $odcTravelSupport,
            'showOdcTravelSupport' => $showOdcTravelSupport,
            'formOptions' => [
                'target_audience' => TargetAudience::getOdcTravelSupportOptions(),
            ],
        ]);
    }
}
