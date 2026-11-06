<?php

declare(strict_types=1);

namespace App\Domains\Notification\Controllers\Admin;

use App\Domains\Notification\Services\SubscriptionService;
use App\Domains\Request\Models\Request as OCDRequest;
use App\Domains\User\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {}

    /**
     * Display subscription management page
     */
    public function index(Request $request): Response
    {
        $subscriptions = $this->subscriptionService->getAllSubscriptions();
        $stats = $this->subscriptionService->getSubscriptionStats();

        // Format requests for SelectField dropdown
        $requests = OCDRequest::select('id', 'status_id', 'user_id')
            ->with([
                'detail:request_id,capacity_development_title',
                'user:id,name',
                'status:id,status_code',
            ])
            ->whereHas('status', fn ($q) => $q->whereNotIn('status_code', ['draft', 'deleted'])
            )
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($req) => [
                'value' => $req->id,
                'label' => ($req->detail->capacity_development_title ?? 'Untitled').
                           " (by {$req->user->name})",
            ]);

        return Inertia::render('admin/Subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'stats' => $stats,
            'requests' => $requests,
        ]);
    }

    /**
     * Subscribe a user to a request (admin action)
     */
    public function subscribeUser(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'request_id' => 'required|exists:requests,id',
        ]);

        try {
            $user = User::findOrFail($validated['user_id']);
            $ocdRequest = OCDRequest::findOrFail($validated['request_id']);
            $admin = auth()->user();

            // Check if already subscribed
            if ($this->subscriptionService->isUserSubscribed($user, $ocdRequest)) {
                return back()->with('warning', 'User is already subscribed to this request.');
            }

            $subscription = $this->subscriptionService->adminSubscribeUser($admin, $user, $ocdRequest);

            // Log the admin action
            Log::info('Admin subscription created', [
                'admin_id' => $admin->id,
                'user_id' => $user->id,
                'request_id' => $ocdRequest->id,
                'subscription_id' => $subscription->id,
            ]);

            return to_route('admin.subscriptions.index')->with(
                'success',
                sprintf(
                    'User "%s" has been successfully subscribed to request "%s".',
                    $user->name,
                    $ocdRequest->detail->capacity_development_title ?? 'Untitled Request'
                )
            );
        } catch (\Exception $e) {
            Log::error('Failed to subscribe user', [
                'admin_id' => auth()->id(),
                'user_id' => $validated['user_id'] ?? null,
                'request_id' => $validated['request_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['general' => 'Failed to subscribe user: '.$e->getMessage()]);
        }
    }

    /**
     * Unsubscribe a user from a request (admin action)
     */
    public function unsubscribeUser(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'request_id' => 'required|exists:requests,id',
        ]);

        try {
            $user = User::findOrFail($validated['user_id']);
            $ocdRequest = OCDRequest::findOrFail($validated['request_id']);

            $success = $this->subscriptionService->unsubscribe($user, $ocdRequest);

            if (! $success) {
                return back()->with(
                    'warning',
                    'Subscription not found. The user may have already been unsubscribed.'
                );
            }

            // Log the admin action
            Log::info('Admin unsubscribed user', [
                'admin_id' => auth()->id(),
                'user_id' => $user->id,
                'request_id' => $ocdRequest->id,
            ]);

            return to_route('admin.subscriptions.index')->with(
                'success',
                sprintf(
                    'User "%s" has been successfully unsubscribed from request "%s".',
                    $user->name,
                    $ocdRequest->detail->capacity_development_title ?? 'Untitled Request'
                )
            );
        } catch (\Exception $e) {
            Log::error('Failed to unsubscribe user', [
                'admin_id' => auth()->id(),
                'user_id' => $validated['user_id'] ?? null,
                'request_id' => $validated['request_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['general' => 'Failed to unsubscribe user: '.$e->getMessage()]);
        }
    }

    /**
     * Show subscribers for a specific request
     */
    public function requestSubscribers(OCDRequest $request): Response
    {
        $subscribers = $this->subscriptionService->getRequestSubscribers($request);

        return Inertia::render('admin/Subscriptions/RequestSubscribers', [
            'request' => $request->load('user', 'status', 'detail'),
            'subscribers' => $subscribers,
        ]);
    }

    /**
     * Show subscriptions for a specific user
     */
    public function userSubscriptions(User $user): Response
    {
        $subscriptions = $this->subscriptionService->getUserSubscriptions($user);

        return Inertia::render('admin/Subscriptions/UserSubscriptions', [
            'user' => $user,
            'subscriptions' => $subscriptions,
        ]);
    }
}
