import React from 'react';
import { DocumentTextIcon, ArrowTopRightOnSquareIcon } from '@heroicons/react/24/outline';
import clsx from 'clsx';

interface CardGuideProps {
    title: string;
    description: string;
    fileUrl: string;
    className?: string;
}

const CardGuide: React.FC<CardGuideProps> = ({ title, description, fileUrl, className }) => (
    <div
        className={clsx(
            'group flex rounded-xl border border-gray-200 dark:border-gray-700',
            'bg-white dark:bg-gray-800',
            'p-6 sm:p-8 sm:flex-row flex-col',
            'shadow-sm hover:shadow-md transition-shadow duration-200',
            className
        )}
    >
        <div className="w-14 h-14 sm:mr-6 sm:mb-0 mb-4 inline-flex items-center justify-center rounded-lg bg-firefly-100 dark:bg-firefly-900/20 text-firefly-600 dark:text-firefly-400 flex-shrink-0">
            <DocumentTextIcon className="h-7 w-7" aria-hidden="true" />
        </div>
        <div className="flex-grow">
            <h3 className="text-gray-900 dark:text-gray-100 text-lg font-semibold mb-2">
                {title}
            </h3>
            <p className="leading-relaxed text-base text-gray-600 dark:text-gray-400 mb-4">
                {description}
            </p>
            <a
                href={fileUrl}
                target="_blank"
                rel="noopener noreferrer"
                className={clsx(
                    'inline-flex items-center gap-2',
                    'text-firefly-600 dark:text-firefly-400',
                    'hover:text-firefly-700 dark:hover:text-firefly-300',
                    'font-medium transition-colors duration-150',
                    'focus:outline-none focus-visible:ring-2 focus-visible:ring-firefly-500 focus-visible:ring-offset-2',
                    'dark:focus-visible:ring-offset-gray-800',
                    'rounded'
                )}
            >
                View Guide
                <ArrowTopRightOnSquareIcon className="h-4 w-4" aria-hidden="true" />
            </a>
        </div>
    </div>
);

export default CardGuide;
