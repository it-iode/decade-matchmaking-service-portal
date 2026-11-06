import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { DropdownActions } from '@ui/organisms/data-table/common';
import { FileUploadDialog } from '@/components/dialogs/FileUploadDialog';
import { ActionHandlerGuards, type EntityAction } from '@/types/actions';
import type { RequestOffer } from '../types/offer.types';

interface OfferDropdownActionsProps {
    offer: RequestOffer;
}

/**
 * OfferDropdownActions - Entity-specific dropdown for offer actions
 *
 * Encapsulates action dropdown and FileUploadDialog management for offers.
 * Uses the Action Provider Pattern with backend-driven actions.
 */
export function OfferDropdownActions({ offer }: Readonly<OfferDropdownActionsProps>) {
    const [fileUploadDialogOpen, setFileUploadDialogOpen] = useState(false);
    const [currentFileUploadAction, setCurrentFileUploadAction] = useState<EntityAction | null>(null);

    const handleDialogOpen = (dialogComponent: string, action: EntityAction) => {
        if (dialogComponent === 'FileUploadDialogProps') {
            setCurrentFileUploadAction(action);
            setFileUploadDialogOpen(true);
        }
    };

    const closeFileUploadDialog = () => {
        setFileUploadDialogOpen(false);
        setCurrentFileUploadAction(null);
    };

    const getFileUploadProps = () => {
        if (!currentFileUploadAction || !ActionHandlerGuards.isDialogHandler(currentFileUploadAction.metadata)) {
            return null;
        }
        return currentFileUploadAction.metadata.dialog_props;
    };

    const fileUploadProps = getFileUploadProps();

    return (
        <>
            <DropdownActions
                actions={offer.actions}
                onDialogOpen={handleDialogOpen}
            />

            {fileUploadDialogOpen && fileUploadProps && (
                <FileUploadDialog
                    isOpen={fileUploadDialogOpen}
                    onClose={closeFileUploadDialog}
                    fileUploadMeta={fileUploadProps as any}
                    onSuccess={() => {
                        closeFileUploadDialog();
                        router.reload();
                    }}
                />
            )}
        </>
    );
}
