import {Head} from '@inertiajs/react';
import { FrontendLayout } from '@layouts/index';
import PortalGuideSection from "@features/home/components/portal-guide-section";
import DirectorySection from "@features/home/components/directory-section";
import { ODCTravelSupportSection } from "@features/odc-travel-support";
import {PortalGuide} from '@/types';
import { Opportunity, OpportunityFormOptions } from '@features/opportunities/types';
import { OrganizationsDialog, IOCPlatformsDialog } from '@features/settings';
import { Reveal } from '@shared/motion';
import React, {useState} from "react";


interface IndexPageProps {
    portalGuide?: PortalGuide | null;
    /** Active ODC travel support opportunities, sorted by closing date. Provided by the backend to everyone, including guests (issue #218). */
    odcTravelSupport?: Opportunity[];
    /** Select options for the ODC travel support upload form. Provided by the backend (issue #218). */
    formOptions?: OpportunityFormOptions;
    /** Whether the ODC travel support section is enabled in portal settings. Defaults to shown. */
    showOdcTravelSupport?: boolean;
}

export default function Index({portalGuide, odcTravelSupport, formOptions, showOdcTravelSupport = true}: Readonly<IndexPageProps>) {
    const [showOrganizationsDialog, setShowOrganizationsDialog] = useState(false);
    const [showIOCPlatformsDialog, setShowIOCPlatformsDialog] = useState(false);

    return (
        <FrontendLayout>
            <Head title="Welcome"/>
            <div className="space-y-9">
                {showOdcTravelSupport && (
                    <Reveal>
                        <ODCTravelSupportSection opportunities={odcTravelSupport} formOptions={formOptions} />
                    </Reveal>
                )}

                {portalGuide && (
                    <Reveal>
                        <PortalGuideSection portalGuide={portalGuide}/>
                    </Reveal>
                )}

                <Reveal>
                    <DirectorySection
                        onShowOrganizations={() => setShowOrganizationsDialog(true)}
                        onShowIOCPlatforms={() => setShowIOCPlatformsDialog(true)}
                    />
                </Reveal>
            </div>

            <OrganizationsDialog
                open={showOrganizationsDialog}
                onClose={() => setShowOrganizationsDialog(false)}
            />

            <IOCPlatformsDialog
                open={showIOCPlatformsDialog}
                onClose={() => setShowIOCPlatformsDialog(false)}
            />
        </FrontendLayout>
    );
}
