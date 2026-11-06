import React from 'react';
import { UserManagement } from '@/types';
import { formatDate } from '@shared/utils';
import { Avatar } from '@ui/primitives/avatar';
import { Badge } from '@ui/primitives/badge';

export const adminUserColumns = [
    {
        key: 'id',
        label: 'ID',
        sortable: true,
        sortField: 'id' as const,
        render: (user: UserManagement) => (
            <span className="font-medium">#{user.id}</span>
        )
    },
    {
        key: 'user',
        label: 'User',
        sortable: true,
        sortField: 'name' as const,
        render: (user: UserManagement) => (
            <div className="flex items-center gap-3">
                <Avatar src={user.avatar_url} initials={user.name.charAt(0)} className="size-10" />
                <div>
                    <div className="font-medium">{user.name}</div>
                    <div className="text-sm text-zinc-500">{user.email}</div>
                </div>
            </div>
        )
    },
    {
        key: 'roles',
        label: 'Roles',
        render: (user: UserManagement) => (
            <div className="flex flex-wrap gap-1">
                {user.roles && user.roles.length > 0 ? (
                    user.roles.map((role) => (
                        <Badge key={role.id} color="blue">
                            {role.name}
                        </Badge>
                    ))
                ) : (
                    <span className="text-sm text-zinc-400">No roles</span>
                )}
            </div>
        )
    },
    {
        key: 'status',
        label: 'Status',
        sortable: true,
        sortField: 'is_blocked' as const,
        render: (user: UserManagement) => (
            <Badge color={user.status.color as any}>
                {user.status.label}
            </Badge>
        )
    },
    {
        key: 'country',
        label: 'Country',
        sortable: true,
        sortField: 'country' as const,
        render: (user: UserManagement) => (
            <span className="text-zinc-500">{user.country || 'N/A'}</span>
        )
    },
    {
        key: 'created_at',
        label: 'Joined',
        sortable: true,
        sortField: 'created_at' as const,
        render: (user: UserManagement) => (
            <span className="text-zinc-500">{formatDate(user.created_at)}</span>
        )
    }
];
