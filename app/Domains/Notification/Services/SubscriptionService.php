<?php

declare(strict_types=1);

namespace App\Domains\Notification\Services;

use App\Domains\Notification\Models\RequestSubscription;
use App\Domains\Request\Models\Request;
use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SubscriptionService
{
    /**
     * Subscribe a user to a request
     */
    public function subscribe(User $user, Request $request, ?User $adminUser = null): RequestSubscription
    {
        // Check if subscription already exists
        $existingSubscription = RequestSubscription::where('user_id', $user->id)
            ->where('request_id', $request->id)
            ->first();

        if ($existingSubscription) {
            throw new \Exception('User is already subscribed to this request.');
        }

        return RequestSubscription::create([
            'user_id' => $user->id,
            'request_id' => $request->id,
            'subscribed_by_admin' => $adminUser !== null,
            'admin_user_id' => $adminUser?->id,
        ]);
    }

    /**
     * Unsubscribe a user from a request
     */
    public function unsubscribe(User $user, Request $request): bool
    {
        return RequestSubscription::where('user_id', $user->id)
            ->where('request_id', $request->id)
            ->delete() > 0;
    }

    /**
     * Get user's subscriptions with pagination
     */
    public function getUserSubscriptions(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return RequestSubscription::where('user_id', $user->id)
            ->with(['request.status', 'request.user', 'request.detail', 'adminUser'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get request subscribers
     */
    public function getRequestSubscribers(Request $request): Collection
    {
        return RequestSubscription::where('request_id', $request->id)
            ->with(['user', 'adminUser'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Admin subscribe a user to a request
     */
    public function adminSubscribeUser(User $admin, User $targetUser, Request $request): RequestSubscription
    {
        if (! $admin->hasRole('administrator')) {
            throw new \Exception('Only administrators can subscribe users to requests.');
        }

        return $this->subscribe($targetUser, $request, $admin);
    }

    /**
     * Check if user is subscribed to a request
     */
    public function isUserSubscribed(User $user, Request $request): bool
    {
        return RequestSubscription::where('user_id', $user->id)
            ->where('request_id', $request->id)
            ->exists();
    }

    /**
     * Get all subscriptions with pagination (for admin)
     */
    public function getAllSubscriptions(int $perPage = 20): LengthAwarePaginator
    {
        return RequestSubscription::with(['user', 'request.status', 'request.detail', 'adminUser'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get subscription statistics
     */
    public function getSubscriptionStats(): array
    {
        return [
            'total_subscriptions' => RequestSubscription::count(),
            'admin_created_subscriptions' => RequestSubscription::where('subscribed_by_admin', true)->count(),
            'user_created_subscriptions' => RequestSubscription::where('subscribed_by_admin', false)->count(),
            'unique_subscribed_requests' => RequestSubscription::distinct('request_id')->count(),
        ];
    }
}
