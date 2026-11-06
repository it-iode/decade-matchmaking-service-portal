import React, { useState } from 'react';
import { DropdownActions } from '@ui/organisms/data-table/common';
import { UpdateStatusDialog } from '@ui/organisms/dialogs';
import type { EntityAction } from '@/types/actions';
import { ActionHandlerGuards } from '@/types/actions';
import type { OCDRequest, OCDRequestStatus, Context } from '@/types';

interface RequestDropdownActionsProps {
    request: OCDRequest;
    context?: Context;
}

/**
 * RequestDropdownActions - Entity-specific dropdown for request actions
 *
 * Encapsulates action dropdown and dialog management for requests.
 * Uses the new type-safe ActionMetadata system to extract dialog props from action metadata.
 */
export function RequestDropdownActions({ request, context = 'public' }: Readonly<RequestDropdownActionsProps>) {
    const [isStatusDialogOpen, setIsStatusDialogOpen] = useState(false);

    /**
     * Handle dialog open requests from DropdownActions
     */
    const handleDialogOpen = (dialogComponent: string, action: EntityAction) => {
        if (dialogComponent === 'UpdateStatusDialog') {
            setIsStatusDialogOpen(true);
        }
    };

    /**
     * Close the status dialog
     */
    const closeStatusDialog = () => {
        setIsStatusDialogOpen(false);
    };

    /**
     * Extract availableStatuses from the update_status action metadata
     * Using type-safe metadata extraction from the new ActionMetadata system
     */
    const getAvailableStatuses = (): OCDRequestStatus[] => {
        const updateStatusAction = request.actions.find(action => action.key === 'update_status');

        if (updateStatusAction && ActionHandlerGuards.isDialogHandler(updateStatusAction.metadata)) {
            // Type guard ensures metadata.dialog_props is available
            return (updateStatusAction.metadata.dialog_props?.availableStatuses as OCDRequestStatus[]) || [];
        }

        return [];
    };

    return (
        <>
            <DropdownActions
                actions={request.actions}
                onDialogOpen={handleDialogOpen}
            />

            {isStatusDialogOpen && (
                <UpdateStatusDialog
                    request={request}
                    isOpen={isStatusDialogOpen}
                    onClose={closeStatusDialog}
                    availableStatuses={getAvailableStatuses()}
                />
            )}
        </>
    );
}
