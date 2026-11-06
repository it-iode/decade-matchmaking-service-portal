<?php

declare(strict_types=1);

namespace App\Domains\Notification\Controllers;

use App\Domains\Notification\Services\NotificationPreferenceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ToggleController extends Controller
{
    public function __construct(
        private readonly NotificationPreferenceService $preferenceService
    ) {}

    /**
     * Set a single notification type to the desired enabled state for the current user.
     *
     * Accepts `enabled` as the target state (idempotent — sending the same
     * request twice produces no additional effect).
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'entity_type' => ['required', Rule::in([
                NotificationPreferenceService::ENTITY_OPPORTUNITY,
                NotificationPreferenceService::ENTITY_REQUEST,
            ])],
            'attribute_value' => ['required', 'string'],
            'enabled' => ['required', 'boolean'],
        ]);

        $this->preferenceService->toggle(
            $request->user(),
            $validated['entity_type'],
            $validated['attribute_value'],
            (bool) $validated['enabled'],
        );

        return back();
    }
}
