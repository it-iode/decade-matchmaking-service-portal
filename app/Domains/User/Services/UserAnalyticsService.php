<?php

namespace App\Domains\User\Services;

use App\Domains\User\Models\User;

class UserAnalyticsService
{
    public function getUserStatistics(User $user): array
    {
        return [
            'total_requests' => $user->requests()->count(),
            'active_requests' => $user->requests()
                ->whereHas('status', fn ($q) => $q->where('status_code', 'active'))
                ->count(),
            'total_offers' => $user->matchedRequests()->count(),
            'total_opportunities' => $user->opportunities()->count(),
            'notifications_received' => $user->notifications()->count(),
            'unread_notifications' => $user->notifications()
                ->whereNull('read_at')
                ->count(),
            'account_age_days' => $user->created_at->diffInDays(now()),
            'last_activity' => $user->updated_at,
        ];
    }

    public function getUserActivitySummary(User $user): array
    {
        $thirtyDaysAgo = now()->subDays(30);

        return [
            'recent_requests' => $user->requests()
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->count(),
            'recent_offers' => $user->matchedRequests()
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->count(),
            'activity_timeline' => $this->getActivityTimeline($user),
        ];
    }

    private function getActivityTimeline(User $user): array
    {
        // Get last 10 activities
        $activities = collect();

        // Add requests
        $user->requests()
            ->latest()
            ->take(5)
            ->get()
            ->each(fn ($r) => $activities->push([
                'type' => 'request_created',
                'title' => 'Created request: '.$r->title,
                'date' => $r->created_at,
            ]));

        // Add matched requests
        $user->matchedRequests()
            ->latest()
            ->take(5)
            ->get()
            ->each(fn ($o) => $activities->push([
                'type' => 'offer_made',
                'title' => 'Matched with request',
                'date' => $o->created_at,
            ]));

        return $activities
            ->sortByDesc('date')
            ->take(10)
            ->values()
            ->toArray();
    }

    public function getSystemWideStatistics(): array
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('is_blocked', false)->count(),
            'blocked_users' => User::where('is_blocked', true)->count(),
            'verified_users' => User::whereNotNull('email_verified_at')->count(),
            'users_by_country' => User::groupBy('country')
                ->selectRaw('country, COUNT(*) as count')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'country'),
            'new_users_this_week' => User::where('created_at', '>=', now()->subWeek())->count(),
            'new_users_this_month' => User::where('created_at', '>=', now()->subMonth())->count(),
        ];
    }
}
