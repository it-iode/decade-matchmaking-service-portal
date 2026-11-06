import React from 'react';
import {PageProps} from '@/types';
import {Badge} from '@ui/primitives/badge';
import {Heading} from '@ui/primitives/heading';
import {OCDRequest} from '@features/requests';
import {getStatusConfig} from '@features/requests/config/status-messages';

interface StatusBannerProps
    extends PageProps<{
        request: OCDRequest;
    }> {
}

export function StatusBanner({request, auth}: StatusBannerProps) {
    const config = getStatusConfig(request.status.status_code);
    const Icon = config.icon;

    return (
        <div className={`rounded-lg p-6 border ${config.bgClass}`}>
            <div className="flex items-start gap-4">
                <Icon className="h-8 w-8 flex-shrink-0 text-current" data-slot="icon"/>
                <div className="flex-1 min-w-0">
                    <div className="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3 mb-2">
                        <Heading level={2} className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Request #{request.id}
                        </Heading>
                        <Badge color={config.color}>{request.status.status_label}</Badge>
                    </div>
                    {(auth.user && auth.user.id === request.user.id) && (
                        <p className="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                            {config.message}
                        </p>
                    )}
                </div>
            </div>
        </div>
    );
}
