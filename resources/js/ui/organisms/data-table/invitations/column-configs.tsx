import React from 'react';
import { Invitation } from '@/features/invitations';
import { formatDate } from '@shared/utils';
import { Badge } from '@ui/primitives/badge';
import { ClockIcon, ExclamationTriangleIcon } from '@heroicons/react/16/solid';

type SortField = 'id' | 'name' | 'email' | 'expires_at' | 'created_at';

interface TableColumn {
    key: string;
    label: string;
    sortable?: boolean;
    sortField?: SortField;
    render: (invitation: Invitation) => React.ReactNode;
    className?: string;
    headerClassName?: string;
}

/**
 * Get badge color based on status value
 */
const getStatusBadgeColor = (status: string): 'amber' | 'green' | 'zinc' => {
    switch (status) {
        case 'pending':
            return 'amber';
        case 'accepted':
            return 'green';
        case 'expired':
            return 'zinc';
        default:
            return 'zinc';
    }
};

/**
 * Check if invitation expires within 24 hours
 */
const isExpiringSoon = (expiresAt: string): boolean => {
    const expiresDate = new Date(expiresAt);
    const now = new Date();
    const hoursUntilExpiry = (expiresDate.getTime() - now.getTime()) / (1000 * 60 * 60);
    return hoursUntilExpiry > 0 && hoursUntilExpiry <= 24;
};

/**
 * Admin columns configuration for invitations table
 */
export const invitationColumns: TableColumn[] = [
    {
        key: 'id',
        label: 'ID',
        sortable: true,
        sortField: 'id' as const,
        render: (invitation: Invitation) => (
            <span className="font-medium text-zinc-600">#{invitation.id}</span>
        ),
    },
    {
        key: 'invitee',
        label: 'Invitee',
        sortable: true,
        sortField: 'name' as const,
        render: (invitation: Invitation) => (
            <div className="flex flex-col max-w-xs">
                <span className="font-medium truncate">{invitation.name}</span>
                <span className="text-xs text-zinc-500 truncate">{invitation.email}</span>
            </div>
        ),
    },
    {
        key: 'status',
        label: 'Status',
        sortable: false,
        render: (invitation: Invitation) => (
            <Badge color={getStatusBadgeColor(invitation.status.value)}>
                {invitation.status.label}
            </Badge>
        ),
    },
    {
        key: 'inviter',
        label: 'Invited By',
        sortable: false,
        render: (invitation: Invitation) => (
            <div className="flex flex-col max-w-xs">
                {invitation.inviter ? (
                    <>
                        <span className="font-medium truncate">{invitation.inviter.name}</span>
                        <span className="text-xs text-zinc-500 truncate">{invitation.inviter.email}</span>
                    </>
                ) : (
                    <span className="text-zinc-400">-</span>
                )}
            </div>
        ),
    },
    {
        key: 'expires_at',
        label: 'Expires',
        sortable: true,
        sortField: 'expires_at' as const,
        render: (invitation: Invitation) => {
            const expiringSoon = invitation.status.value === 'pending' && isExpiringSoon(invitation.expires_at);
            const isExpired = invitation.status.value === 'expired';

            return (
                <div className="flex items-center gap-1.5">
                    {expiringSoon && (
                        <ExclamationTriangleIcon className="size-4 text-amber-500" />
                    )}
                    {isExpired && (
                        <ClockIcon className="size-4 text-zinc-400" />
                    )}
                    <span className={`${isExpired ? 'text-zinc-400' : expiringSoon ? 'text-amber-600 font-medium' : 'text-zinc-600'}`}>
                        {formatDate(invitation.expires_at, 'en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric',
                        })}
                    </span>
                </div>
            );
        },
    },
    {
        key: 'created_at',
        label: 'Sent',
        sortable: true,
        sortField: 'created_at' as const,
        render: (invitation: Invitation) => (
            <div className="flex flex-col">
                <span className="text-zinc-600">
                    {formatDate(invitation.created_at, 'en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                    })}
                </span>
                <span className="text-xs text-zinc-400">
                    {formatDate(invitation.created_at, 'en-US', {
                        hour: '2-digit',
                        minute: '2-digit',
                    })}
                </span>
            </div>
        ),
    },
];
