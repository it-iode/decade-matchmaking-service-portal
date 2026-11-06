import React from 'react';
import { Opportunity } from '@/types';
import { formatDate, opportunityStatusBadgeRenderer } from '@shared/utils';
import { Badge } from '@ui/primitives/badge';


// Column configuration for Admin interface
export const adminColumns = [
    {
        key: 'id',
        label: 'ID',
        sortable: true,
        sortField: 'id' as const,
        render: (opportunity: Opportunity) => (
            <span className="font-medium">#{opportunity.id}</span>
        )
    },
    {
        key: 'title',
        label: 'Title',
        sortable: true,
        sortField: 'title' as const,
        render: (opportunity: Opportunity) => (
            <div className="max-w-xs">
                <div className="line-clamp-2 font-medium">{opportunity.title}</div>
            </div>
        )
    },
    {
        key: 'type',
        label: 'Type',
        sortable: true,
        sortField: 'type' as const,
        render: (opportunity: Opportunity) => (
            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                {opportunity.type.label}
            </span>
        )
    },
    {
        key: 'user',
        label: 'Submitted By',
        render: (opportunity: Opportunity) => (
            <div className="flex flex-col max-w-xs">
                <span className="font-medium truncate">{opportunity.user?.name ?? 'N/A'}</span>
                <span className="text-xs text-gray-500 truncate">{opportunity.user?.email ?? ''}</span>
            </div>
        )
    },
    {
        key: 'closing_date',
        label: 'Closing Date',
        sortable: true,
        sortField: 'closing_date' as const,
        render: (opportunity: Opportunity) => (
            <span className="text-zinc-500">
                {opportunity.closing_date ? formatDate(opportunity.closing_date) : 'N/A'}
            </span>
        )
    },
    {
        key: 'status',
        label: 'Status',
        sortable: true,
        sortField: 'status' as const,
        render: (opportunity: Opportunity) => opportunityStatusBadgeRenderer(opportunity)
    }
];

// Column configuration for Partner interface (for partner's own opportunities)
export const partnerColumns = [
    {
        key: 'title',
        label: 'Opportunity Title',
        render: (opportunity: Opportunity) => (
            <div className="max-w-md">
                <div className="font-medium line-clamp-2">{opportunity.title}</div>
                <div className="text-sm text-gray-500 line-clamp-2">
                    {opportunity.summary}
                </div>
            </div>
        )
    },
    {
        key: 'key_words',
        label: 'Keywords',
        render: (opportunity: Opportunity) => (
            opportunity.key_words?.length ? (
                <div className="flex flex-col items-start gap-1 max-w-xs">
                    {opportunity.key_words.slice(0, 3).map((keyword: string) => (
                        <Badge key={keyword} color="blue">
                            {keyword}
                        </Badge>
                    ))}
                </div>
            ) : (
                <span className="text-zinc-500">—</span>
            )
        )
    },
    {
        key: 'type',
        label: 'Type',
        render: (opportunity: Opportunity) => (
            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                {opportunity.type.label}
            </span>
        )
    },
    {
        key: 'closing_date',
        label: 'Closing Date',
        sortable: true,
        sortField: 'closing_date' as const,
        render: (opportunity: Opportunity) => (
            <div>
                <div>{opportunity.closing_date ?
                    formatDate(opportunity.closing_date, 'en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    }) : 'No deadline'
                }</div>
                {opportunity.closing_date && (
                    <div className="text-gray-500 text-xs">
                        {new Date(opportunity.closing_date) > new Date() ? 'Open' : 'Closed'}
                    </div>
                )}
            </div>
        )
    },
    {
        key: 'status',
        label: 'Status',
        render: (opportunity: Opportunity) => (
            <div className="flex flex-col items-start">
                {opportunityStatusBadgeRenderer(opportunity)}
            </div>
        )
    }
];
