import React, { useEffect, useMemo, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { FrontendLayout } from '@layouts/index';
import { Heading } from '@ui/primitives/heading';
import { Text } from '@ui/primitives/text';
import { Button } from '@ui/primitives/button';
import { Switch, SwitchField } from '@ui/primitives/switch';
import { Label } from '@ui/primitives/fieldset';
import {
    NotificationEntityType,
    NotificationOption,
    NotificationPreferencesPageProps,
    NotificationSettings,
} from '@/types';
import {
    BriefcaseIcon,
    CheckCircleIcon,
    ExclamationCircleIcon,
    ExclamationTriangleIcon,
    InboxArrowDownIcon,
} from '@heroicons/react/16/solid';

/**
 * Build the per-key pending guard token for an in-flight toggle request.
 */
function pendingKey(entityType: NotificationEntityType, value: string): string {
    return `${entityType}:${value}`;
}

/**
 * A single togglable notification row built on Catalyst's SwitchField. The
 * Switch is the sole interactive control and the sole state indicator; the
 * Label is natively clickable via the surrounding HeadlessUI Field.
 */
function NotificationRow({
    option,
    entityType,
    pending,
    onToggle,
}: Readonly<{
    option: NotificationOption;
    entityType: NotificationEntityType;
    pending: boolean;
    onToggle: (entityType: NotificationEntityType, value: string) => void;
}>) {
    return (
        <SwitchField className="px-3 py-2.5 transition hover:bg-zinc-50 dark:hover:bg-white/5">
            <Label
                className={
                    'text-sm font-medium transition-colors ' +
                    (option.enabled
                        ? 'text-zinc-900 dark:text-zinc-100'
                        : 'text-zinc-400 dark:text-zinc-500')
                }
            >
                {option.label}
            </Label>
            <Switch
                color="indigo"
                checked={option.enabled}
                disabled={pending}
                onChange={() => onToggle(entityType, option.value)}
            />
        </SwitchField>
    );
}

/**
 * A section card grouping a flat, divided list of notification options for one
 * entity type. The header shows an opt-out-aware status rather than a counter.
 */
function NotificationCard({
    title,
    description,
    icon,
    options,
    entityType,
    pending,
    onToggle,
}: Readonly<{
    title: string;
    description: string;
    icon: React.ReactNode;
    options: NotificationOption[];
    entityType: NotificationEntityType;
    pending: ReadonlySet<string>;
    onToggle: (entityType: NotificationEntityType, value: string) => void;
}>) {
    const pausedCount = options.filter((option) => !option.enabled).length;

    return (
        <section className="flex flex-col rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-white/10 dark:bg-zinc-900">
            <header className="flex items-start justify-between gap-4 border-b border-zinc-200 px-5 py-4 dark:border-white/10">
                <div className="flex items-start gap-3">
                    <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-600 dark:bg-white/5 dark:text-zinc-400">
                        {icon}
                    </span>
                    <div>
                        <Heading level={2} className="text-base/6 font-semibold">
                            {title}
                        </Heading>
                        <Text className="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                            {description}
                        </Text>
                    </div>
                </div>
                <span
                    className={
                        'shrink-0 text-xs font-medium ' +
                        (pausedCount > 0
                            ? 'text-amber-600 dark:text-amber-400'
                            : 'text-zinc-500 dark:text-zinc-400')
                    }
                >
                    {pausedCount > 0 ? `${pausedCount} paused` : 'All on'}
                </span>
            </header>

            {options.length === 0 ? (
                <div className="p-5">
                    <Text className="text-sm text-zinc-500 dark:text-zinc-400">
                        No notification types configured yet. Contact your administrator if
                        you expect to see options here.
                    </Text>
                </div>
            ) : (
                <div className="divide-y divide-zinc-100 px-2 py-2 dark:divide-white/10">
                    {options.map((option) => (
                        <NotificationRow
                            key={option.value}
                            option={option}
                            entityType={entityType}
                            pending={pending.has(pendingKey(entityType, option.value))}
                            onToggle={onToggle}
                        />
                    ))}
                </div>
            )}
        </section>
    );
}

export default function List({ settings }: Readonly<NotificationPreferencesPageProps>) {
    const [local, setLocal] = useState<NotificationSettings>(settings);
    const [pending, setPending] = useState<ReadonlySet<string>>(new Set());
    const [showSaved, setShowSaved] = useState(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);
    const savedTimeout = useRef<ReturnType<typeof setTimeout> | null>(null);
    const errorTimeout = useRef<ReturnType<typeof setTimeout> | null>(null);

    // Resync local state whenever the server sends a fresh payload.
    useEffect(() => setLocal(settings), [settings]);

    useEffect(() => {
        return () => {
            if (savedTimeout.current) {
                clearTimeout(savedTimeout.current);
            }
            if (errorTimeout.current) {
                clearTimeout(errorTimeout.current);
            }
        };
    }, []);

    const flashSaved = () => {
        setErrorMessage(null);
        setShowSaved(true);
        if (savedTimeout.current) {
            clearTimeout(savedTimeout.current);
        }
        savedTimeout.current = setTimeout(() => setShowSaved(false), 2000);
    };

    const flashError = () => {
        setShowSaved(false);
        setErrorMessage("Couldn't save your change. Please try again.");
        if (errorTimeout.current) {
            clearTimeout(errorTimeout.current);
        }
        errorTimeout.current = setTimeout(() => setErrorMessage(null), 5000);
    };

    /**
     * Flip a single option in local state. Pure helper reused for the
     * optimistic update and its revert-on-error.
     */
    const flipOption = (
        current: NotificationSettings,
        entityType: NotificationEntityType,
        value: string
    ): NotificationSettings => {
        const flip = (options: NotificationOption[]): NotificationOption[] =>
            options.map((option) =>
                option.value === value
                    ? { ...option, enabled: !option.enabled }
                    : option
            );

        if (entityType === 'opportunity') {
            return { ...current, opportunity: flip(current.opportunity) };
        }

        return {
            ...current,
            request: current.request === null ? null : flip(current.request),
        };
    };

    const handleToggle = (entityType: NotificationEntityType, value: string) => {
        const key = pendingKey(entityType, value);

        // Derive the desired final state from the live local state, then flip
        // optimistically. We send the TARGET state (idempotent) so the backend
        // SETs rather than flips — this makes the revert-on-error exact and
        // immune to rapid-click races.
        const sourceOptions =
            entityType === 'opportunity' ? local.opportunity : local.request;
        const currentOption = sourceOptions?.find((o) => o.value === value);
        if (!currentOption) {
            return;
        }
        const nextEnabled = !currentOption.enabled;

        // Optimistic flip.
        setLocal((current) => flipOption(current, entityType, value));
        setPending((current) => new Set(current).add(key));

        router.put(
            route('notification.preferences.toggle'),
            { entity_type: entityType, attribute_value: value, enabled: nextEnabled },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: flashSaved,
                onError: () => {
                    // Revert the optimistic flip and surface feedback. Because
                    // we sent the target state, restoring the prior local value
                    // is exact regardless of in-flight requests.
                    setLocal((current) => flipOption(current, entityType, value));
                    flashError();
                },
                onFinish: () => {
                    setPending((current) => {
                        const next = new Set(current);
                        next.delete(key);
                        return next;
                    });
                },
            }
        );
    };

    const handleResubscribe = () => {
        // Optimistically re-enable the master switch.
        setLocal((current) => ({ ...current, master_enabled: true }));

        router.put(
            route('notification.preferences.resubscribe'),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: flashSaved,
                onError: () => {
                    setLocal((current) => ({ ...current, master_enabled: false }));
                    flashError();
                },
            }
        );
    };

    const isUnsubscribed = !local.master_enabled;
    const hasRequestCard = local.request !== null;

    const cardsLayout = useMemo(
        () =>
            hasRequestCard
                ? 'grid grid-cols-1 gap-6 lg:grid-cols-2'
                : 'grid max-w-2xl grid-cols-1 gap-6',
        [hasRequestCard]
    );

    return (
        <FrontendLayout>
            <Head title="Notification Preferences" />

            <div className="space-y-8">
                {/* Header */}
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div className="max-w-2xl">
                        <Heading level={1}>Notification preferences</Heading>
                        <Text className="mt-2 text-zinc-600 dark:text-zinc-400">
                            By default, you receive email updates about new requests and
                            opportunities that match these types. Turn any type off to stop
                            receiving those emails. Changes are saved automatically.
                        </Text>
                    </div>

                    <div
                        aria-live="polite"
                        className="flex shrink-0 items-center sm:pt-1"
                    >
                        {errorMessage ? (
                            <span
                                role="alert"
                                className="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1 text-sm font-medium text-red-700 dark:bg-red-500/10 dark:text-red-300"
                            >
                                <ExclamationCircleIcon
                                    data-slot="icon"
                                    aria-hidden="true"
                                    className="size-4"
                                />
                                {errorMessage}
                            </span>
                        ) : (
                            <span
                                className={
                                    'inline-flex items-center gap-1.5 rounded-full bg-green-50 px-3 py-1 text-sm font-medium text-green-700 transition-opacity duration-300 dark:bg-green-500/10 dark:text-green-300 ' +
                                    (showSaved ? 'opacity-100' : 'pointer-events-none opacity-0')
                                }
                            >
                                <CheckCircleIcon data-slot="icon" aria-hidden="true" className="size-4" />
                                Saved
                            </span>
                        )}
                    </div>
                </div>

                {/* Globally-unsubscribed banner */}
                {isUnsubscribed && (
                    <div className="flex flex-col gap-4 rounded-lg border border-amber-300 bg-amber-50 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-amber-500/30 dark:bg-amber-500/10">
                        <div className="flex items-start gap-3">
                            <ExclamationTriangleIcon
                                data-slot="icon"
                                aria-hidden="true"
                                className="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-400"
                            />
                            <div>
                                <Heading
                                    level={2}
                                    className="text-sm font-semibold text-amber-900 dark:text-amber-200"
                                >
                                    You&rsquo;ve unsubscribed from all emails
                                </Heading>
                                <Text className="mt-1 text-sm text-amber-800 dark:text-amber-200/80">
                                    Your preferences below are saved, but no emails will be
                                    sent until you resubscribe.
                                </Text>
                            </div>
                        </div>
                        <div className="shrink-0">
                            <Button color="amber" onClick={handleResubscribe}>
                                Resubscribe
                            </Button>
                        </div>
                    </div>
                )}

                {/* Cards */}
                <div
                    className={
                        cardsLayout +
                        (isUnsubscribed ? ' pointer-events-none opacity-60' : '')
                    }
                >
                    <NotificationCard
                        title="Opportunity notifications"
                        description="New partner opportunities by type."
                        icon={<BriefcaseIcon data-slot="icon" className="size-5" />}
                        options={local.opportunity}
                        entityType="opportunity"
                        pending={pending}
                        onToggle={handleToggle}
                    />

                    {local.request !== null && (
                        <NotificationCard
                            title="Request notifications"
                            description="New capacity development requests by Decade Challenge."
                            icon={<InboxArrowDownIcon data-slot="icon" className="size-5" />}
                            options={local.request}
                            entityType="request"
                            pending={pending}
                            onToggle={handleToggle}
                        />
                    )}
                </div>
            </div>
        </FrontendLayout>
    );
}
