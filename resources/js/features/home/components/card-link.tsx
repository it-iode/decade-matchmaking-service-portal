import { Link } from '@inertiajs/react';
import React from 'react';

interface CardLinkProps {
    title: string;
    text: string;
    link: string;
    className: string;
    disabled?: boolean;
}

const CardLink: React.FC<CardLinkProps> = ({ title, text, link, className, disabled = false }) => {
    const cardContent = (
        <div>
            <div className="px-6 py-4">
                <div className="font-bold text-xl mb-2">{title}</div>
                <p className="text-base">{text}</p>
            </div>
        </div>
    );

    if (disabled) {
        return (
            <div className={className} role="button" tabIndex={-1} aria-disabled="true">
                {cardContent}
            </div>
        );
    }

    return (
        <Link href={link} className={className}>
            {cardContent}
        </Link>
    );
};

export default CardLink; 