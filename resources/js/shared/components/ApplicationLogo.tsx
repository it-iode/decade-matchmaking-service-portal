import { Link } from '@inertiajs/react';

type LogoSize = 'small' | 'medium' | 'large';

interface ApplicationLogoProps {
    size?: LogoSize;
    className?: string;
}

const sizeClasses: Record<LogoSize, string> = {
    small: 'h-8 sm:h-10 w-auto',
    medium: 'h-12 sm:h-16 w-auto',
    large: 'h-16 sm:h-20 md:h-28 w-auto',
};

export default function ApplicationLogo({
    size = 'large',
    className = '',
}: ApplicationLogoProps) {
    return (
        <Link
            href={route('index')}
            className="inline-flex items-center focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-firefly-900 rounded-md"
        >
            <img
                src="/assets/img/logo.png"
                alt="Ocean Connector Logo"
                className={[
                    'transition-all duration-300 ease-in-out',
                    'object-contain',
                    sizeClasses[size],
                    className,
                ].filter(Boolean).join(' ')}
            />
        </Link>
    );
}