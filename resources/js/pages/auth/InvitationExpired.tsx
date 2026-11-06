import { Head } from '@inertiajs/react';
import { FrontendLayout } from '@layouts/index';
import { Button } from '@ui/primitives/button';
import { Link } from '@ui/primitives/link';
import { Text } from '@ui/primitives/text';
import { Heading } from '@ui/primitives/heading';
import { XCircleIcon, ArrowRightIcon } from '@heroicons/react/24/outline';

interface InvitationExpiredProps {
    message?: string;
}

export default function InvitationExpired({ message }: Readonly<InvitationExpiredProps>) {
    const displayMessage = message || 'This invitation link is invalid or has expired.';

    return (
        <FrontendLayout>
            <Head title="Invitation Invalid" />

            <div className="max-w-lg mx-auto mt-8 p-6">
                {/* Error Icon */}
                <div className="flex justify-center mb-6">
                    <div className="rounded-full bg-red-50 dark:bg-red-900/20 p-4">
                        <XCircleIcon className="h-12 w-12 text-red-600 dark:text-red-400" />
                    </div>
                </div>

                {/* Header */}
                <div className="text-center mb-8">
                    <Heading level={1}>Invitation Invalid</Heading>
                    <Text className="mt-2 text-gray-600 dark:text-gray-400">
                        {displayMessage}
                    </Text>
                </div>

                {/* Help Card */}
                <div className="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 mb-6">
                    <Text className="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">
                        What can you do?
                    </Text>
                    <ul className="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                        <li className="flex items-start gap-2">
                            <span className="text-firefly-600 dark:text-firefly-400">•</span>
                            <span>Contact the person who invited you to request a new invitation</span>
                        </li>
                        <li className="flex items-start gap-2">
                            <span className="text-firefly-600 dark:text-firefly-400">•</span>
                            <span>If you already have an account, sign in with your existing credentials</span>
                        </li>
                    </ul>
                </div>

                {/* Actions */}
                <div className="space-y-3">
                    <Button
                        href={route('sign.in')}
                        color="firefly"
                        className="w-full justify-center"
                    >
                        Go to Sign In
                        <ArrowRightIcon data-slot="icon" />
                    </Button>

                    <div className="text-center">
                        <Link
                            href={route('index')}
                            className="text-sm text-zinc-600 hover:text-zinc-500 dark:text-zinc-400"
                        >
                            Return to Home
                        </Link>
                    </div>
                </div>
            </div>
        </FrontendLayout>
    );
}
