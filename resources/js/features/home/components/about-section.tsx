import { Heading } from '@ui/primitives/heading';
import { Text } from '@ui/primitives/text';
import React from 'react';
import clsx from 'clsx';

interface AboutSectionProps {
    title: string;
    description: string;
    className?: string;
}

export default function AboutSection({
    title,
    description,
    className,
}: AboutSectionProps) {
    return (
        <section className={clsx('py-12 px-4', className)}>
            <div className="max-w-3xl mx-auto text-center">
                <Heading
                    level={2}
                    className="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-gray-100 mb-4"
                >
                    {title}
                </Heading>
                <Text className="text-lg text-gray-600 dark:text-gray-400 leading-relaxed">
                    {description}
                </Text>
            </div>
        </section>
    );
}

export type { AboutSectionProps };
