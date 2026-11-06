import React from 'react';
import { UsersIcon, ShieldCheckIcon, DocumentTextIcon } from '@heroicons/react/16/solid';
import { SubscriptionStats } from '../types/subscription.types';

interface SubscriptionStatsCardsProps {
    stats: SubscriptionStats;
    className?: string;
}

interface StatCard {
    label: string;
    value: number;
    icon?: React.ComponentType<React.SVGProps<SVGSVGElement>>;
    color?: string;
}

export function SubscriptionStatsCards({ stats, className = '' }: SubscriptionStatsCardsProps) {
    const statCards: StatCard[] = [
        {
            label: 'Total Subscriptions',
            value: stats.total_subscriptions,
            icon: UsersIcon,
        },
        {
            label: 'Admin Created',
            value: stats.admin_created_subscriptions,
            icon: ShieldCheckIcon,
        },
        {
            label: 'Subscribed Requests',
            value: stats.unique_subscribed_requests,
            icon: DocumentTextIcon,
        },
    ];

    return (
        <div className={`grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 ${className}`}>
            {statCards.map((stat) => (
                <div
                    key={stat.label}
                    className="bg-white dark:bg-zinc-800 overflow-hidden shadow rounded-lg border border-zinc-200 dark:border-zinc-700"
                >
                    <div className="p-5">
                        <div className="flex items-center">
                            {stat.icon && (
                                <div className="flex-shrink-0">
                                    <stat.icon className="h-6 w-6 text-zinc-400" />
                                </div>
                            )}
                            <div className={`${stat.icon ? 'ml-5' : ''} w-0 flex-1`}>
                                <dl>
                                    <dt className="text-sm font-medium text-zinc-500 dark:text-zinc-400 truncate">
                                        {stat.label}
                                    </dt>
                                    <dd className="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                                        {stat.value}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
}
