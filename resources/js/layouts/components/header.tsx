import { usePage } from '@inertiajs/react';
import { ApplicationLogo } from '@shared/components';
import { SignInDialog } from '@features/auth';
import type { Auth } from '@/types';
import { NavigationMenu } from '@layouts/components';
import { useScrollPosition } from '@/hooks';

/** Scroll distance in pixels before header transitions to compact mode */
const HEADER_SCROLL_THRESHOLD = 50;

interface HeaderProps {
    isOverlay?: boolean;
}

export default function Header({ isOverlay = false }: HeaderProps) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const isScrolled = useScrollPosition({
        threshold: HEADER_SCROLL_THRESHOLD,
        enabled: isOverlay,
    });

    // Determine if we should show scrolled state
    // For non-overlay mode, always show scrolled (solid) state
    const showScrolledState = !isOverlay || isScrolled;

    const headerClasses = [
        isOverlay ? 'fixed' : 'relative',
        'top-0 left-0 right-0 z-20',
        'transition-all duration-300 ease-in-out',
        showScrolledState
            ? 'bg-firefly-900 dark:bg-firefly-950 shadow-lg py-1 sm:py-2'
            : 'py-2 sm:py-4',
        'text-white px-2 sm:px-4',
    ].join(' ');

    return (
        <>
            {/* Skip to content link for accessibility */}
            <a
                href="#main-content"
                className="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-white text-firefly-900 px-4 py-2 rounded-md focus:z-[51] font-medium"
            >
                Skip to main content
            </a>

            <header role="banner" className={headerClasses}>
                <div className="container mx-auto flex items-center justify-between">
                    <ApplicationLogo size={showScrolledState ? 'small' : 'large'} />
                    <nav
                        role="navigation"
                        aria-label="Main navigation"
                        className="relative"
                    >
                        {!auth.user ? <SignInDialog /> : <NavigationMenu />}
                    </nav>
                </div>
            </header>
        </>
    );
}