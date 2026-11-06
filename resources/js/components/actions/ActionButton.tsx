/**
 * ActionButton Component
 *
 * Renders a single action button from backend action configuration.
 * Handles route navigation, HTTP methods, confirmations, and dialogs.
 */

import React from 'react';
import { Button } from '@ui/primitives/button';
import { router } from '@inertiajs/react';
import { getIconComponent } from '@/utils/icon-mapper';
import type { EntityAction } from '@/types/actions';
import { ActionHandlerGuards } from '@/types/actions';
import { clsx } from 'clsx';

export interface ActionButtonProps {
    action: EntityAction;
    layout?: 'horizontal' | 'vertical';
    className?: string;
    onDialogOpen?: (dialogComponent: string, action: EntityAction) => void;
}

/**
 * ActionButton - Renders a single action based on backend configuration
 * Supports two handler types: 'route' for navigation and 'dialog' for modals
 */
export function ActionButton({
    action,
    layout = 'horizontal',
    className,
    onDialogOpen,
}: Readonly<ActionButtonProps>) {
    const Icon = action.style?.icon ? getIconComponent(action.style.icon) : null;

    const handleClick = () => {
        // Handle dialog actions
        if (ActionHandlerGuards.isDialogHandler(action.metadata)) {
            onDialogOpen?.(action.metadata.dialog_component, action);
            return;
        }

        // Handle route-based actions
        if (!action.route) {
            console.warn(`Action "${action.key}" has no route defined`);
            return;
        }

        // Show confirmation if required
        if (action.confirm) {
            if (!window.confirm(action.confirm)) {
                return;
            }
        }

        // Handle different HTTP methods
        const method = action.method.toLowerCase() as 'get' | 'post' | 'put' | 'patch' | 'delete';

        // Open in new tab for GET requests with metadata flag
        if (method === 'get' && ActionHandlerGuards.isRouteHandler(action.metadata) && action.metadata.open_in_new_tab) {
            window.open(action.route, '_blank', 'noopener,noreferrer');
            return;
        }

        // Use Inertia router for navigation
        if (method === 'get') {
            router.visit(action.route);
        } else {
            // For POST/PUT/PATCH/DELETE, Inertia expects data as first param, options as second
            router[method](action.route, {} as any, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    // Success handled by backend flash messages
                },
                onError: (errors) => {
                    console.error(`Action "${action.key}" failed:`, errors);
                },
            });
        }
    };

    // Determine button props based on variant
    const isOutline = action.style?.variant === 'outline';
    const isPlain = action.style?.variant === 'plain';

    // Button props - plain buttons don't use color prop
    const buttonProps: any = {
        onClick: handleClick,
        disabled: !action.enabled,
        className: clsx(
            'flex items-center gap-2',
            layout === 'vertical' && 'w-full justify-center',
            className
        ),
    };

    if (isPlain) {
        buttonProps.plain = true;
    } else if (isOutline) {
        buttonProps.outline = true;
        buttonProps.color = action.style?.color;
    } else if (action.style?.color) {
        buttonProps.color = action.style.color;
    }

    return (
        <Button {...buttonProps}>
            {Icon && <Icon data-slot="icon" />}
            {action.label}
        </Button>
    );
}