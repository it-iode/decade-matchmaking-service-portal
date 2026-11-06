<?php

declare(strict_types=1);

namespace App\Domains\Notification\Controllers;

use App\Domains\Notification\Services\NotificationPreferenceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ListController extends Controller
{
    public function __construct(
        private readonly NotificationPreferenceService $preferenceService
    ) {}

    /**
     * Display the user's notification preferences as a toggle matrix.
     */
    public function list(Request $request): Response
    {
        $settings = $this->preferenceService->getSettings($request->user());

        return Inertia::render('notification-preferences/List', [
            'settings' => $settings,
            'title' => 'Notification Preferences',
            'banner' => [
                'title' => 'Notification Preferences',
                'description' => 'Manage your notification preferences for requests and opportunities.',
                'image' => '/assets/img/sidebar.png',
            ],
        ]);
    }
}
