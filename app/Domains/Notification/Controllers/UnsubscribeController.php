<?php

declare(strict_types=1);

namespace App\Domains\Notification\Controllers;

use App\Domains\Notification\Services\NotificationPreferenceService;
use App\Domains\User\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class UnsubscribeController extends Controller
{
    public function __construct(
        private readonly NotificationPreferenceService $notificationPreferenceService
    ) {}

    /**
     * Display the unsubscribe confirmation page
     */
    public function show(Request $request, User $user): Response
    {
        // Validate user exists and is not blocked
        if ($user->isBlocked()) {
            abort(403, 'This user account has been blocked.');
        }

        return Inertia::render('unsubscribe/Index', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email_notifications_enabled' => $user->isSubscribedToEmails(),
            ],
        ]);
    }

    /**
     * Process the unsubscribe action
     */
    public function unsubscribe(Request $request, User $user): RedirectResponse
    {
        // Validate user exists and is not blocked
        if ($user->isBlocked()) {
            abort(403, 'This user account has been blocked.');
        }

        // Optionally validate request with CSRF token for additional security
        $request->validate([
            'confirm' => 'sometimes|boolean',
            'remove_subscriptions' => 'sometimes|boolean',
        ]);

        try {
            // Use the service to handle the unsubscribe logic
            $removeSubscriptions = $request->boolean('remove_subscriptions', false);
            $this->notificationPreferenceService->unsubscribeFromAllNotifications($user, $removeSubscriptions);

            // Log the unsubscribe action with request details
            Log::info('User unsubscribed via web interface', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'removed_subscriptions' => $removeSubscriptions,
            ]);

            // Return success response with Inertia redirect
            return redirect()->route('unsubscribe.show', $user)
                ->with('success', 'You have been successfully unsubscribed from all email notifications.');

        } catch (\Exception $e) {
            Log::error('Failed to process unsubscribe request', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'An error occurred while processing your unsubscribe request. Please try again later.');
        }
    }

    /**
     * Display success page after unsubscribe
     */
    public function success(User $user): Response
    {
        return Inertia::render('unsubscribe/Success', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
