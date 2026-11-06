import React from 'react';
import { clsx } from 'clsx';
import {OCDRequest} from '../types/request.types';
import {ActionButton} from '@/components/actions/ActionButton';

export interface RequestShowActionButtonsProps {
    request: OCDRequest;
    auth?: import('@/types').Auth;
    className?: string;
    layout?: 'horizontal' | 'vertical';
    onDialogOpen?: (dialogComponent: string, action: any) => void;
}

/**
 * RequestShowActionButtons - Renders action buttons for the Request Show page
 *
 * This component uses the Action Provider Pattern to render backend-driven actions.
 * Actions are determined server-side based on context, permissions, and entity state.
 */
export function RequestShowActionButtons({
    request,
    auth,
    className,
    layout = 'horizontal',
    onDialogOpen,
}: Readonly<RequestShowActionButtonsProps>) {

    // Use actions from backend if available, otherwise return null
    const actions = request.actions || [];

    // Don't render anything if no actions are available
    if (actions.length === 0) {
        return null;
    }

    return (
        <div
            className={clsx(
                'actions-buttons flex gap-3',
                layout === 'horizontal' ? 'flex-wrap items-center justify-end mt-6' : 'flex-col items-stretch',
                className
            )}
            role="group"
            aria-label="Request action buttons"
        >
            {actions.map((action) => (
                <ActionButton
                    key={action.key}
                    action={action}
                    layout={layout}
                    onDialogOpen={onDialogOpen}
                />
            ))}
        </div>
    );
}
