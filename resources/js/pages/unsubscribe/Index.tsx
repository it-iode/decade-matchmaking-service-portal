import React, { useState } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import { FrontendLayout } from '@layouts/index';
import { Button } from '@ui/primitives/button';
import { Text } from '@ui/primitives/text';
import { Heading} from '@ui/primitives/heading';
import { PageProps, User } from '@/types';
import { EnvelopeIcon, CheckCircleIcon, XCircleIcon } from '@heroicons/react/24/outline';

interface UnsubscribePageProps extends PageProps {
    user: User;
    token?: string;
}

function UnsubscribePage({ user }: UnsubscribePageProps) {
    const [unsubscribed, setUnsubscribed] = useState(false);
    const { props } = usePage<UnsubscribePageProps>();

    const { post, processing, errors } = useForm({
        token: props.token || '',
    });

    const handleUnsubscribe = (e: React.FormEvent) => {
        e.preventDefault();

        post(route('unsubscribe.process',{user:user.id}), {
            onSuccess: () => {
                setUnsubscribed(true);
            },
            onError: () => {
                // Errors are handled by the errors object and flash messages
            }
        });
    };

    const handleGoHome = () => {
        window.location.href = route('user.home');
    };

    // If successfully unsubscribed, show success message
    if (unsubscribed) {
        return (
            <>
                <Head title="Unsubscribed Successfully" />
                <div className="max-w-2xl mx-auto text-center py-12">
                    <div className="mb-6 flex justify-center">
                        <div className="rounded-full bg-green-50 dark:bg-green-900 p-3">
                            <CheckCircleIcon className="h-12 w-12 text-green-600 dark:text-green-400" data-slot="icon" />
                        </div>
                    </div>

                    <Heading level={1} className="mb-4">
                        You've Been Unsubscribed
                    </Heading>

                    <Text className="mb-8 text-gray-500 dark:text-gray-400">
                        You will no longer receive email notifications about requests, opportunities.
                        If you change your mind, you can always re-enable notifications from your account settings.
                    </Text>

                    <div className="flex justify-center gap-4">
                        <Button onClick={handleGoHome} color="indigo">
                            Return to Home
                        </Button>
                    </div>
                </div>
            </>
        );
    }

    // Main unsubscribe confirmation view
    return (
        <>
            <Head title="Unsubscribe from Notifications" />
            <div className="max-w-2xl mx-auto text-center py-12">
                <div className="mb-6 flex justify-center">
                    <div className="rounded-full bg-amber-50 dark:bg-amber-900 p-3">
                        <EnvelopeIcon className="h-12 w-12 text-amber-600 dark:text-amber-400" data-slot="icon" />
                    </div>
                </div>

                <Heading level={1} className="mb-4">
                    Unsubscribe from Email Notifications
                </Heading>

                <div className="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-6">
                    <Text className="text-sm text-blue-800 dark:text-blue-200">
                        <strong>Account:</strong> {user.first_name} {user.last_name} ({user.email})
                    </Text>
                </div>

                <Text className="text-lg mb-6 text-gray-600 dark:text-gray-300">
                    Are you sure you want to unsubscribe from all email notifications?
                </Text>

                <Text className="mb-8 text-gray-500 dark:text-gray-400">
                    By unsubscribing, you will no longer receive:
                </Text>

                <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 mb-8 text-left max-w-md mx-auto">
                    <ul className="space-y-2 text-gray-600 dark:text-gray-300">
                        <li className="flex items-start">
                            <XCircleIcon className="h-5 w-5 text-gray-400 dark:text-gray-500 mr-2 mt-0.5 flex-shrink-0" data-slot="icon" />
                            <span>Notifications about new opportunities</span>
                        </li>
                        <li className="flex items-start">
                            <XCircleIcon className="h-5 w-5 text-gray-400 dark:text-gray-500 mr-2 mt-0.5 flex-shrink-0" data-slot="icon" />
                            <span>Notifications about new requests</span>
                        </li>
                    </ul>
                </div>

                {errors.token && (
                    <div className="mb-6 p-4 bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-lg">
                        <Text className="text-sm text-red-600 dark:text-red-200">
                            {errors.token}
                        </Text>
                    </div>
                )}

                <form onSubmit={handleUnsubscribe} className="flex justify-center gap-4">
                    <Button
                        type="button"
                        outline
                        onClick={handleGoHome}
                        disabled={processing}
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        color="red"
                        disabled={processing}
                    >
                        {processing ? 'Unsubscribing...' : 'Unsubscribe from All Notifications'}
                    </Button>
                </form>

                <Text className="mt-8 text-sm text-gray-500 dark:text-gray-400">
                    You can re-enable notifications at any time from your account settings.
                </Text>
            </div>
        </>
    );
}

// Wrap with FrontendLayout
UnsubscribePage.layout = (page: React.ReactNode) => (
    <FrontendLayout>{page}</FrontendLayout>
);

export default UnsubscribePage;