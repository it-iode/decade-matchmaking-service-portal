<?php

namespace App\Domains\Notification\Controllers;

use App\Domains\Notification\Services\SubscriptionService;
use App\Domains\Request\Models\Request as OCDRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {}

    /**
     * Subscribe to a request
     */
    public function subscribe(Request $request): JsonResponse
    {
        try {
            $ocdRequest = OCDRequest::findOrFail($request->input('request_id'));
            $user = auth()->user();

            $subscription = $this->subscriptionService->subscribe($user, $ocdRequest);

            return response()->json([
                'success' => true,
                'message' => 'Successfully subscribed to request',
                'subscription' => $subscription,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Unsubscribe from a request
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        try {
            $ocdRequest = OCDRequest::findOrFail($request->input('request_id'));
            $user = auth()->user();

            $success = $this->subscriptionService->unsubscribe($user, $ocdRequest);

            return response()->json([
                'success' => $success,
                'message' => $success ? 'Successfully unsubscribed from request' : 'Subscription not found',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Check subscription status
     */
    public function status(Request $request): JsonResponse
    {
        $ocdRequest = OCDRequest::findOrFail($request->input('request_id'));
        $user = auth()->user();

        $isSubscribed = $this->subscriptionService->isUserSubscribed($user, $ocdRequest);

        return response()->json([
            'is_subscribed' => $isSubscribed,
        ]);
    }

    /**
     * Show user's subscriptions
     */
    public function index(): Response
    {
        $user = auth()->user();
        $subscriptions = $this->subscriptionService->getUserSubscriptions($user);

        return Inertia::render('subscriptions/Index', [
            'subscriptions' => $subscriptions,
        ]);
    }
}
