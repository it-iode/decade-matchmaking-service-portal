import { Heading } from '@ui/primitives/heading';
import { Text } from '@ui/primitives/text';
import { Button } from '@ui/primitives/button';
import { Link } from '@inertiajs/react';
import React from 'react';
import clsx from 'clsx';

// CTA action types - either a link or a button
type CTALink = { text: string; href: string; onClick?: never };
type CTAButton = { text: string; onClick: () => void; href?: never };
type CTAAction = CTALink | CTAButton;

interface HeroSectionProps {
    title: string;
    subtitle: string;
    primaryCTA?: CTAAction;
    secondaryCTA?: CTAAction;
    className?: string;
}

function CTAButtonComponent({ cta, variant }: { cta: CTAAction; variant: 'primary' | 'secondary' }) {
    const isPrimary = variant === 'primary';

    // Common button styles
    const baseClasses = clsx(
        'inline-flex items-center justify-center px-6 py-3 rounded-lg font-semibold',
        'transition-all duration-200',
        'focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
        isPrimary
            ? 'bg-white text-firefly-700 hover:bg-gray-100 focus-visible:ring-white'
            : 'bg-transparent text-white border-2 border-white/80 hover:bg-white/10 focus-visible:ring-white'
    );

    // Link variant
    if ('href' in cta && cta.href) {
        return (
            <Link href={cta.href} className={baseClasses}>
                {cta.text}
            </Link>
        );
    }

    // Button variant
    return (
        <button type="button" onClick={cta.onClick} className={baseClasses}>
            {cta.text}
        </button>
    );
}

export default function HeroSection({
    title,
    subtitle,
    primaryCTA,
    secondaryCTA,
    className,
}: HeroSectionProps) {
    return (
        <section
            className={clsx(
                // Use same gradient pattern as WelcomeSection for consistency
                'bg-gradient-to-r from-firefly-600 to-bright-turquoise-600',
                'dark:from-firefly-700 dark:to-bright-turquoise-700',
                'rounded-xl p-8 md:p-12 lg:p-16 text-white shadow-lg',
                className
            )}
        >
            <div className="max-w-3xl mx-auto text-center">
                <Heading
                    level={1}
                    className="text-3xl sm:text-4xl lg:text-5xl font-bold text-white mb-4"
                >
                    {title}
                </Heading>

                <Text className="text-lg sm:text-xl text-white/90 mb-8 max-w-2xl mx-auto">
                    {subtitle}
                </Text>

                {(primaryCTA || secondaryCTA) && (
                    <div className="flex flex-col sm:flex-row gap-4 justify-center">
                        {primaryCTA && (
                            <CTAButtonComponent cta={primaryCTA} variant="primary" />
                        )}
                        {secondaryCTA && (
                            <CTAButtonComponent cta={secondaryCTA} variant="secondary" />
                        )}
                    </div>
                )}
            </div>
        </section>
    );
}

export type { HeroSectionProps, CTAAction };
