import React from 'react'
import clsx from 'clsx'
import { Heading } from '@ui/primitives/heading'
import { Text } from '@ui/primitives/text'
import { Divider } from '@ui/primitives/divider'
import { Button, type ButtonColor } from '@ui/primitives/button'

export interface PageHeaderAction {
    id: string
    label: string
    icon?: React.ComponentType<{ className?: string; 'data-slot'?: string }>
    onClick?: () => void
    href?: string
    variant?: 'solid' | 'outline' | 'plain'
    color?: ButtonColor
    disabled?: boolean
    className?: string
}

export interface PageHeaderProps {
    /**
     * The main page title
     */
    title: string

    /**
     * Optional subtitle/description text
     */
    subtitle?: string

    /**
     * Action buttons to display in header
     * Can be single action or array of actions
     */
    actions?: PageHeaderAction | PageHeaderAction[]

    /**
     * Show divider below header
     * @default true
     */
    showDivider?: boolean

    /**
     * Use soft divider variant
     * @default false
     */
    softDivider?: boolean

    /**
     * Custom className for container
     */
    className?: string

    /**
     * Custom title level
     * @default 1
     */
    titleLevel?: 1 | 2 | 3 | 4 | 5 | 6

    /**
     * Layout mode for actions
     * @default 'row' - Actions inline with subtitle
     * 'stacked' - Actions below subtitle on mobile, inline on desktop
     */
    layout?: 'row' | 'stacked'

    /**
     * Additional content to render below subtitle
     */
    children?: React.ReactNode
}

function renderAction(action: PageHeaderAction) {
    const Icon = action.icon

    const buttonProps: Record<string, unknown> = {
        disabled: action.disabled,
        className: action.className,
    }

    if (action.variant === 'outline') {
        buttonProps.outline = true
    } else if (action.variant === 'plain') {
        buttonProps.plain = true
    } else {
        // Solid variant (default) - apply color if specified
        if (action.color) {
            buttonProps.color = action.color
        }
    }

    const content = (
        <>
            {Icon && <Icon data-slot="icon" />}
            {action.label}
        </>
    )

    if (action.href) {
        return (
            <Button key={action.id} {...buttonProps} href={action.href}>
                {content}
            </Button>
        )
    }

    return (
        <Button key={action.id} {...buttonProps} onClick={action.onClick}>
            {content}
        </Button>
    )
}

export function PageHeader({
    title,
    subtitle,
    actions,
    showDivider = true,
    softDivider = false,
    className,
    titleLevel = 1,
    layout = 'row',
    children,
}: Readonly<PageHeaderProps>) {
    // Normalize actions to array
    const actionItems = actions
        ? Array.isArray(actions)
            ? actions
            : [actions]
        : []

    const hasActions = actionItems.length > 0
    const hasSubtitle = Boolean(subtitle)

    return (
        <div className={clsx('space-y-4', className)}>
            {/* Title Row */}
            <div
                className={clsx(
                    'flex gap-4',
                    layout === 'stacked'
                        ? 'flex-col sm:flex-row sm:items-center sm:justify-between'
                        : 'items-center justify-between'
                )}
            >
                <div className="min-w-0 flex-1">
                    <Heading level={titleLevel}>{title}</Heading>
                </div>

                {/* Actions when no subtitle - inline with title */}
                {hasActions && !hasSubtitle && (
                    <div
                        className={clsx(
                            'flex flex-shrink-0 gap-2',
                            layout === 'stacked' && 'w-full sm:w-auto'
                        )}
                    >
                        {actionItems.map((action) => renderAction(action))}
                    </div>
                )}
            </div>

            {/* Divider */}
            {showDivider && <Divider soft={softDivider} />}

            {/* Subtitle + Actions Row */}
            {hasSubtitle && (
                <div
                    className={clsx(
                        'flex gap-4',
                        hasActions
                            ? 'flex-col sm:flex-row sm:items-start sm:justify-between'
                            : 'flex-col'
                    )}
                >
                    <div className="min-w-0 flex-1">
                        <Text className="text-gray-600 dark:text-gray-400">
                            {subtitle}
                        </Text>
                    </div>

                    {hasActions && (
                        <div className="flex flex-shrink-0 gap-2">
                            {actionItems.map((action) => renderAction(action))}
                        </div>
                    )}
                </div>
            )}

            {/* Additional custom content */}
            {children}
        </div>
    )
}

// Default export for backward compatibility
export default PageHeader
