import {
    Sidebar,
    SidebarBody,
    SidebarFooter,
    SidebarHeader,
    SidebarItem,
    SidebarLabel,
    SidebarSection,
} from '@ui/primitives/sidebar'

import {
    Cog6ToothIcon,
    HomeIcon,
    DocumentDuplicateIcon,
    RocketLaunchIcon,
    BellAlertIcon,
    ArrowLeftStartOnRectangleIcon,
    PresentationChartBarIcon,
    UserIcon,
    TagIcon,
    EnvelopeIcon,
    BeakerIcon
} from '@heroicons/react/16/solid'
import { usePage } from '@inertiajs/react'
import type { SharedData } from '@/types'


export function SidebarContent() {
    const { devTools } = usePage<SharedData>().props
    const showSampleData = devTools?.sampleData === true

    return (
        <Sidebar>
            <SidebarHeader>
                <SidebarItem href={route('user.home')}>
                    <HomeIcon/>
                    <SidebarLabel>home</SidebarLabel>
                </SidebarItem>
            </SidebarHeader>
            <SidebarBody>
                <SidebarSection>
                    <SidebarItem href={route('admin.dashboard.index')}>
                        <PresentationChartBarIcon/>
                        <SidebarLabel>Dashboard</SidebarLabel>
                    </SidebarItem>
                    <SidebarItem href={route('admin.notifications.index')}>
                        <BellAlertIcon/>
                        <SidebarLabel>Notifications</SidebarLabel>
                    </SidebarItem>
                    <SidebarItem href={route('admin.request.list')}>
                        <DocumentDuplicateIcon/>
                        <SidebarLabel>Requests</SidebarLabel>
                    </SidebarItem>
                    <SidebarItem href={route('admin.offer.list')}>
                        <TagIcon/>
                        <SidebarLabel>Offers</SidebarLabel>
                    </SidebarItem>
                    <SidebarItem href={route('admin.opportunity.list')}>
                        <RocketLaunchIcon/>
                        <SidebarLabel>Opportunities</SidebarLabel>
                    </SidebarItem>
                    <SidebarItem href={route('admin.users.index')}>
                        <UserIcon/>
                        <SidebarLabel>Users</SidebarLabel>
                    </SidebarItem>
                    <SidebarItem href={route('admin.invitations.index')}>
                        <EnvelopeIcon/>
                        <SidebarLabel>Invitations</SidebarLabel>
                    </SidebarItem>
                    <SidebarItem href={route('admin.settings.index')}>
                        <Cog6ToothIcon/>
                        <SidebarLabel>Settings</SidebarLabel>
                    </SidebarItem>
                    <SidebarItem href={route('admin.subscriptions.index')}>
                        <Cog6ToothIcon/>
                        <SidebarLabel>Request subscriptions</SidebarLabel>
                    </SidebarItem>
                    {showSampleData && (
                        <SidebarItem href={route('admin.sample-data.index')}>
                            <BeakerIcon/>
                            <SidebarLabel>Sample data</SidebarLabel>
                        </SidebarItem>
                    )}
                </SidebarSection>
            </SidebarBody>
            <SidebarFooter>
                <SidebarItem href={route('sign.out')} method="post">
                    <ArrowLeftStartOnRectangleIcon/>
                    <SidebarLabel>Sign out</SidebarLabel>
                </SidebarItem>
            </SidebarFooter>
        </Sidebar>
    )
}
