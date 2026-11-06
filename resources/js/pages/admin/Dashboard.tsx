import React from 'react';
import { Head , usePage} from '@inertiajs/react';
import { DashboardStats } from '@features/dashboard';
import { PageProps } from '@/types';

import {SidebarLayout} from '@layouts/index'

interface DashboardProps extends PageProps
{
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

export default function Dashboard() {
    const { stats } = usePage<DashboardProps>().props;
    console.log(stats);
    return (
        <SidebarLayout>
            <Head title="Dashboard" />
            <div className="space-y-4">
                <DashboardStats stats={stats} />
                <div className="mt-8 overflow-hidden bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                    <div className="p-6">
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h3>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <a
                                href="/request/create"
                                className="flex items-center p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                            >
                                <div className="flex-shrink-0">
                                    <div className="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                                        <svg className="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                    </div>
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-900 dark:text-white">Create Request</p>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">Submit a new capacity development request</p>
                                </div>
                            </a>

                            <a
                                href="/opportunity/create"
                                className="flex items-center p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                            >
                                <div className="flex-shrink-0">
                                    <div className="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                                        <svg className="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-900 dark:text-white">Post Opportunity</p>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">Create a new training opportunity</p>
                                </div>
                            </a>

                            <a
                                href="/request/list"
                                className="flex items-center p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                            >
                                <div className="flex-shrink-0">
                                    <div className="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                                        <svg className="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                    </div>
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-900 dark:text-white">View Requests</p>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">Browse all capacity development requests</p>
                                </div>
                            </a>

                            <a
                                href="/opportunity/list"
                                className="flex items-center p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                            >
                                <div className="flex-shrink-0">
                                    <div className="w-8 h-8 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center">
                                        <svg className="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-900 dark:text-white">Find Opportunities</p>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">Discover available training opportunities</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </SidebarLayout>
    );
}
