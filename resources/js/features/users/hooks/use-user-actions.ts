import { useState, useCallback } from 'react';
import { UserManagement, UserAction } from '@/types';

export function useUserActions() {
    const [isRoleDialogOpen, setIsRoleDialogOpen] = useState(false);
    const [isBlockDialogOpen, setIsBlockDialogOpen] = useState(false);
    const [isDetailsDialogOpen, setIsDetailsDialogOpen] = useState(false);
    const [selectedUser, setSelectedUser] = useState<UserManagement | null>(null);
    const [blockAction, setBlockAction] = useState<'block' | 'unblock'>('block');

    const handleViewDetails = useCallback((user: UserManagement) => {
        setSelectedUser(user);
        setIsDetailsDialogOpen(true);
    }, []);

    const handleAssignRoles = useCallback((user: UserManagement) => {
        setSelectedUser(user);
        setIsRoleDialogOpen(true);
    }, []);

    const handleBlockUser = useCallback((user: UserManagement) => {
        setSelectedUser(user);
        setBlockAction('block');
        setIsBlockDialogOpen(true);
    }, []);

    const handleUnblockUser = useCallback((user: UserManagement) => {
        setSelectedUser(user);
        setBlockAction('unblock');
        setIsBlockDialogOpen(true);
    }, []);

    const getActionsForUser = useCallback((user: UserManagement): UserAction[] => {
        const actions: UserAction[] = [];

        // View Details
        actions.push({
            key: 'view-details',
            label: 'View Details',
            icon: 'EyeIcon',
            onClick: () => handleViewDetails(user)
        });

        // Assign Roles
        actions.push({
            key: 'assign-roles',
            label: 'Assign Roles',
            icon: 'ShieldCheckIcon',
            onClick: () => handleAssignRoles(user),
            divider: true
        });

        // Block/Unblock
        if (user.status.value === 'blocked') {
            actions.push({
                key: 'unblock-user',
                label: 'Unblock User',
                icon: 'CheckCircleIcon',
                onClick: () => handleUnblockUser(user),
                className: 'text-green-600'
            });
        } else {
            actions.push({
                key: 'block-user',
                label: 'Block User',
                icon: 'XCircleIcon',
                onClick: () => handleBlockUser(user),
                className: 'text-red-600'
            });
        }

        return actions;
    }, [handleViewDetails, handleAssignRoles, handleBlockUser, handleUnblockUser]);

    return {
        isRoleDialogOpen,
        isBlockDialogOpen,
        isDetailsDialogOpen,
        selectedUser,
        blockAction,
        closeRoleDialog: () => setIsRoleDialogOpen(false),
        closeBlockDialog: () => setIsBlockDialogOpen(false),
        closeDetailsDialog: () => setIsDetailsDialogOpen(false),
        getActionsForUser
    };
}
