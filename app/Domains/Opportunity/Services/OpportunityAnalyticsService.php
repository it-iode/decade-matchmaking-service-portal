<?php

namespace App\Domains\Opportunity\Services;

use App\Domains\Opportunity\Enums\Status;
use App\Domains\Opportunity\Models\Opportunity;
use App\Domains\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OpportunityAnalyticsService
{
    public function __construct(
        private readonly OpportunityRepository $repository
    ) {}

    /**
     * Get opportunity statistics for a specific user
     */
    public function getUserOpportunityStats(User $user): array
    {
        return $this->repository->getCountByStatus($user);
    }

    /**
     * Get system-wide opportunity statistics
     */
    public function getSystemStats(): array
    {
        return [
            'total_opportunities' => Opportunity::count(),
            'active_opportunities' => Opportunity::where('status', Status::ACTIVE)->count(),
            'pending_opportunities' => Opportunity::where('status', Status::PENDING_REVIEW)->count(),
            'closed_opportunities' => Opportunity::where('status', Status::CLOSED)->count(),
            'total_partners' => Opportunity::distinct('user_id')->count(),
        ];
    }

    /**
     * Get opportunities created over time (monthly breakdown)
     */
    public function getOpportunitiesOverTime(int $months = 12): array
    {
        $startDate = Carbon::now()->subMonths($months)->startOfMonth();

        return Opportunity::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();
    }

    /**
     * Get opportunities by type distribution
     */
    public function getOpportunityTypeDistribution(): array
    {
        return Opportunity::select('type', DB::raw('COUNT(*) as count'))
            ->whereNotNull('type')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
    }

    /**
     * Get opportunities by location distribution
     */
    public function getOpportunityLocationDistribution(int $limit = 10): array
    {
        return Opportunity::select('implementation_location', DB::raw('COUNT(*) as count'))
            ->whereNotNull('implementation_location')
            ->where('implementation_location', '!=', '')
            ->groupBy('implementation_location')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->pluck('count', 'implementation_location')
            ->toArray();
    }

    /**
     * Get opportunities by status distribution
     */
    public function getOpportunityStatusDistribution(): array
    {
        $statusCounts = Opportunity::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Map status codes to labels
        $result = [];
        foreach ($statusCounts as $status => $count) {
            $label = match ($status) {
                Status::PENDING_REVIEW->value => 'Pending Review',
                Status::ACTIVE->value => 'Active',
                Status::CLOSED->value => 'Closed',
                default => 'Unknown'
            };
            $result[$label] = $count;
        }

        return $result;
    }

    /**
     * Get user's opportunity performance metrics
     */
    public function getUserPerformanceMetrics(User $user): array
    {
        $userOpportunities = $this->repository->getByUser($user);
        $totalOpportunities = $userOpportunities->count();

        if ($totalOpportunities === 0) {
            return [
                'total_opportunities' => 0,
                'active_rate' => 0,
                'average_time_to_activation' => 0,
                'most_recent_activity' => null,
            ];
        }

        $activeOpportunities = $userOpportunities->where('status', Status::ACTIVE);
        $activeRate = ($activeOpportunities->count() / $totalOpportunities) * 100;

        // Calculate average time to activation for active opportunities
        $averageTimeToActivation = $activeOpportunities
            ->filter(fn ($opp) => $opp->updated_at && $opp->created_at)
            ->map(fn ($opp) => $opp->created_at->diffInDays($opp->updated_at))
            ->average() ?? 0;

        return [
            'total_opportunities' => $totalOpportunities,
            'active_rate' => round($activeRate, 2),
            'average_time_to_activation' => round($averageTimeToActivation, 1),
            'most_recent_activity' => $userOpportunities->first()?->created_at,
        ];
    }

    /**
     * Get trending opportunity data
     */
    public function getTrendingOpportunities(int $days = 30, int $limit = 10): array
    {
        $startDate = Carbon::now()->subDays($days);

        return Opportunity::where('created_at', '>=', $startDate)
            ->where('status', Status::ACTIVE)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($opportunity) {
                return [
                    'id' => $opportunity->id,
                    'title' => $opportunity->title,
                    'type' => $opportunity->type,
                    'location' => $opportunity->implementation_location,
                    'created_at' => $opportunity->created_at,
                    'partner' => $opportunity->user->name ?? 'Unknown',
                ];
            })
            ->toArray();
    }
}
