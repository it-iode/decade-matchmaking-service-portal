import { useCallback, useState } from 'react';
import { router } from '@inertiajs/react';
import { Action } from '@ui/organisms/data-table/common';
import { RequestSubscription } from '../types/subscription.types';
import { useConfirmation } from '@ui/organisms/confirmation';

export function useSubscriptionActions() {
    const { confirm } = useConfirmation();
    const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);

    const openCreateDialog = useCallback(() => {
        setIsCreateDialogOpen(true);
    }, []);

    const closeCreateDialog = useCallback(() => {
        setIsCreateDialogOpen(false);
    }, []);

    const handleDelete = useCallback(async (subscription: RequestSubscription) => {
        const userName = subscription.user?.name || 'Unknown User';
        const requestTitle = subscription.request?.detail?.capacity_development_title || `Request #${subscription.request_id}`;

        await confirm({
            title: 'Remove Subscription?',
            message: `Are you sure you want to remove the subscription for "${userName}" from "${requestTitle}"? The user will no longer receive updates about this request.`,
            type: 'danger',
            confirmText: 'Remove',
            confirmButtonColor: 'red',
            onConfirm: () => {
                router.post(route('admin.subscriptions.unsubscribe-user'), {
                    user_id: subscription.user_id,
                    request_id: subscription.request_id,
                }, {
                    preserveScroll: true,
                });
            }
        });
    }, [confirm]);

    const getActionsForSubscription = useCallback((subscription: RequestSubscription): Action[] => {
        return [
            {
                key: 'delete',
                label: 'Remove Subscription',
                onClick: () => handleDelete(subscription),
                divider: false,
            },
        ];
    }, [handleDelete]);

    return {
        isCreateDialogOpen,
        openCreateDialog,
        closeCreateDialog,
        getActionsForSubscription,
        handleDelete,
    };
}
