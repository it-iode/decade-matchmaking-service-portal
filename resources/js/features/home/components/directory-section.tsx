import { BuildingOffice2Icon, GlobeAltIcon } from '@heroicons/react/24/outline';
import { ChevronRightIcon } from '@heroicons/react/16/solid';
import { Stagger, StaggerItem } from '@shared/motion';
import React from 'react';

interface DirectoryCardProps {
    title: string;
    description: string;
    icon: React.ComponentType<{ className?: string }>;
    onClick: () => void;
    id?: string;
}

function DirectoryCard({ title, description, icon: Icon, onClick, id }: Readonly<DirectoryCardProps>) {
    return (
        <button
            type="button"
            id={id}
            onClick={onClick}
            className="group flex h-full w-full flex-col gap-3 rounded-xl border border-gray-200 bg-white p-6 text-left transition-colors hover:border-firefly-300 hover:bg-firefly-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-firefly-500 focus-visible:ring-offset-2 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-firefly-700 dark:hover:bg-gray-700/60 dark:focus-visible:ring-offset-gray-900"
        >
            <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-firefly-900 text-white dark:bg-firefly-700">
                <Icon className="h-5 w-5" aria-hidden="true" data-slot="icon" />
            </span>
            <span className="text-lg font-semibold text-gray-900 dark:text-gray-100">{title}</span>
            <span className="text-sm leading-relaxed text-gray-500 dark:text-gray-400">{description}</span>
            <span className="inline-flex items-center gap-1 text-sm font-medium text-firefly-700 dark:text-firefly-300">
                Open the directory
                <ChevronRightIcon
                    className="h-4 w-4 transition-transform group-hover:translate-x-0.5"
                    aria-hidden="true"
                />
            </span>
        </button>
    );
}

interface DirectorySectionProps {
    onShowOrganizations: () => void;
    onShowIOCPlatforms: () => void;
}

export default function DirectorySection({
    onShowOrganizations,
    onShowIOCPlatforms,
}: Readonly<DirectorySectionProps>) {
    return (
        <section id="features" aria-labelledby="directories-heading" className="space-y-4">
            <div className="space-y-0.5">
                <h2 id="directories-heading" className="text-base font-semibold text-gray-900 dark:text-gray-100">
                    Who supports capacity development through the Ocean Connector
                </h2>
                <p className="text-sm text-gray-500 dark:text-gray-400">Two directories, open to everyone.</p>
            </div>
            <Stagger className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <StaggerItem className="h-full">
                    <DirectoryCard
                        title="CDF partners"
                        description="Organisations supporting capacity development through the Ocean Connector."
                        icon={BuildingOffice2Icon}
                        onClick={onShowOrganizations}
                    />
                </StaggerItem>
                <StaggerItem className="h-full">
                    <DirectoryCard
                        id="iocplatform"
                        title="IOC platforms"
                        description="Directory of IOC platforms supporting capacity development in ocean science."
                        icon={GlobeAltIcon}
                        onClick={onShowIOCPlatforms}
                    />
                </StaggerItem>
            </Stagger>
        </section>
    );
}
