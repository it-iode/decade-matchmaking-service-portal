import { Heading } from '@ui/primitives/heading';
import { Text } from '@ui/primitives/text';
import React from 'react';
import clsx from 'clsx';
import { Stagger, StaggerItem } from '@shared/motion';
import ActionCard, { ActionCardVariant } from './action-card';

// Base data shared by all variants
interface BaseActionCardData {
    title: string;
    description: string;
    icon?: React.ComponentType<{ className?: string }>;
    variant?: ActionCardVariant;
    disabled?: boolean;
    disabledReason?: string;
    visible?: boolean;
}

// Link variant requires href
interface LinkActionCardData extends BaseActionCardData {
    href: string;
    onClick?: never;
}

// Button variant requires onClick
interface ButtonActionCardData extends BaseActionCardData {
    href?: never;
    onClick: () => void;
}

// Union type ensures either href OR onClick is provided
type ActionCardData = LinkActionCardData | ButtonActionCardData;

interface ActionSectionProps {
    title: string;
    description?: string;
    icon?: React.ComponentType<{ className?: string }>;
    cards: ActionCardData[];
    emptyMessage?: string;
    className?: string;
}

export default function ActionSection({
    title,
    description,
    icon: Icon,
    cards,
    emptyMessage = 'No actions available in this section.',
    className,
}: ActionSectionProps) {
    const visibleCards = cards.filter(card => card.visible !== false);

    return (
        <section className={clsx('space-y-6', className)}>
            <div className="flex items-start gap-4">
                {Icon && (
                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-firefly-100 dark:bg-firefly-900/20 mt-1 flex-shrink-0">
                        <Icon className="h-6 w-6 text-firefly-600 dark:text-firefly-400" aria-hidden="true" data-slot="icon" />
                    </div>
                )}
                <div className="flex-1">
                    <Heading level={2} className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {title}
                    </Heading>
                    {description && (
                        <Text className="mt-2 text-base text-gray-600 dark:text-gray-400">
                            {description}
                        </Text>
                    )}
                </div>
            </div>

            {visibleCards.length > 0 ? (
                <Stagger className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:gap-8">
                    {visibleCards.map((card) => (
                        <StaggerItem key={card.title} className="h-full">
                            <ActionCard {...card} />
                        </StaggerItem>
                    ))}
                </Stagger>
            ) : (
                <div className="text-center py-12 px-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700">
                    <Text className="text-gray-600 dark:text-gray-400">
                        {emptyMessage}
                    </Text>
                </div>
            )}
        </section>
    );
}

export type { ActionSectionProps, ActionCardData };
