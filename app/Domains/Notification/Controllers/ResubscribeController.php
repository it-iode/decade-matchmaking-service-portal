<?php

declare(strict_types=1);

namespace App\Domains\Notification\Controllers;

use App\Domains\Notification\Services\NotificationPreferenceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ResubscribeController extends Controller
{
    public function __construct(
        private readonly NotificationPreferenceService $preferenceService
    ) {}

    /**
     * Re-enable all email notifications for the current user.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $this->preferenceService->resubscribe($request->user());

        return back();
    }
}
