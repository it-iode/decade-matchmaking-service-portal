import React from 'react';
import { OCDRequest } from '@/types';
import { formatDate, requestStatusBadgeRenderer } from '@shared/utils';


// Column configuration for Admin interface
export const adminColumns = [
    {
        key: 'id',
        label: 'ID',
        sortable: true,
        sortField: 'id' as const,
        render: (request: OCDRequest) => (
            <span className="font-medium">#{request.id}</span>
        )
    },
    {
        key: 'title',
        label: 'Title',
        render: (request: OCDRequest) => (
            <div className="max-w-xs">
                <div className="line-clamp-2">{request.detail?.capacity_development_title || 'No Title'}</div>
            </div>
        )
    },
    {
        key: 'user',
        label: 'Submitted By',
        sortable: true,
        sortField: 'user' as const,
        render: (request: OCDRequest) => (
            <div className="flex flex-col max-w-xs">
                <span className="font-medium truncate">{request.user.name}</span>
                <span className="text-xs text-gray-500 truncate">{request.user.email}</span>
            </div>
        )
    },
    {
        key: 'created_at',
        label: 'Submitted At',
        sortable: true,
        sortField: 'created_at' as const,
        render: (request: OCDRequest) => (
            <span className="text-zinc-500">{formatDate(request.created_at)}</span>
        )
    },
    {
        key: 'status',
        label: 'Status',
        sortable: false,
        render: (request: OCDRequest) => requestStatusBadgeRenderer(request.status)
    }
];

// Column configuration for User interface (different columns for user's own requests)
export const userColumns = [
    {
        key: 'title',
        label: 'Request Title',
        render: (request: OCDRequest) => (
            <div className="max-w-md">
                <div className="font-medium line-clamp-2">{request.detail?.capacity_development_title || 'No Title'}</div>
                <div className="text-sm text-gray-500 line-clamp-2">
                    {request.detail?.gap_description}
                </div>
            </div>
        )
    },
    {
        key: 'created_at',
        label: 'Submitted',
        sortable: true,
        sortField: 'created_at' as const,
        render: (request: OCDRequest) => (
            <div>
                <div>{formatDate(request.created_at, 'en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                })}</div>
                <div className="text-gray-500">{formatDate(request.created_at, 'en-US', {
                    hour: '2-digit',
                    minute: '2-digit'
                })}</div>
            </div>
        )
    },
    {
        key: 'status',
        label: 'Status',
        render: (request: OCDRequest) => (
            <div className="flex flex-col items-start">
                {requestStatusBadgeRenderer(request.status)}
            </div>
        )
    },
    {
        key: 'completion_date',
        label: 'Target Completion',
        render: (request: OCDRequest) => (
            <span className="text-gray-600">
                {request.detail?.completion_date ?
                    formatDate(request.detail?.completion_date, 'en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    }) :
                    'Not specified'
                }
            </span>
        )
    }
];

