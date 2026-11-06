import React, { useState } from 'react';
import { clsx } from 'clsx';
import { router } from '@inertiajs/react';
import { RequestOffer } from '@features/offers';
import { ActionButton } from '@/components/actions/ActionButton';
import { FileUploadDialog } from '@/components/dialogs/FileUploadDialog';
import { ActionHandlerGuards, type EntityAction } from '@/types/actions';

export interface OfferActionButtonsProps {
    offer: RequestOffer;
    className?: string;
    layout?: 'horizontal' | 'vertical';
}

/**
 * OfferActionButtons - Renders action buttons for offers
 *
 * This component uses the Action Provider Pattern to render backend-driven actions.
 * Actions are determined server-side based on context, permissions, and entity state.
 * Handles FileUploadDialog through the dialog handler architecture.
 */
export function OfferActionButtons({
    offer,
    className,
    layout = 'horizontal',
}: Readonly<OfferActionButtonsProps>) {
    const [fileUploadDialogOpen, setFileUploadDialogOpen] = useState(false);
    const [currentFileUploadAction, setCurrentFileUploadAction] = useState<EntityAction | null>(null);

    // Use actions from backend if available, otherwise return null
    const actions = offer.actions || [];

    // Don't render anything if no actions are available
    if (actions.length === 0) {
        return null;
    }

    /**
     * Handle dialog open requests from ActionButton
     */
    const handleDialogOpen = (dialogComponent: string, action: EntityAction) => {
        if (dialogComponent === 'FileUploadDialogProps') {
            setCurrentFileUploadAction(action);
            setFileUploadDialogOpen(true);
        }
    };

    /**
     * Close file upload dialog
     */
    const closeFileUploadDialog = () => {
        setFileUploadDialogOpen(false);
        setCurrentFileUploadAction(null);
    };

    /**
     * Get file upload props from action metadata
     */
    const getFileUploadProps = () => {
        if (!currentFileUploadAction || !ActionHandlerGuards.isDialogHandler(currentFileUploadAction.metadata)) {
            return null;
        }
        return currentFileUploadAction.metadata.dialog_props;
    };

    const fileUploadProps = getFileUploadProps();

    return (
        <>
            <div
                className={clsx(
                    'offer-actions-buttons flex gap-3',
                    layout === 'horizontal' ? 'flex-wrap items-center justify-end' : 'flex-col items-stretch',
                    className
                )}
                role="group"
                aria-label="Offer action buttons"
            >
                {actions.map((action) => (
                    <ActionButton
                        key={action.key}
                        action={action}
                        layout={layout}
                        onDialogOpen={handleDialogOpen}
                    />
                ))}
            </div>

            {/* File Upload Dialog */}
            {fileUploadDialogOpen && fileUploadProps && (
                <FileUploadDialog
                    isOpen={fileUploadDialogOpen}
                    onClose={closeFileUploadDialog}
                    fileUploadMeta={fileUploadProps as any}
                    onSuccess={() => {
                        closeFileUploadDialog();
                        router.reload(); // Reload to show uploaded document
                    }}
                />
            )}
        </>
    );
}