import { Heading } from '@ui/primitives/heading';
import { Text } from '@ui/primitives/text';
import { User } from '@/types';
import React from 'react';

interface WelcomeSectionProps {
    user: User;
    stats?: {
        requestsCount?: number;
        opportunitiesCount?: number;
    };
}

function getGreeting(): string {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 18) return 'Good afternoon';
    return 'Good evening';
}

export default function WelcomeSection({ user, stats }: WelcomeSectionProps) {
    return (
        <section className="mb-12 rounded-xl bg-gradient-to-r from-firefly-600 to-bright-turquoise-600 dark:from-firefly-700 dark:to-bright-turquoise-700 p-8 text-white shadow-lg">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div className="flex-1">
                    <Heading level={1} className="text-3xl font-bold text-white mb-2">
                        {getGreeting()}, {user.first_name || user.name}!
                    </Heading>
                    <Text className="text-white">
                        Welcome to the Ocean Decade Portal
                    </Text>
                </div>

                {stats && (stats.requestsCount !== undefined || stats.opportunitiesCount !== undefined) && (
                    <div className="flex gap-6 sm:gap-8">
                        {stats.requestsCount !== undefined && (
                            <div className="text-center">
                                <div className="text-3xl font-bold text-white">
                                    {stats.requestsCount}
                                </div>
                                <Text className="text-sm text-white/80">
                                    Requests
                                </Text>
                            </div>
                        )}
                        {stats.opportunitiesCount !== undefined && (
                            <div className="text-center">
                                <div className="text-3xl font-bold text-white">
                                    {stats.opportunitiesCount}
                                </div>
                                <Text className="text-sm text-white/80">
                                    Opportunities
                                </Text>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </section>
    );
}

export type { WelcomeSectionProps };
