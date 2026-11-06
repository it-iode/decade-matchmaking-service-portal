import {FrontendLayout} from '@layouts/index';
import React from 'react';
import {Head} from '@inertiajs/react';
import {Heading} from '@ui/primitives/heading';
import {PageProps} from '@/types';
import {OCDRequest, RequestShowActionButtons} from '@features/requests';
import {
    StatusBanner,
    StatusInfoCard,
    RequestDetailsCard,
    OfferDetailsCard,
} from '@features/requests/components/show';

interface ShowRequestProps
    extends PageProps<{
        request: OCDRequest;
    }> {
}

export default function ShowRequest({auth, request}: Readonly<ShowRequestProps>) {
    return (
        <FrontendLayout>
            <Head title={`Request: ${request.detail.capacity_development_title || request.id}`}/>

            {/* Page Title */}
            <Heading
                className="text-2xl/7 font-bold text-gray-900 dark:text-gray-100 sm:truncate sm:text-3xl sm:tracking-tight mb-6">
                Request Details
            </Heading>

            {/* Status Banner */}
            <div className="mb-6">
                <StatusBanner auth={auth} request={request}/>
            </div>

            {/* Mobile-only action buttons at top */}
            <div className="mb-6 lg:hidden">
                <RequestShowActionButtons request={request} auth={auth}/>
            </div>

            {/* Two-Column Layout */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Main Content - Left Column (2/3 width on large screens) */}
                <div className="lg:col-span-2 space-y-6">
                    <RequestDetailsCard request={request}/>
                    {/* Display offer details if there's an active offer */}
                    {request.active_offer && (
                        <OfferDetailsCard offer={request.active_offer}/>
                    )}
                </div>

                {/* Sidebar - Right Column (1/3 width on large screens) */}
                <div className="space-y-6">
                    <StatusInfoCard auth={auth} request={request}/>
                    {/* Desktop-only sticky action buttons */}
                    <div className="hidden lg:block">
                        <div
                            className="sticky top-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6"
                            role="region"
                            aria-labelledby="actions-heading"
                        >
                            <h3
                                id="actions-heading"
                                className="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4"
                            >
                                Available Actions
                            </h3>
                            <RequestShowActionButtons
                                request={request}
                                auth={auth}
                                layout="vertical"
                                className="mt-0"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </FrontendLayout>
    );
}
