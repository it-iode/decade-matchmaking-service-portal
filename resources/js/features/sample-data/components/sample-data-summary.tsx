import React from 'react';
import { Users, Briefcase, FileText, Handshake, Bell, BellRing, Bookmark, ShieldCheck } from 'lucide-react';
import { KPICard } from '@features/dashboard';
import { Subheading } from '@ui/primitives/heading';
import { Text, Code, Strong } from '@ui/primitives/text';
import { EntityCountsTable } from './entity-counts-table';
import type { SampleDataSummary } from '../types';

interface SampleDataSummaryProps {
    summary: SampleDataSummary;
}

/**
 * Result of the last run (kept in the session flash, so a page refresh does
 * not re-run anything).
 */
export function SampleDataSummaryPanel({ summary }: Readonly<SampleDataSummaryProps>) {
    const { created, credentials } = summary;
    const totalUsers = created.admins + created.partners + created.users;
    const exampleLogin = `${credentials.logins[0]?.split('..')[0] ?? 'admin1'}@${credentials.domain}`;

    return (
        <section aria-labelledby="sample-data-summary-heading" className="space-y-6">
            <div>
                <Subheading id="sample-data-summary-heading" level={2}>
                    Last run
                </Subheading>
                <Text>
                    Completed in <Strong>{summary.duration_seconds}s</Strong> on <Strong>{summary.environment}</Strong>.
                </Text>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <KPICard
                    title="Users"
                    value={totalUsers}
                    icon={Users}
                    color="blue"
                    description={`${created.admins} admins · ${created.partners} partners · ${created.users} users`}
                />
                <KPICard
                    title="Opportunities"
                    value={created.opportunities}
                    icon={Briefcase}
                    color="green"
                    description="All types, mixed statuses"
                />
                <KPICard
                    title="Requests"
                    value={created.requests}
                    icon={FileText}
                    color="purple"
                    description="All statuses, mixed activities"
                />
                <KPICard
                    title="Offers"
                    value={created.offers}
                    icon={Handshake}
                    color="orange"
                    description="Pending and active"
                />
                <KPICard title="Subscriptions" value={created.subscriptions} icon={Bookmark} color="blue" />
                <KPICard
                    title="Notification settings"
                    value={created.notification_settings}
                    icon={BellRing}
                    color="green"
                    description="Opt-out rows; other users keep defaults"
                />
                <KPICard title="In-app notifications" value={created.notifications} icon={Bell} color="purple" />
                <KPICard
                    title="Protected accounts"
                    value={summary.protected.users}
                    icon={ShieldCheck}
                    color="red"
                    description="Untouched, with their related rows"
                />
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div className="space-y-3">
                    <Subheading level={3}>Kept vs erased</Subheading>
                    <EntityCountsTable kept={summary.protected} erased={summary.deleted} />
                </div>

                <div className="space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <Subheading level={3}>Test logins</Subheading>
                    <Text>
                        Every generated account uses the password <Code>{credentials.password}</Code> and an address
                        on <Code>@{credentials.domain}</Code>.
                    </Text>
                    <ul className="list-disc space-y-1 pl-5 text-sm text-zinc-700 dark:text-zinc-300">
                        {credentials.logins.map((pattern) => (
                            <li key={pattern}>
                                <Code>
                                    {pattern}@{credentials.domain}
                                </Code>
                            </li>
                        ))}
                    </ul>
                    <Text>
                        Example: <Code>{exampleLogin}</Code>
                    </Text>
                </div>
            </div>
        </section>
    );
}
