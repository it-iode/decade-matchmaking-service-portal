import React, {useState} from 'react';
import {router, Head, Link} from '@inertiajs/react';
import { FrontendLayout } from '@layouts/index';
import {Heading} from '@ui/primitives/heading';
import {Button} from '@ui/primitives/button';
import {Text} from '@ui/primitives/text';
import {Divider} from '@ui/primitives/divider';
import {Table, TableHead, TableBody, TableRow, TableHeader, TableCell} from '@ui/primitives/table';
import {Badge} from '@ui/primitives/badge';
import {Dialog, DialogActions, DialogBody, DialogDescription, DialogTitle} from '@ui/primitives/dialog';
import {RequestSubscription, PageProps} from '@/types';
import {BellSlashIcon, EyeIcon} from '@heroicons/react/16/solid';

interface SubscriptionsIndexProps extends PageProps {
    subscriptions: {
        data: RequestSubscription[];
        links: any;
        meta: any;
    };
}

export default function SubscriptionsIndex({subscriptions}: SubscriptionsIndexProps) {
    const [showUnsubscribeDialog, setShowUnsubscribeDialog] = useState(false);
    const [subscriptionToRemove, setSubscriptionToRemove] = useState<RequestSubscription | null>(null);
    const [isUnsubscribing, setIsUnsubscribing] = useState(false);

    const handleUnsubscribe = (subscription: RequestSubscription) => {
        setSubscriptionToRemove(subscription);
        setShowUnsubscribeDialog(true);
    };

    const confirmUnsubscribe = async () => {
        if (!subscriptionToRemove) return;

        setIsUnsubscribing(true);

        try {
            const response = await fetch(route('user.subscriptions.unsubscribe'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({request_id: subscriptionToRemove.request_id}),
            });

            const data = await response.json();

            if (data.success) {
                // Reload the page to refresh the subscriptions list
                router.reload();
            } else {
                console.error('Failed to unsubscribe:', data.message);
            }
        } catch (error) {
            console.error('Unsubscribe error:', error);
        } finally {
            setIsUnsubscribing(false);
            setShowUnsubscribeDialog(false);
            setSubscriptionToRemove(null);
        }
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    const getStatusBadgeColor = (statusCode: string) => {
        switch (statusCode?.toLowerCase()) {
            case 'draft':
                return 'zinc';
            case 'submitted':
                return 'blue';
            case 'under_review':
                return 'amber';
            case 'approved':
                return 'green';
            case 'rejected':
                return 'red';
            case 'matched':
                return 'purple';
            case 'completed':
                return 'emerald';
            default:
                return 'zinc';
        }
    };

    return (
        <FrontendLayout>
            <Head title="My Subscriptions"/>

            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
                <div className="sm:flex sm:items-center">
                    <div className="sm:flex-auto">
                        <Heading>My Subscriptions</Heading>
                        <Text className="mt-2">
                            Manage your subscriptions to capacity development requests. You'll receive updates when
                            subscribed requests are modified.
                        </Text>
                    </div>
                </div>

                <Divider className="my-6"/>

                {subscriptions.data.length === 0 ? (
                    <div className="text-center py-12">
                        <BellSlashIcon className="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500"/>
                        <Heading level={3} className="mt-4 text-gray-900 dark:text-gray-100">No subscriptions</Heading>
                        <Text className="mt-2 text-gray-600 dark:text-gray-400">
                            You haven't subscribed to any requests yet. Visit request details to subscribe.
                        </Text>
                        <div className="mt-6">
                            <Button>
                                <Link href={route('request.list')}>
                                    Browse Requests
                                </Link>
                            </Button>
                        </div>
                    </div>
                ) : (
                    <>
                        <Table className="[--gutter:--spacing(6)] sm:[--gutter:--spacing(8)]">
                            <TableHead>
                                <TableRow>
                                    <TableHeader>Request</TableHeader>
                                    <TableHeader>Status</TableHeader>
                                    <TableHeader>Subscribed</TableHeader>
                                    <TableHeader>Subscription Type</TableHeader>
                                    <TableHeader className="text-right">Actions</TableHeader>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {subscriptions.data.map((subscription) => (
                                    <TableRow key={subscription.id}>
                                        <TableCell>
                                            <div>
                                                <Text className="font-medium text-gray-900 dark:text-gray-100">
                                                    {subscription.request?.detail.capacity_development_title || `Request #${subscription.request_id}`}
                                                </Text>
                                                <Text className="text-sm text-gray-500 dark:text-gray-400">
                                                    by {subscription.request?.user.name || 'Unknown'}
                                                </Text>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                color={getStatusBadgeColor(subscription.request?.status?.status_code || '')}>
                                                {subscription.request?.status?.status_label || 'Unknown'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <Text className="text-sm text-gray-900 dark:text-gray-100">
                                                {formatDate(subscription.created_at)}
                                            </Text>
                                        </TableCell>
                                        <TableCell>
                                            {subscription.subscribed_by_admin ? (
                                                <Badge color="purple">Admin Created</Badge>
                                            ) : (
                                                <Badge color="blue">Self Subscribed</Badge>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    href={route('request.show', subscription.request_id)}
                                                >
                                                    <EyeIcon data-slot="icon"/>
                                                    View
                                                </Button>
                                                {!subscription.subscribed_by_admin && (
                                                    <Button
                                                        color="red"
                                                        onClick={() => handleUnsubscribe(subscription)}
                                                    >
                                                        <BellSlashIcon data-slot="icon"/>
                                                        Unsubscribe
                                                    </Button>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        {/* Pagination */}
                        {subscriptions.links && (
                            <div className="mt-6 flex justify-center">
                                {/* Add pagination component here if needed */}
                            </div>
                        )}
                    </>
                )}

                {/* Unsubscribe Confirmation Dialog */}
                <Dialog open={showUnsubscribeDialog} onClose={() => setShowUnsubscribeDialog(false)}>
                    <DialogTitle>Confirm Unsubscribe</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to unsubscribe from this request? You will no longer receive updates about
                        changes to this request.
                    </DialogDescription>
                    <DialogBody>
                        {subscriptionToRemove && (
                            <Text>
                                <strong>Request:</strong> {subscriptionToRemove.request?.detail.capacity_development_title || `Request #${subscriptionToRemove.request_id}`}
                            </Text>
                        )}
                    </DialogBody>
                    <DialogActions>
                        <Button
                            plain
                            onClick={() => setShowUnsubscribeDialog(false)}
                            disabled={isUnsubscribing}
                        >
                            Cancel
                        </Button>
                        <Button
                            color="red"
                            onClick={confirmUnsubscribe}
                            disabled={isUnsubscribing}
                        >
                            {isUnsubscribing ? 'Unsubscribing...' : 'Unsubscribe'}
                        </Button>
                    </DialogActions>
                </Dialog>
            </div>
        </FrontendLayout>
    );
}
