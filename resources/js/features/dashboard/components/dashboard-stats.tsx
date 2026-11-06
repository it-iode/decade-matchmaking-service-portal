import React from 'react';
import KPICard from './kpi-card';
import {
  FileText,
  Users,
  TrendingUp,
  Calendar,
  Globe,
  Activity,
  Target,
  Award
} from 'lucide-react';

interface DashboardStatsProps {
  stats: {
    dailyRequests: number;
    dailyOpportunities: number;
    weeklyRegistrations: number;
    totalUsers: number;
    totalRequests: number;
    totalOpportunities: number;
    trends: {
      requests: number;
      opportunities: number;
      registrations: number;
    };
  };
}

export default function DashboardStats({ stats }: DashboardStatsProps) {
  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="border-b border-gray-200 dark:border-gray-700 pb-4">
        <h2 className="text-2xl font-bold text-gray-900 dark:text-white">Platform Overview</h2>
        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
          Key performance indicators and platform usage statistics
        </p>
      </div>

      {/* KPI Grid */}
      <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        {/* Daily Requests */}
        <KPICard
          title="Daily Requests"
          value={stats.dailyRequests}
          icon={FileText}
          color="blue"
          description="New requests created today"
          trend={{
            value: stats.trends.requests,
            isPositive: stats.trends.requests >= 0,
            label: "vs yesterday"
          }}
        />

        {/* Daily Opportunities */}
        <KPICard
          title="Daily Opportunities"
          value={stats.dailyOpportunities}
          icon={Target}
          color="green"
          description="New opportunities posted today"
          trend={{
            value: stats.trends.opportunities,
            isPositive: stats.trends.opportunities >= 0,
            label: "vs yesterday"
          }}
        />

        {/* Weekly Registrations */}
        <KPICard
          title="Weekly Registrations"
          value={stats.weeklyRegistrations}
          icon={Users}
          color="purple"
          description="New users registered this week"
          trend={{
            value: stats.trends.registrations,
            isPositive: stats.trends.registrations >= 0,
            label: "vs last week"
          }}
        />

        {/* Total Users */}
        <KPICard
          title="Total Users"
          value={stats.totalUsers.toLocaleString()}
          icon={Globe}
          color="orange"
          description="Registered platform users"
        />
      </div>

      {/* Second Row */}{/*
      <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
         Total Requests
        <KPICard
          title="Total Requests"
          value={stats.totalRequests.toLocaleString()}
          icon={FileText}
          color="blue"
          description="All time requests submitted"
        />

         Total Opportunities
        <KPICard
          title="Total Opportunities"
          value={stats.totalOpportunities.toLocaleString()}
          icon={Target}
          color="green"
          description="All time opportunities posted"
        />

         Active Partners
        <KPICard
          title="Active Partners"
          value={stats.activePartners}
          icon={Award}
          color="purple"
          description="Partners with active offers"
          trend={{
            value: 5,
            isPositive: true,
            label: "vs last month"
          }}
        />

         Success Rate
        <KPICard
          title="Success Rate"
          value={`${stats.successRate}%`}
          icon={TrendingUp}
          color="green"
          description="Requests successfully matched"
          trend={{
            value: 3,
            isPositive: true,
            label: "vs last month"
          }}
        />
      </div>*/}

      {/* Activity Summary */}
      <div className="mt-8 rounded-lg bg-gray-50 dark:bg-gray-800 p-6">
        <div className="flex items-center justify-between">
          <div>
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">Recent Activity</h3>
            <p className="text-sm text-gray-600 dark:text-gray-400">Platform activity over the last 24 hours</p>
          </div>
          <Activity className="h-6 w-6 text-gray-400 dark:text-gray-500" />
        </div>

        <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
          <div className="flex items-center space-x-3">
            <div className="h-2 w-2 rounded-full bg-green-500 dark:bg-green-400"></div>
            <span className="text-sm text-gray-600 dark:text-gray-300">
              <span className="font-medium">{stats.dailyRequests}</span> new requests
            </span>
          </div>
          <div className="flex items-center space-x-3">
            <div className="h-2 w-2 rounded-full bg-blue-500 dark:bg-blue-400"></div>
            <span className="text-sm text-gray-600 dark:text-gray-300">
              <span className="font-medium">{stats.dailyOpportunities}</span> new opportunities
            </span>
          </div>
          <div className="flex items-center space-x-3">
            <div className="h-2 w-2 rounded-full bg-purple-500 dark:bg-purple-400"></div>
            <span className="text-sm text-gray-600 dark:text-gray-300">
              <span className="font-medium">{Math.floor(stats.weeklyRegistrations / 7)}</span> new users today
            </span>
          </div>
        </div>
      </div>
    </div>
  );
}
