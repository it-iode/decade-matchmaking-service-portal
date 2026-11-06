<?php

namespace App\Domains\User\Controllers;

use App\Domains\Opportunity\Models\Opportunity;
use App\Domains\Request\Models\Request as OCDRequest;
use App\Domains\User\Models\User;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $HttpRequest): Response
    {
        $user = $HttpRequest->user();

        // Calculate statistics
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $weekAgo = Carbon::now()->subWeek();

        // Daily statistics
        $dailyRequests = OCDRequest::whereDate('created_at', $today)->count();
        $dailyOpportunities = Opportunity::whereDate('created_at', $today)->count();

        // Weekly statistics
        $weeklyRegistrations = User::where('created_at', '>=', $weekAgo)->count();

        // Total statistics
        $totalUsers = User::count();
        $totalRequests = OCDRequest::count();
        $totalOpportunities = Opportunity::count();

        // Calculate trends (simplified - you might want to implement more sophisticated trend calculation)
        $yesterdayRequests = OCDRequest::whereDate('created_at', $yesterday)->count();
        $yesterdayOpportunities = Opportunity::whereDate('created_at', $yesterday)->count();
        $lastWeekRegistrations = User::whereBetween('created_at', [$weekAgo->copy()->subWeek(), $weekAgo])->count();

        $requestTrend = $yesterdayRequests > 0 ? round((($dailyRequests - $yesterdayRequests) / $yesterdayRequests) * 100) : 0;
        $opportunityTrend = $yesterdayOpportunities > 0 ? round((($dailyOpportunities - $yesterdayOpportunities) / $yesterdayOpportunities) * 100) : 0;
        $registrationTrend = $lastWeekRegistrations > 0 ? round((($weeklyRegistrations - $lastWeekRegistrations) / $lastWeekRegistrations) * 100) : 0;

        return Inertia::render('admin/Dashboard', [
            'title' => 'Welcome '.$user->name,
            'stats' => [
                'dailyRequests' => $dailyRequests,
                'dailyOpportunities' => $dailyOpportunities,
                'weeklyRegistrations' => $weeklyRegistrations,
                'totalUsers' => $totalUsers,
                'totalRequests' => $totalRequests,
                'totalOpportunities' => $totalOpportunities,
                'trends' => [
                    'requests' => $requestTrend,
                    'opportunities' => $opportunityTrend,
                    'registrations' => $registrationTrend,
                ],
            ],
        ]);
    }
}
