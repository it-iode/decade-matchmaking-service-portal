import React from 'react';
import { Dropdown, DropdownButton, DropdownItem, DropdownMenu, DropdownDivider } from '@ui/primitives/dropdown';
import { ChevronDownIcon } from '@heroicons/react/16/solid';
import { router } from '@inertiajs/react';
import { getIconComponent } from '@/utils/icon-mapper';
import type { EntityAction } from '@/types/actions';
import { ActionHandlerGuards } from '@/types/actions';

// Simple action type for backward compatibility
export interface Action {
    key: string;
    label: string;
    onClick: () => void;
    divider?: boolean;
    disabled?: boolean;
}

interface DropdownActionsProps {
    actions: Action[] | EntityAction[];
    onDialogOpen?: (dialogComponent: string, action: EntityAction) => void;
}

/**
 * Type guard to check if an action is an EntityAction
 */
function isEntityAction(action: Action | EntityAction): action is EntityAction {
    return 'route' in action && 'method' in action;
}

/**
 * DropdownActions - Generic dropdown for entity actions
 * Supports two action types:
 * 1. EntityAction (route and dialog handlers)
 * 2. Simple Action (legacy support with onClick callback)
 */
export function DropdownActions({ actions, onDialogOpen }: DropdownActionsProps) {
    if (!actions || actions.length === 0) {
        return null;
    }

    /**
     * Handle EntityAction execution with route navigation, confirmations, and dialogs
     */
    const handleEntityAction = (action: EntityAction) => {
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
            // For POST/PUT/PATCH/DELETE
            router[method](action.route, (action.data ?? {}) as any, {
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

    /**
     * Render a dropdown item for an EntityAction
     */
    const renderEntityActionItem = (action: EntityAction) => {
        const Icon = action.style?.icon ? getIconComponent(action.style.icon) : null;
        const isDanger = action.style?.color === 'red';

        return (
            <DropdownItem
                onClick={() => handleEntityAction(action)}
                disabled={!action.enabled}
                className={isDanger ? 'text-red-600 hover:bg-red-50' : undefined}
            >
                {Icon && <Icon data-slot="icon" />}
                {action.label}
            </DropdownItem>
        );
    };

    /**
     * Render a dropdown item for a simple Action
     */
    const renderSimpleActionItem = (action: Action) => {
        return (
            <DropdownItem
                onClick={action.onClick}
                disabled={action.disabled}
            >
                {action.label}
            </DropdownItem>
        );
    };

    return (
        <Dropdown>
            <DropdownButton color="white" className="flex items-center gap-2">
                Actions
                <ChevronDownIcon className="h-4 w-4" />
            </DropdownButton>
            <DropdownMenu anchor="bottom end">
                {actions.map((action, index) => (
                    <React.Fragment key={action.key}>
                        {/* Simple actions support divider property */}
                        {!isEntityAction(action) && action.divider && index > 0 && <DropdownDivider />}

                        {/* Render based on action type */}
                        {isEntityAction(action)
                            ? renderEntityActionItem(action)
                            : renderSimpleActionItem(action)
                        }
                    </React.Fragment>
                ))}
            </DropdownMenu>
        </Dropdown>
    );
}
