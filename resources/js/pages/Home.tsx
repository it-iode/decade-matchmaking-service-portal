import { Head } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';
import { FrontendLayout } from '@layouts/index';
import { Auth, User } from '@/types';
import { Heading } from '@ui/primitives/heading';
import { Divider } from '@ui/primitives/divider';
import {
    AcademicCapIcon,
    BriefcaseIcon,
    DocumentTextIcon,
    ClipboardDocumentListIcon,
    BellAlertIcon,
} from '@heroicons/react/24/outline';

import { CardGuide } from '@features/home';
import WelcomeSection from '@features/home/components/welcome-section';
import ActionSection, { ActionCardData } from '@features/home/components/action-section';
import {Reveal} from "@shared/motion";
import {ODCTravelSupportSection} from "@features/odc-travel-support";
import React from "react";
import {Opportunity, OpportunityFormOptions} from "@features/opportunities";

type HomePageProps = {
    userGuide?: string;
    partnerGuide?: string;
    odcTravelSupport?: Opportunity[];
    formOptions?: OpportunityFormOptions;
    /** Whether the ODC travel support section is enabled in portal settings. Defaults to shown. */
    showOdcTravelSupport?: boolean;
}

export default function Home({ userGuide, partnerGuide, odcTravelSupport, formOptions, showOdcTravelSupport = true}: Readonly<HomePageProps>) {
    const { auth } = usePage<{ auth: Auth }>().props;

    // Helper function to check if user has access (either is_user or is_partner)
    const userHasAccess = (user: User): boolean => user.is_user || user.is_partner;

    // Define request cards with role-based visibility
    const requestCards: ActionCardData[] = [
        {
            title: 'Submit New Request',
            description: 'Submit a new capacity development request for training and workshops.',
            href: route('request.create'),
            icon: AcademicCapIcon,
            variant: 'request',
            disabled: !userHasAccess(auth.user),
            disabledReason: !userHasAccess(auth.user) ? 'Register as a user to submit requests' : undefined,
            visible: true,
        },
        {
            title: 'List of My Requests',
            description: 'Track the progress and current status of your submitted requests.',
            href: route('request.me.list'),
            icon: DocumentTextIcon,
            variant: 'request',
            disabled: !userHasAccess(auth.user),
            disabledReason: !userHasAccess(auth.user) ? 'Register as a user to view your requests' : undefined,
            visible: true,
        },
        {
            title: 'View Request for Training & Workshops',
            description: 'Browse and explore training and workshop requests that align with your interests and expertise.',
            href: route('request.list'),
            icon: ClipboardDocumentListIcon,
            variant: 'request',
            visible: auth.user.is_partner,
        },
        {
            title: 'My Matched Requests',
            description: 'Manage training and workshop requests that you matched with as a Partner.',
            href: route('request.me.matched-requests'),
            icon: DocumentTextIcon,
            variant: 'request',
            visible: auth.user.is_partner,
        },
        {
            title: 'My Subscribed Requests',
            description: 'Manage and keep track of the requests you have subscribed to for updates and notifications.',
            href: route('request.me.subscribed-requests'),
            icon: BellAlertIcon,
            variant: 'request',
            disabled: !userHasAccess(auth.user),
            disabledReason: !userHasAccess(auth.user) ? 'Register as a user to subscribe to requests' : undefined,
            visible: true,
        },
    ];

    // Define opportunity cards with role-based visibility
    const opportunityCards: ActionCardData[] = [
        {
            title: 'View and Apply for Partner Opportunities',
            description: 'Browse and apply for available capacity development opportunities offered by partners.',
            href: route('opportunity.list'),
            icon: BriefcaseIcon,
            variant: 'opportunity',
            visible: true,
        },
        {
            title: 'Submit Opportunity',
            description: 'Submit a new capacity development opportunity as a Partner.',
            href: route('opportunity.create'),
            icon: BriefcaseIcon,
            variant: 'opportunity',
            visible: auth.user.is_partner,
        },
        {
            title: 'View My Submitted Opportunities',
            description: 'View the capacity development opportunities you have submitted as a Partner.',
            href: route('me.opportunity.list'),
            icon: BriefcaseIcon,
            variant: 'opportunity',
            visible: auth.user.is_partner,
        },
    ];

    // Check if any guides are available
    const hasGuides = userGuide || (partnerGuide && auth.user.is_partner);

    return (
        <FrontendLayout>
            <Head title="Welcome" />

            <div className="space-y-10">
                <WelcomeSection user={auth.user} />
                {showOdcTravelSupport && (
                    <Reveal>
                        <ODCTravelSupportSection opportunities={odcTravelSupport} formOptions={formOptions} />
                    </Reveal>
                )}
                {/* Getting Started Guides */}
                {hasGuides && (
                    <section className="space-y-6">
                        <Heading level={2}>Getting Started</Heading>
                        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            {userGuide && (
                                <CardGuide
                                    title="User Guide"
                                    description="A step-by-step guide to help users navigate the Ocean Connector, submit requests, and engage with partner opportunities."
                                    fileUrl={userGuide}
                                />
                            )}
                            {partnerGuide && auth.user.is_partner && (
                                <CardGuide
                                    title="Partner Guide"
                                    description="A step-by-step guide to help partners use the Ocean Connector, review requests, and submit opportunities."
                                    fileUrl={partnerGuide}
                                />
                            )}
                        </div>
                    </section>
                )}

                <Divider className="my-10" />

                {/* Requests Section */}
                <ActionSection
                    title="Capacity Development Requests"
                    description="Submit and manage training and workshop requests"
                    icon={AcademicCapIcon}
                    cards={requestCards}
                    emptyMessage="No request actions available for your role."
                />

                <Divider className="my-10" />

                {/* Opportunities Section */}
                <ActionSection
                    title="Partner Opportunities"
                    description="Explore and manage capacity development opportunities"
                    icon={BriefcaseIcon}
                    cards={opportunityCards}
                    emptyMessage="No opportunity actions available."
                />
            </div>
        </FrontendLayout>
    );
}
