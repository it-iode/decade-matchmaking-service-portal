import React from 'react';
import {OCDRequestStatus} from '@/types';
import {SidebarLayout} from '@layouts/index'
import {Head} from "@inertiajs/react";
import {PageHeader} from "@ui/molecules/page-header";
import {UpdateStatusDialog} from "@ui/organisms/dialogs";

import {OCDRequest, PageProps} from '@/types';
import {RequestShowActionButtons} from '@features/requests';
import {
    StatusBanner,
    StatusInfoCard,
    RequestDetailsCard,
    OfferDetailsCard,
} from '@features/requests/components/show';


interface RequestShowPageProps
    extends PageProps<{
        request: OCDRequest;
        current_route_name: string;
        availableStatuses?: OCDRequestStatus[];
    }> {
}

export default function RequestShowPage({
                                            auth,
                                            request,
                                            availableStatuses = [],
                                            current_route_name
                                        }: Readonly<RequestShowPageProps>) {
    const [isStatusDialogOpen, setIsStatusDialogOpen] = React.useState(false);
    const [selectedRequest, setSelectedRequest] = React.useState<OCDRequest | null>(null);

    const handleDialogOpen = (dialogComponent: string, action: any) => {
        if (dialogComponent === 'UpdateStatusDialog') {
            setSelectedRequest(request);
            setIsStatusDialogOpen(true);
        }
    };

    const closeStatusDialog = () => {
        setIsStatusDialogOpen(false);
        setSelectedRequest(null);
    };
    return (
        <SidebarLayout>
            <Head title={`Request: ${request.detail.capacity_development_title || request.id}`} />

            <PageHeader title="Request Details" />
            <div className="mb-6">
                <StatusBanner auth={auth} request={request} />
            </div>
            {/* Two-Column Layout */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Main Content - Left Column (2/3 width on large screens) */}
                <div className="lg:col-span-2 space-y-6">
                    <RequestDetailsCard request={request} />

                    {/* Display offer details if there's an active offer */}
                    {request.active_offer && (
                        <OfferDetailsCard offer={request.active_offer} />
                    )}
                </div>
                <div className="space-y-6">
                    <StatusInfoCard  auth={auth} request={request} />

                    {/* Action Buttons in Sidebar */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Actions
                        </h3>
                        <RequestShowActionButtons
                            request={request}
                            auth={auth}
                            layout="vertical"
                            onDialogOpen={handleDialogOpen}
                        />
                    </div>
                </div>
            </div>
            {/* Status Banner */}

            <UpdateStatusDialog
                isOpen={isStatusDialogOpen}
                onClose={closeStatusDialog}
                request={selectedRequest}
                availableStatuses={availableStatuses}
            />
        </SidebarLayout>
    )
}
