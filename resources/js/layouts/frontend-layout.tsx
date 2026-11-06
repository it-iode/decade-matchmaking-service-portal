import React from 'react';
import { Header, Footer } from '@layouts/components';
import BannerSection from '@layouts/frontend-layout/banner-section';
import { usePage } from '@inertiajs/react';
import { PageTransition } from '@shared/motion';
import { FlashMessages, ActionsBar } from '@ui/organisms';
import { ActionButton } from '@/types';
import type { Banner } from '@layouts/frontend-layout/banner-section';

interface FrontendLayoutProps {
    children: React.ReactNode;
}

const FrontendLayout: React.FC<FrontendLayoutProps> = ({ children }) => {
    const page = usePage();
    const actions = page.props?.actions as ActionButton[] | undefined;
    const banner = page.props.banner as Banner | undefined;

    // Determine if banner will be shown (has image)
    const hasBanner = Boolean(banner?.image);

    return (
        <div className="min-h-screen flex flex-col text-gray-900 dark:text-gray-100 dark:bg-gray-900">
            <Header isOverlay={hasBanner} />
            <BannerSection />
            <main
                id="main-content"
                className={[
                    'flex-grow container mx-auto py-4 ',
                    !hasBanner && 'pt-20',
                ].filter(Boolean).join(' ')}
            >
                <div>
                    <FlashMessages />
                </div>
                <div className="pt-4">
                    {actions && <ActionsBar actions={actions} />}
                </div>
                <PageTransition
                    key={page.component}
                    className="shadow rounded bg-white dark:bg-gray-800 p-4"
                >
                    {children}
                </PageTransition>
            </main>
            <Footer />
        </div>
    );
};

export default FrontendLayout;