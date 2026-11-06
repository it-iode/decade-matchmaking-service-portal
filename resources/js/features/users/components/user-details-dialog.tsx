import React from 'react';
import { Dialog, DialogTitle, DialogBody, DialogActions } from '@ui/primitives/dialog';
import { Button } from '@ui/primitives/button';
import { Badge } from '@ui/primitives/badge';
import { Avatar } from '@ui/primitives/avatar';
import { UserManagement, UserStatistics, UserActivity } from '@/types';
import { formatDate } from '@shared/utils';

interface UserDetailsDialogProps {
    isOpen: boolean;
    onClose: () => void;
    user: UserManagement | null;
    statistics?: UserStatistics;
    activity?: UserActivity;
}

export function UserDetailsDialog({ isOpen, onClose, user, statistics, activity }: UserDetailsDialogProps) {
    if (!user) return null;

    return (
        <Dialog open={isOpen} onClose={onClose} size="2xl">
            <DialogTitle>User Details</DialogTitle>
            <DialogBody>
                {/* User Info */}
                <div className="flex items-center gap-4 mb-6">
                    <Avatar src={user.avatar_url} initials={user.name.charAt(0)} className="size-16" />
                    <div>
                        <h3 className="text-lg font-semibold">{user.name}</h3>
                        <p className="text-sm text-zinc-500">{user.email}</p>
                        <div className="flex gap-2 mt-1">
                            <Badge color={user.status.color as any}>{user.status.label}</Badge>
                            {user.email_verified && (
                                <Badge color="green">Verified</Badge>
                            )}
                        </div>
                    </div>
                </div>

                {/* Basic Info */}
                <div className="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <p className="text-sm font-medium text-zinc-700 dark:text-zinc-300">Country</p>
                        <p className="text-sm text-zinc-600 dark:text-zinc-400">{user.country || 'N/A'}</p>
                    </div>
                    <div>
                        <p className="text-sm font-medium text-zinc-700 dark:text-zinc-300">City</p>
                        <p className="text-sm text-zinc-600 dark:text-zinc-400">{user.city || 'N/A'}</p>
                    </div>
                    <div>
                        <p className="text-sm font-medium text-zinc-700 dark:text-zinc-300">Joined</p>
                        <p className="text-sm text-zinc-600 dark:text-zinc-400">{formatDate(user.created_at)}</p>
                    </div>
                    <div>
                        <p className="text-sm font-medium text-zinc-700 dark:text-zinc-300">Auth Provider</p>
                        <p className="text-sm text-zinc-600 dark:text-zinc-400">
                            {user.is_social_user ? user.provider : 'Email'}
                        </p>
                    </div>
                </div>

                {/* Roles */}
                <div className="mb-6">
                    <p className="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Roles</p>
                    <div className="flex flex-wrap gap-2">
                        {user.roles && user.roles.length > 0 ? (
                            user.roles.map((role) => (
                                <Badge key={role.id} color="blue">{role.name}</Badge>
                            ))
                        ) : (
                            <span className="text-sm text-zinc-400">No roles assigned</span>
                        )}
                    </div>
                </div>

                {/* Statistics */}
                {statistics && (
                    <div className="mb-6">
                        <p className="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Statistics</p>
                        <div className="grid grid-cols-3 gap-4">
                            <div className="bg-zinc-50 dark:bg-zinc-800 p-3 rounded-lg">
                                <p className="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                                    {statistics.total_requests}
                                </p>
                                <p className="text-xs text-zinc-600 dark:text-zinc-400">Total Requests</p>
                            </div>
                            <div className="bg-zinc-50 dark:bg-zinc-800 p-3 rounded-lg">
                                <p className="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                                    {statistics.total_offers}
                                </p>
                                <p className="text-xs text-zinc-600 dark:text-zinc-400">Total Offers</p>
                            </div>
                            <div className="bg-zinc-50 dark:bg-zinc-800 p-3 rounded-lg">
                                <p className="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                                    {statistics.total_opportunities}
                                </p>
                                <p className="text-xs text-zinc-600 dark:text-zinc-400">Opportunities</p>
                            </div>
                        </div>
                    </div>
                )}
            </DialogBody>
            <DialogActions>
                <Button onClick={onClose}>Close</Button>
            </DialogActions>
        </Dialog>
    );
}
