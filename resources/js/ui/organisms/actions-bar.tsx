import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@ui/primitives/button';
import { ActionButton } from '@/types';
import { useConfirmation } from '@ui/organisms/confirmation';
import * as HeroIcons from '@heroicons/react/16/solid';

interface ActionsBarProps {
    actions?: ActionButton[];
    className?: string;
}

export function ActionsBar({ actions = [], className = '' }: ActionsBarProps) {
    const { confirm } = useConfirmation();
    const [downloadingActions, setDownloadingActions] = useState<Set<string>>(new Set());

    if (actions.length === 0) return null;

    const handleDownload = (action: ActionButton, event?: React.MouseEvent) => {
        if (event) {
            event.preventDefault();
        }

        const actionKey = action.href;
        if (downloadingActions.has(actionKey)) return;

        setDownloadingActions(prev => new Set(prev).add(actionKey));

        try {
            window.location.href = action.href;
            setTimeout(() => {
                setDownloadingActions(prev => {
                    const next = new Set(prev);
                    next.delete(actionKey);
                    return next;
                });
            }, 1500);
        } catch (error) {
            console.error('Download failed:', error);
            setDownloadingActions(prev => {
                const next = new Set(prev);
                next.delete(actionKey);
                return next;
            });
        }
    };

    const handleAction = async (action: ActionButton, event?: React.MouseEvent) => {
        // Prevent default behavior to avoid conflicts
        if (event) {
            event.preventDefault();
        }

        // Default method is GET - should not reach here for GET methods
        if (!action.method || action.method === 'GET') {
            return;
        }

        // Handle confirmation for destructive actions
        if (action.confirm) {
            const confirmed = await confirm({
                title: 'Confirm Action',
                message: action.confirm,
                type: action.variant === 'danger' ? 'danger' : 'warning',
                confirmText: 'Confirm',
                confirmButtonColor: action.variant === 'danger' ? 'red' : 'blue',
            });
            
            if (!confirmed) {
                return;
            }
        }

        // Use Inertia router for non-GET methods
        const routerMethod = action.method.toLowerCase() as 'post' | 'put' | 'patch' | 'delete';

        const routerOptions = {
            data: action.data,
            preserveScroll: false,
            onError: (errors: any) => {
                console.error('Action failed:', errors);
            }
        };

        try {
            if (routerMethod === 'delete') {
                router.delete(action.href, routerOptions);
            } else if (routerMethod === 'post') {
                router.post(action.href, action.data || {}, {
                    preserveScroll: false,
                    onError: (errors: any) => {
                        console.error('POST action failed:', errors);
                    }
                });
            } else if (routerMethod === 'put') {
                router.put(action.href, action.data || {}, {
                    preserveScroll: false,
                    onError: (errors: any) => {
                        console.error('PUT action failed:', errors);
                    }
                });
            } else if (routerMethod === 'patch') {
                router.patch(action.href, action.data || {}, {
                    preserveScroll: false,
                    onError: (errors: any) => {
                        console.error('PATCH action failed:', errors);
                    }
                });
            }
        } catch (error) {
            console.error('Router action error:', error);
        }
    };

    return (
        <div className={`flex items-center gap-2 mb-6 ${className}`.trim()}>
            {actions.map((action, index) => {
                const Icon = action.icon ? HeroIcons[action.icon as keyof typeof HeroIcons] : null;
                const isGetRequest = !action.method || action.method === 'GET';
                const isLinkAction = action.method === 'LINK';
                const isDownloadAction = action.method === 'DOWNLOAD';
                const isDownloading = downloadingActions.has(action.href);

                // Render anchor tag for LINK method
                if (isLinkAction) {
                    return (
                        <a
                            key={action.label + index}
                            href={action.href}
                            className={`inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md transition-colors
                                ${getLinkClasses(action.variant)}`}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            {Icon && <Icon className="size-4" data-slot="icon" />}
                            {action.label}
                        </a>
                    );
                }

                // Render download button with loading state
                if (isDownloadAction) {
                    return (
                        <Button
                            key={action.label + index}
                            onClick={(event: React.MouseEvent) => handleDownload(action, event)}
                            color={getButtonColor(action.variant)}
                            disabled={isDownloading}
                            className={`--color-firefly-900 ${isDownloading ? 'opacity-75 cursor-wait' : ''}`}
                        >
                            {isDownloading ? (
                                <>
                                    <svg
                                        className="animate-spin -ml-1 mr-2 h-4 w-4"
                                        data-slot="icon"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle
                                            className="opacity-25"
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            strokeWidth="4"
                                        />
                                        <path
                                            className="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                        />
                                    </svg>
                                    Downloading...
                                </>
                            ) : (
                                <>
                                    {Icon && <Icon className="size-4" data-slot="icon" />}
                                    {action.label}
                                </>
                            )}
                        </Button>
                    );
                }

                return (
                    <Button
                        key={action.label + index}
                        href={isGetRequest ? action.href : undefined}
                        onClick={!isGetRequest ? (event: React.MouseEvent) => handleAction(action, event) : undefined}
                        color={getButtonColor(action.variant)}
                        className={"--color-firefly-900"}
                    >
                        {Icon && <Icon className="size-4" data-slot="icon" />}
                        {action.label}
                    </Button>
                );
            })}
        </div>
    );
}

function getButtonColor(variant?: string) {
    switch (variant) {
        case 'primary':
            return 'firefly';
        case 'secondary':
            return 'zinc';
        case 'danger':
            return 'red';
        default:
            return 'zinc';
    }
}

function getLinkClasses(variant?: string) {
    const baseClasses = 'hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none';

    switch (variant) {
        case 'primary':
            return `${baseClasses} bg-firefly-600 text-white hover:bg-firefly-700 focus:ring-firefly-500 border-transparent`;
        case 'secondary':
            return `${baseClasses} bg-firefly-600 text-white hover:bg-firefly-700 focus:ring-zinc-500 border-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700 dark:border-zinc-600`;
        case 'danger':
            return `${baseClasses} bg-firefly-600 text-white hover:bg-firefly-700 focus:ring-red-500 border-transparent`;
        default:
            return `${baseClasses} bg-firefly-600 text-white hover:bg-firefly-700 focus:ring-zinc-500 border-zinc-300 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700 dark:border-zinc-600`;
    }
}
