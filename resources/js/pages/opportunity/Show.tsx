import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import {
    ArrowTopRightOnSquareIcon,
    CalendarDaysIcon,
    ClockIcon,
    PencilSquareIcon,
} from '@heroicons/react/16/solid';
import { FrontendLayout } from '@layouts/index';
import { PageProps } from '@/types';
import {
    ExtendOpportunityDialog,
    Opportunity,
    OpportunityActionService,
    OpportunityStatus,
} from '@features/opportunities';
import { Badge } from '@ui/primitives/badge';
import { Button } from '@ui/primitives/button';
import { useConfirmation } from '@ui/organisms/confirmation';
import { formatDate, formatDateOnly, opportunityStatusBadgeRenderer } from '@shared/utils';

interface ShowPageProps extends PageProps {
    opportunity: Opportunity;
}

type LabelledOption = { value: string; label: string };

const MS_PER_DAY = 86_400_000;

const LONG_DATE: Intl.DateTimeFormatOptions = { year: 'numeric', month: 'long', day: 'numeric' };

/**
 * Whole days from today until the given date-only string (YYYY-MM-DD).
 * Both ends are taken as UTC midnights so DST changes cannot skew the count.
 * Negative when the date is in the past.
 */
function daysUntil(dateString: string): number {
    const [year, month, day] = dateString.slice(0, 10).split('-').map(Number);
    const target = Date.UTC(year, month - 1, day);
    const now = new Date();
    const today = Date.UTC(now.getFullYear(), now.getMonth(), now.getDate());
    return Math.round((target - today) / MS_PER_DAY);
}

function describeDaysLeft(days: number, statusValue: string): string {
    if (statusValue === OpportunityStatus.CLOSED || statusValue === OpportunityStatus.REJECTED) {
        return 'No longer accepting applications';
    }
    if (days < 0) return 'Deadline passed';
    if (days === 0) return 'Closes today';
    if (days === 1) return '1 day left';
    return `${days} days left`;
}

/**
 * Merge a labelled list with its free-text "other" companion into one de-duplicated list of strings.
 */
function toLabels(items: LabelledOption[] | undefined, other?: string | null): string[] {
    const labels = items?.map(item => item.label) ?? [];
    return Array.from(new Set(other ? [...labels, other] : labels));
}

function ChipList({ items }: Readonly<{ items: string[] }>) {
    return (
        <div className="flex flex-wrap gap-2">
            {items.map(item => (
                <Badge key={item} color="zinc">
                    {item}
                </Badge>
            ))}
        </div>
    );
}

/**
 * A titled block of the page. Renders nothing when it has no content, so fields
 * not collected for some opportunity types simply disappear instead of showing "N/A".
 */
function Section({ title, children }: Readonly<{ title: string; children: React.ReactNode }>) {
    if (!children) return null;

    return (
        <section className="flex flex-col gap-2">
            <h2 className="text-sm/6 font-semibold text-gray-900 dark:text-white">{title}</h2>
            {children}
        </section>
    );
}

function DetailRow({ label, children }: Readonly<{ label: string; children: React.ReactNode }>) {
    return (
        <div className="flex justify-between gap-4 border-t border-gray-100 py-2.5 text-sm/5 dark:border-gray-700">
            <dt className="shrink-0 text-gray-500 dark:text-gray-400">{label}</dt>
            <dd className="min-w-0 text-right font-medium text-gray-900 dark:text-white">{children}</dd>
        </div>
    );
}

/**
 * Link that leaves the app in a new tab. A plain anchor on purpose: the Button/Link
 * primitives route clicks through Inertia, which cannot follow an external redirect.
 */
function ExternalLink({
    href,
    className,
    children,
}: Readonly<{ href: string; className?: string; children: React.ReactNode }>) {
    return (
        <a href={href} target="_blank" rel="noopener noreferrer" className={className}>
            {children}
            <span className="sr-only"> (opens in a new tab)</span>
        </a>
    );
}

export default function Show({ opportunity }: Readonly<ShowPageProps>) {
    const [isExtendDialogOpen, setIsExtendDialogOpen] = useState(false);
    const { confirm } = useConfirmation();

    const permissions = opportunity.permissions;
    const daysLeft = daysUntil(opportunity.closing_date);
    const isClosingSoon = daysLeft >= 0 && daysLeft <= 14;

    const targetAudience = toLabels(opportunity.target_audience, opportunity.target_audience_other);
    const thematicAreas = toLabels(opportunity.thematic_areas, opportunity.thematic_areas_other);
    const languages = toLabels(opportunity.target_languages, opportunity.target_languages_other);
    const locations = toLabels(opportunity.implementation_location);
    const organizers = opportunity.co_organizers ?? [];
    const keywords = opportunity.key_words ?? [];

    const canManage = Boolean(permissions?.can_edit || permissions?.can_extend || permissions?.can_close);

    const handleClose = async () => {
        await confirm({
            title: 'Close this opportunity?',
            message: 'It will no longer accept applications and will be listed as closed.',
            type: 'warning',
            confirmText: 'Close opportunity',
            confirmButtonColor: 'orange',
            onConfirm: () => {
                OpportunityActionService.updateStatus(opportunity, OpportunityStatus.CLOSED);
            },
        });
    };

    return (
        <FrontendLayout>
            <Head title={`Opportunity: ${opportunity.title}`} />

            <div className="flex flex-col gap-6">
                {/* Page header */}
                <header className="flex flex-col gap-3 border-b border-gray-200 pb-6 dark:border-gray-700">
                    <div className="flex flex-wrap items-center gap-2" role="group" aria-label="Opportunity type and status">
                        <Badge color="zinc">{opportunity.type.label}</Badge>
                        {opportunityStatusBadgeRenderer(opportunity)}
                    </div>
                    <h1 className="max-w-4xl text-2xl/8 font-semibold text-gray-900 sm:text-3xl/9 dark:text-white">
                        {opportunity.title}
                    </h1>
                    <p className="flex items-center gap-1.5 text-sm/5 text-gray-500 dark:text-gray-400">
                        <CalendarDaysIcon className="size-4 shrink-0" aria-hidden="true" />
                        Published {formatDate(opportunity.created_at, 'en-US', LONG_DATE)}
                    </p>
                </header>

                <div className="grid grid-cols-1 items-start gap-10 lg:grid-cols-[minmax(0,1fr)_360px]">
                    {/* Main column */}
                    <div className="flex min-w-0 flex-col gap-8">
                        <Section title="Summary">
                            {opportunity.summary && (
                                <p className="whitespace-pre-line text-base/6.5 text-gray-700 dark:text-gray-300">
                                    {opportunity.summary}
                                </p>
                            )}
                        </Section>

                        <Section title={opportunity.type.value == 'odc-travel-support' ? "Sponsoring Organization" : "Organizers"}>
                            {organizers.length > 0 && <ChipList items={organizers} />}
                        </Section>

                        <Section title="Target Audience">
                            {targetAudience.length > 0 && <ChipList items={targetAudience} />}
                        </Section>

                        <Section title="Thematic areas">
                            {thematicAreas.length > 0 && <ChipList items={thematicAreas} />}
                        </Section>

                        <div className="grid grid-cols-1 gap-8 sm:grid-cols-2">
                            <Section title="Language of participation">
                                {languages.length > 0 && <ChipList items={languages} />}
                            </Section>
                            <Section title="Keywords">
                                {keywords.length > 0 && <ChipList items={keywords} />}
                            </Section>
                        </div>
                    </div>

                    {/* Side column */}
                    <aside className="flex flex-col gap-4">
                        {/* Apply card */}
                        <div className="flex flex-col gap-5 rounded-xl border border-gray-200 bg-gray-50 p-6 shadow-xs dark:border-gray-700 dark:bg-gray-900">
                            <div className="flex flex-col gap-1">
                                <span className="text-xs/5 font-medium text-gray-500 dark:text-gray-400">
                                    Applications close
                                </span>
                                <span className="text-2xl/8 font-semibold text-gray-900 dark:text-white">
                                    {formatDateOnly(opportunity.closing_date)}
                                </span>
                                <span
                                    className={[
                                        'flex items-center gap-1.5 text-sm/5 font-medium',
                                        isClosingSoon
                                            ? 'text-amber-700 dark:text-amber-400'
                                            : 'text-gray-500 dark:text-gray-400',
                                    ].join(' ')}
                                >
                                    <ClockIcon className="size-4 shrink-0" aria-hidden="true" />
                                    {describeDaysLeft(daysLeft, opportunity.status.value)}
                                </span>
                            </div>

                            {permissions?.can_apply && opportunity.apply_url && (
                                <ExternalLink
                                    href={opportunity.apply_url}
                                    className="inline-flex items-center justify-center gap-2 rounded-lg bg-firefly-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-firefly-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-firefly-500"
                                >
                                    Apply for opportunity
                                    <ArrowTopRightOnSquareIcon className="size-4" aria-hidden="true" />
                                </ExternalLink>
                            )}
                        </div>

                        {/* Details card */}
                        <div className="rounded-xl border border-gray-200 bg-gray-50 px-6 py-5 dark:border-gray-700 dark:bg-gray-900">
                            <h2 className="mb-2 text-sm/6 font-semibold text-gray-900 dark:text-white">Details</h2>
                            <dl className="flex flex-col">
                                <DetailRow label="Type">{opportunity.type.label}</DetailRow>
                                {opportunity.coverage_activity && (
                                    <DetailRow label="Coverage">{opportunity.coverage_activity.label}</DetailRow>
                                )}
                                {locations.length > 0 && (
                                    <DetailRow label="Location">{locations.join(', ')}</DetailRow>
                                )}
                                <DetailRow label="Status">{opportunity.status.label}</DetailRow>
                            </dl>
                        </div>

                        {/* Owner tools */}
                        {canManage && (
                            <div className="flex flex-col gap-3 rounded-xl border border-dashed border-gray-300 bg-white px-6 py-5 dark:border-gray-600 dark:bg-white/5">
                                <div className="flex flex-col gap-0.5">
                                    <span className="text-sm/6 font-semibold text-gray-900 dark:text-white">
                                        You manage this opportunity
                                    </span>
                                    <span className="text-xs/4 text-gray-500 dark:text-gray-400">
                                        Only you and portal administrators see this panel.
                                    </span>
                                </div>
                                <div className="flex gap-2">
                                    {permissions?.can_edit && (
                                        <Button
                                            outline
                                            href={route('opportunity.edit', { id: opportunity.id })}
                                            className="grow"
                                        >
                                            <PencilSquareIcon data-slot="icon" />
                                            Edit
                                        </Button>
                                    )}
                                    {permissions?.can_extend && (
                                        <Button outline onClick={() => setIsExtendDialogOpen(true)} className="grow">
                                            <CalendarDaysIcon data-slot="icon" />
                                            Extend deadline
                                        </Button>
                                    )}
                                </div>
                                {permissions?.can_close && (
                                    <Button outline onClick={handleClose}>
                                        <span className="text-red-700 dark:text-red-400">Close opportunity</span>
                                    </Button>
                                )}
                            </div>
                        )}
                    </aside>
                </div>
            </div>

            {permissions?.can_extend && (
                <ExtendOpportunityDialog
                    isOpen={isExtendDialogOpen}
                    onClose={() => setIsExtendDialogOpen(false)}
                    opportunity={opportunity}
                />
            )}
        </FrontendLayout>
    );
}
