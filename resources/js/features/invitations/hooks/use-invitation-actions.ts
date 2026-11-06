import { useCallback } from 'react';
import { router } from '@inertiajs/react';
import { Action } from '@ui/organisms/data-table/common';
import { Invitation } from '../types/invitation.types';
import { useConfirmation, useDeleteConfirmation } from '@ui/organisms/confirmation';

export function useInvitationActions() {
    const { confirm } = useConfirmation();
    const deleteConfirmation = useDeleteConfirmation();

    const handleResend = useCallback(async (invitation: Invitation) => {
        await confirm({
            title: 'Resend Invitation?',
            message: `This will send a new invitation email to ${invitation.email}. The previous invitation link will be invalidated.`,
            type: 'warning',
            confirmText: 'Resend',
            confirmButtonColor: 'orange',
            onConfirm: () => {
                router.post(route('admin.invitations.resend', { invitation: invitation.id }), {}, {
                    preserveScroll: true,
                });
            }
        });
    }, [confirm]);

    const handleCancel = useCallback(async (invitation: Invitation) => {
        await deleteConfirmation('invitation', () => {
            router.delete(route('admin.invitations.destroy', { invitation: invitation.id }), {
                preserveScroll: true,
            });
        });
    }, [deleteConfirmation]);

    const getActionsForInvitation = useCallback((invitation: Invitation): Action[] => {
        const actions: Action[] = [];

        // Only show actions for non-accepted invitations
        if (invitation.is_resendable) {
            actions.push({
                key: 'resend',
                label: 'Resend Invitation',
                onClick: () => handleResend(invitation),
                divider: false,
            });
        }

        if (invitation.is_cancellable) {
            actions.push({
                key: 'cancel',
                label: 'Cancel Invitation',
                onClick: () => handleCancel(invitation),
                divider: actions.length > 0,
            });
        }

        return actions;
    }, [handleResend, handleCancel]);

    return {
        getActionsForInvitation,
        handleResend,
        handleCancel,
    };
}
