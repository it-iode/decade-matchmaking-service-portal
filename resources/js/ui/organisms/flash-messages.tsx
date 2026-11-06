'use client'

import React, { useEffect, useState } from 'react'
import { usePage } from '@inertiajs/react'
import clsx from 'clsx'
import {
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ExclamationCircleIcon,
    InformationCircleIcon,
    XMarkIcon
} from '@heroicons/react/24/outline'
import { Button } from '@ui/primitives/button'
import { FlashMessages as FlashMessagesType, PagePropsWithFlash } from '@/types'

interface FlashMessageProps {
    type: 'success' | 'error' | 'warning' | 'info'
    message: string
    onDismiss: () => void
    autoDismiss?: boolean
    autoDismissDelay?: number
}

const FlashMessage: React.FC<FlashMessageProps> = ({
    type,
    message,
    onDismiss,
    autoDismiss = true,
    autoDismissDelay = 5000
}) => {
    useEffect(() => {
        if (autoDismiss && autoDismissDelay > 0) {
            const timer = setTimeout(() => {
                onDismiss()
            }, autoDismissDelay)

            return () => clearTimeout(timer)
        }
    }, [autoDismiss, autoDismissDelay, onDismiss])

    const getMessageConfig = () => {
        switch (type) {
            case 'success':
                return {
                    icon: CheckCircleIcon,
                    bgColor: 'bg-green-50 dark:bg-green-900/20',
                    borderColor: 'border-green-200 dark:border-green-800',
                    textColor: 'text-green-800 dark:text-green-200',
                    iconColor: 'text-green-600 dark:text-green-400'
                }
            case 'error':
                return {
                    icon: ExclamationCircleIcon,
                    bgColor: 'bg-red-50 dark:bg-red-900/20',
                    borderColor: 'border-red-200 dark:border-red-800',
                    textColor: 'text-red-800 dark:text-red-200',
                    iconColor: 'text-red-600 dark:text-red-400'
                }
            case 'warning':
                return {
                    icon: ExclamationTriangleIcon,
                    bgColor: 'bg-yellow-50 dark:bg-yellow-900/20',
                    borderColor: 'border-yellow-200 dark:border-yellow-800',
                    textColor: 'text-yellow-800 dark:text-yellow-200',
                    iconColor: 'text-yellow-600 dark:text-yellow-400'
                }
            case 'info':
                return {
                    icon: InformationCircleIcon,
                    bgColor: 'bg-blue-50 dark:bg-blue-900/20',
                    borderColor: 'border-blue-200 dark:border-blue-800',
                    textColor: 'text-blue-800 dark:text-blue-200',
                    iconColor: 'text-blue-600 dark:text-blue-400'
                }
            default:
                return {
                    icon: InformationCircleIcon,
                    bgColor: 'bg-gray-50 dark:bg-gray-900/20',
                    borderColor: 'border-gray-200 dark:border-gray-800',
                    textColor: 'text-gray-800 dark:text-gray-200',
                    iconColor: 'text-gray-600 dark:text-gray-400'
                }
        }
    }

    const config = getMessageConfig()
    const Icon = config.icon

    return (
        <div className={clsx(
            'relative rounded-lg border p-4 shadow-sm transition-all duration-300 ease-in-out',
            config.bgColor,
            config.borderColor
        )}>
            <div className="flex items-start">
                <div className="flex-shrink-0">
                    <Icon
                        data-slot="icon"
                        className={clsx('size-5', config.iconColor)}
                        aria-hidden="true"
                    />
                </div>
                <div className="ml-3 flex-1">
                    <p className={clsx('text-sm font-medium', config.textColor)}>
                        {message}
                    </p>
                </div>
                <div className="ml-auto pl-3">
                    <Button
                        plain
                        onClick={onDismiss}
                        className={clsx(
                            'inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2',
                            config.textColor,
                            'hover:bg-black/5 focus:ring-current dark:hover:bg-white/5'
                        )}
                        aria-label="Dismiss"
                    >
                        <XMarkIcon data-slot="icon" className="size-4" aria-hidden="true" />
                    </Button>
                </div>
            </div>
        </div>
    )
}

interface FlashMessagesContainerProps {
    className?: string
    autoDismiss?: boolean
    autoDismissDelay?: number
}

export const FlashMessages: React.FC<FlashMessagesContainerProps> = ({
    className,
    autoDismiss = true,
    autoDismissDelay = 10000
}) => {
    const { flash } = usePage<PagePropsWithFlash>().props
    const [visibleMessages, setVisibleMessages] = useState<FlashMessagesType>({})

    // Update visible messages when flash messages change
    useEffect(() => {
        if (flash) {
            setVisibleMessages(flash)
        }
    }, [flash])

    const dismissMessage = (type: keyof FlashMessagesType) => {
        setVisibleMessages(prev => {
            const updated = { ...prev }
            delete updated[type]
            return updated
        })
    }

    const hasMessages = Object.keys(visibleMessages).length > 0

    if (!hasMessages) {
        return null
    }

    return (
        <div className={clsx('space-y-3', className)}>
            {visibleMessages.success && (
                <FlashMessage
                    type="success"
                    message={visibleMessages.success}
                    onDismiss={() => dismissMessage('success')}
                    autoDismiss={autoDismiss}
                    autoDismissDelay={autoDismissDelay}
                />
            )}
            {visibleMessages.error && (
                <FlashMessage
                    type="error"
                    message={visibleMessages.error}
                    onDismiss={() => dismissMessage('error')}
                    autoDismiss={autoDismiss}
                    autoDismissDelay={autoDismissDelay}
                />
            )}
            {visibleMessages.warning && (
                <FlashMessage
                    type="warning"
                    message={visibleMessages.warning}
                    onDismiss={() => dismissMessage('warning')}
                    autoDismiss={autoDismiss}
                    autoDismissDelay={autoDismissDelay}
                />
            )}
            {visibleMessages.info && (
                <FlashMessage
                    type="info"
                    message={visibleMessages.info}
                    onDismiss={() => dismissMessage('info')}
                    autoDismiss={autoDismiss}
                    autoDismissDelay={autoDismissDelay}
                />
            )}
        </div>
    )
}

export default FlashMessages
