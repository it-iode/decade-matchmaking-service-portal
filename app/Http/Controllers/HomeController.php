<?php

namespace App\Http\Controllers;

use App\Domains\Settings\Models\Setting;
use App\Domains\Settings\Services\SettingsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function __construct(private readonly SettingsService $settingsService) {}

    public function index(
        Request $request
    ): \Inertia\Response {
        $user = $request->user();

        $showOdcTravelSupport = filter_var(
            $this->settingsService->getSetting(Setting::SHOW_ODC_TRAVEL_SUPPORT_SECTION, true),
            FILTER_VALIDATE_BOOLEAN
        );

        return Inertia::render('Home', [
            'title' => 'Welcome '.$user->name,
            'userGuide' => $this->settingsService->getSetting(Setting::USER_GUIDE),
            'partnerGuide' => $this->settingsService->getSetting(Setting::PARTNER_GUIDE),
            'showOdcTravelSupport' => $showOdcTravelSupport,
            'banner' => [
                'title' => 'Welcome back '.$user->name,
                'description' => 'The Ocean Decade Capacity Development Platform',
                'image' => '/assets/img/sidebar.png',
            ],
        ]);
    }
}
