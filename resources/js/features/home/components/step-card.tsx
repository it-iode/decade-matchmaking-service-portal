import { Text } from '@ui/primitives/text';
import React from 'react';
import clsx from 'clsx';

interface StepCardProps {
    stepNumber: number;
    title: string;
    description: string;
    icon?: React.ComponentType<{ className?: string }>;
    className?: string;
}

export default function StepCard({
    stepNumber,
    title,
    description,
    icon: Icon,
    className,
}: StepCardProps) {
    return (
        <div
            className={clsx(
                'relative flex flex-col items-center text-center p-6',
                'bg-white dark:bg-gray-800 rounded-xl shadow-sm',
                'border border-gray-200 dark:border-gray-700',
                className
            )}
        >
            {/* Step number badge */}
            <div
                className={clsx(
                    'absolute -top-4 left-1/2 -translate-x-1/2',
                    'w-8 h-8 rounded-full',
                    'bg-firefly-600 dark:bg-firefly-500',
                    'flex items-center justify-center',
                    'text-white font-bold text-sm'
                )}
            >
                {stepNumber}
            </div>

            {/* Icon */}
            {Icon && (
                <div
                    className={clsx(
                        'mt-4 mb-4 w-16 h-16 rounded-full',
                        'bg-firefly-100 dark:bg-firefly-900/30',
                        'flex items-center justify-center'
                    )}
                >
                    <Icon
                        className="h-8 w-8 text-firefly-600 dark:text-firefly-400"
                        aria-hidden="true"
                        data-slot="icon"
                    />
                </div>
            )}

            {/* Title */}
            <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                {title}
            </h3>

            {/* Description */}
            <Text className="text-sm text-gray-600 dark:text-gray-400">
                {description}
            </Text>
        </div>
    );
}

export type { StepCardProps };
