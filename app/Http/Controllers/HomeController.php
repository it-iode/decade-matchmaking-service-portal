<?php

namespace App\Http\Controllers;

use App\Domains\Opportunity\Enums\Type;
use App\Domains\Opportunity\Resources\OpportunityResource;
use App\Domains\Opportunity\Services\OpportunityService;
use App\Domains\Settings\Models\Setting;
use App\Domains\Settings\Services\SettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Throwable;

class HomeController extends Controller
{
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly OpportunityService $opportunityService
    ) {}

    /**
     * @throws Throwable
     */
    public function index(
        Request $request
    ): \Inertia\Response {
        $user = $request->user();

        $showOdcTravelSupport = filter_var(
            $this->settingsService->getSetting(Setting::SHOW_ODC_TRAVEL_SUPPORT_SECTION, true),
            FILTER_VALIDATE_BOOLEAN
        );
        $odcTravelSupport = $showOdcTravelSupport
            ? $this->opportunityService
                ->getActiveOpportunitiesByType(Type::ODC_TRAVEL_SUPPORT)
                ->toResourceCollection(OpportunityResource::class)
            : [];

        return Inertia::render('Home', [
            'title' => 'Welcome '.$user->name,
            'userGuide' => $this->settingsService->getSetting(Setting::USER_GUIDE),
            'partnerGuide' => $this->settingsService->getSetting(Setting::PARTNER_GUIDE),
            'odcTravelSupport' => $odcTravelSupport,
            'showOdcTravelSupport' => $showOdcTravelSupport,
            'banner' => [
                'title' => 'Welcome back '.$user->name,
                'description' => 'The Ocean Decade Capacity Development Platform',
                'image' => '/assets/img/sidebar.png',
            ],
        ]);
    }
}
