import { PortalGuide } from '@/types';
import { BookOpenIcon, StarIcon } from '@heroicons/react/24/outline';
import { ArrowTopRightOnSquareIcon } from '@heroicons/react/16/solid';
import React from 'react';

const SUCCESS_STORIES_URL = 'https://oceandecade.org/capacity-development-facility/';

interface PortalGuideSectionProps {
    portalGuide: PortalGuide;
}

interface LinkRowProps {
    href: string;
    title: string;
    description: string;
    icon: React.ComponentType<{ className?: string }>;
}

function LinkRow({ href, title, description, icon: Icon }: Readonly<LinkRowProps>) {
    return (
        <a
            href={href}
            target="_blank"
            rel="noopener noreferrer"
            className="group flex items-center gap-4 rounded-xl border border-gray-200 bg-white px-5 py-4 text-inherit no-underline transition-colors hover:border-firefly-300 hover:bg-firefly-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-firefly-500 focus-visible:ring-offset-2 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-firefly-700 dark:hover:bg-gray-700/60 dark:focus-visible:ring-offset-gray-900"
        >
            <span className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-firefly-100 text-firefly-700 dark:bg-firefly-900/40 dark:text-firefly-300">
                <Icon className="h-5 w-5" aria-hidden="true" data-slot="icon" />
            </span>
            <span className="flex flex-grow flex-col gap-0.5">
                <span className="text-base font-semibold text-gray-900 dark:text-gray-100">{title}</span>
                <span className="text-sm leading-5 text-gray-500 dark:text-gray-400">{description}</span>
            </span>
            <ArrowTopRightOnSquareIcon
                className="h-4 w-4 flex-shrink-0 text-gray-400 transition-colors group-hover:text-firefly-700 dark:text-gray-500 dark:group-hover:text-firefly-300"
                aria-hidden="true"
            />
        </a>
    );
}

export default function PortalGuideSection({ portalGuide }: Readonly<PortalGuideSectionProps>) {
    return (
        <section aria-label="Guides and success stories" className="grid grid-cols-1 gap-4 md:grid-cols-2">
            <LinkRow
                href={portalGuide.url}
                title="New to the platform?"
                description="Read the portal guide to see how it works and how you can get involved."
                icon={BookOpenIcon}
            />
            <LinkRow
                href={SUCCESS_STORIES_URL}
                title="Success stories and lessons learned"
                description="Completed matches, trainings and workshops supported through the CDF."
                icon={StarIcon}
            />
        </section>
    );
}
