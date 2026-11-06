import { Link } from '@inertiajs/react';
import { Badge } from '@ui/primitives/badge';
import { LockClosedIcon } from '@heroicons/react/20/solid';
import { motion, useReducedMotion } from 'motion/react';
import { springGentle } from '@shared/motion';
import React from 'react';
import clsx from 'clsx';

const MotionLink = motion.create(Link);

type ActionCardVariant = 'request' | 'opportunity' | 'neutral';

// Badge colors available in @ui/primitives/badge
type BadgeColor = 'red' | 'orange' | 'amber' | 'yellow' | 'lime' | 'green' | 'emerald' |
    'teal' | 'cyan' | 'sky' | 'blue' | 'indigo' | 'violet' | 'purple' | 'fuchsia' | 'pink' | 'rose' | 'zinc';

// Base props shared by all variants
interface BaseActionCardProps {
    title: string;
    description: string;
    icon?: React.ComponentType<{ className?: string }>;
    badge?: { text: string; color: BadgeColor };
    variant?: ActionCardVariant;
    disabled?: boolean;
    disabledReason?: string;
}

// Link variant requires href
interface LinkActionCardProps extends BaseActionCardProps {
    href: string;
    onClick?: never;
}

// Button variant requires onClick
interface ButtonActionCardProps extends BaseActionCardProps {
    href?: never;
    onClick: () => void;
}

// Union type ensures either href OR onClick is provided
type ActionCardProps = LinkActionCardProps | ButtonActionCardProps;

const variantStyles: Record<ActionCardVariant, {
    gradient: string;
    hoverGradient: string;
    iconBg: string;
    iconColor: string;
    focusRing: string;
}> = {
    request: {
        gradient: 'from-firefly-500 to-firefly-600 dark:from-firefly-700 dark:to-firefly-800',
        hoverGradient: 'hover:from-firefly-500 hover:to-firefly-700 dark:hover:from-firefly-600 dark:hover:to-firefly-800',
        iconBg: 'bg-white/20',
        iconColor: 'text-white',
        focusRing: 'focus-visible:ring-firefly-500',
    },
    opportunity: {
        gradient: 'from-firefly-700 to-firefly-900 dark:from-bright-firefly-700 dark:to-bright-firefly-900',
        hoverGradient: 'hover:from-bright-firefly-500 hover:to-bright-firefly-700 dark:hover:from-bright-firefly-600 dark:hover:to-bright-turquoise-800',
        iconBg: 'bg-white/20',
        iconColor: 'text-white',
        focusRing: 'focus-visible:ring-bright-firefly-500',
    },
    neutral: {
        gradient: 'from-gray-600 to-gray-800 dark:from-gray-700 dark:to-gray-900',
        hoverGradient: 'hover:from-gray-500 hover:to-gray-700 dark:hover:from-gray-600 dark:hover:to-gray-800',
        iconBg: 'bg-white/20',
        iconColor: 'text-white',
        focusRing: 'focus-visible:ring-gray-500',
    },
};

export default function ActionCard(props: ActionCardProps) {
    const {
        title,
        description,
        icon: Icon,
        badge,
        variant = 'neutral',
        disabled = false,
        disabledReason,
    } = props;

    // Determine if this is a button or link variant
    const isButton = 'onClick' in props && props.onClick !== undefined;
    const styles = variantStyles[variant];
    const reduceMotion = useReducedMotion();

    // Spring-based hover/tap feedback, disabled when the user prefers reduced motion.
    const hoverProps = reduceMotion
        ? {}
        : { whileHover: { scale: 1.02 }, whileTap: { scale: 0.98 }, transition: springGentle };

    const cardContent = (
        <div className="relative h-full p-6">
            {badge && (
                <div className="absolute top-4 right-4">
                    <Badge color={badge.color}>{badge.text}</Badge>
                </div>
            )}

            {Icon && (
                <div className={clsx(
                    'mb-4 flex h-12 w-12 items-center justify-center rounded-lg',
                    styles.iconBg
                )}>
                    <Icon className={clsx('h-6 w-6', styles.iconColor)} aria-hidden="true" data-slot="icon" />
                </div>
            )}

            <h3 className="text-lg font-semibold text-white mb-2">
                {title}
            </h3>
            <p className="text-sm leading-relaxed text-white/90">
                {description}
            </p>

            {disabled && (
                <div className="absolute inset-0 bg-gray-900/60 backdrop-blur-sm rounded-xl flex items-center justify-center">
                    <div className="text-center px-4">
                        <LockClosedIcon className="h-8 w-8 text-white/80 mx-auto mb-2" aria-hidden="true" />
                        {disabledReason && (
                            <p className="text-sm text-white/90">{disabledReason}</p>
                        )}
                    </div>
                </div>
            )}
        </div>
    );

    const interactiveClasses = clsx(
        'group relative h-full overflow-hidden rounded-xl',
        'bg-gradient-to-br',
        styles.gradient,
        'shadow-sm hover:shadow-lg',
        // Ease colours + shadow via CSS; transform is owned by motion (whileHover/whileTap).
        'transition-[color,background-color,box-shadow] duration-200',
        styles.hoverGradient,
        'focus:outline-none focus-visible:ring-2',
        styles.focusRing,
        'focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900'
    );

    if (disabled) {
        return (
            <div
                className={clsx(
                    'relative h-full overflow-hidden rounded-xl',
                    'bg-gradient-to-br',
                    styles.gradient,
                    'shadow-sm',
                    'opacity-75 cursor-not-allowed'
                )}
                aria-disabled="true"
            >
                {cardContent}
            </div>
        );
    }

    // Button variant - for onClick handlers (e.g., opening dialogs)
    if (isButton) {
        return (
            <motion.button
                type="button"
                onClick={props.onClick}
                className={clsx(interactiveClasses, 'w-full text-left')}
                {...hoverProps}
            >
                {cardContent}
            </motion.button>
        );
    }

    // Link variant - for navigation
    return (
        <MotionLink
            href={props.href}
            className={clsx(interactiveClasses, 'block')}
            {...hoverProps}
        >
            {cardContent}
        </MotionLink>
    );
}

export type { ActionCardProps, ActionCardVariant };
