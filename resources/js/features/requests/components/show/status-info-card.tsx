import React from 'react';
import {Badge} from '@ui/primitives/badge';
import {Text} from '@ui/primitives/text';
import {OCDRequest, PageProps} from '@/types';
import {getStatusConfig} from '@features/requests/config/status-messages';

interface StatusInfoCardProps
    extends PageProps<{
        request: OCDRequest;
    }> {
}

export function StatusInfoCard({auth, request}: StatusInfoCardProps) {
    const config = getStatusConfig(request.status.status_code);
    const Icon = config.icon;
    return (
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div className="flex items-start gap-3 mb-4">
                <Icon className="h-6 w-6 text-blue-600 dark:text-blue-400 flex-shrink-0"/>
                <div className="flex-1 min-w-0">
                    <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100 mb-2">
                        Status Information
                    </h3>
                    <Badge color={config.color} className="mt-1">
                        {request.status.status_label}
                    </Badge>
                </div>
            </div>
            {(auth.user && auth.user.id === request.user.id) && (
                <Text className="text-sm text-gray-700 dark:text-gray-300 mb-4 leading-relaxed">
                    {config.message}
                </Text>
            )}
        </div>
    );
}
