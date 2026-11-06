import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { FrontendLayout } from '@layouts/index';
import { Button } from '@ui/primitives/button';
import { Text } from '@ui/primitives/text';
import { Heading } from '@ui/primitives/heading';
import { CheckCircleIcon, EnvelopeIcon, UserIcon, ClockIcon } from '@heroicons/react/24/outline';
import axios from 'axios';

interface InvitationAcceptProps {
    invitation: {
        email: string;
        inviter_name: string;
        expires_at: string;
    };
    token: string;
}

export default function InvitationAccept({ invitation, token }: Readonly<InvitationAcceptProps>) {
    const [isAccepting, setIsAccepting] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleAccept = async () => {
        setIsAccepting(true);
        setError(null);

        try {
            await axios.post(route('invitation.accept', { token }));
            router.visit(route('index'));
        } catch (err: unknown) {
            const error = err as { response?: { data?: { message?: string } } };
            setError(error.response?.data?.message || 'Failed to accept invitation. Please try again.');
            setIsAccepting(false);
        }
    };

    return (
        <FrontendLayout>
            <Head title="Accept Invitation" />

            <div className="max-w-lg mx-auto mt-8 p-6">
                {/* Icon */}
                <div className="flex justify-center mb-6">
                    <div className="rounded-full bg-firefly-50 dark:bg-firefly-900/20 p-4">
                        <CheckCircleIcon className="h-12 w-12 text-firefly-600 dark:text-firefly-400" />
                    </div>
                </div>

                {/* Header */}
                <div className="text-center mb-8">
                    <Heading level={1}>You're Invited!</Heading>
                    <Text className="mt-2 text-gray-600 dark:text-gray-400">
                        You've been invited to join the Ocean Decade Portal
                    </Text>
                </div>

                {/* Invitation Details Card */}
                <div className="bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 mb-6 space-y-4">
                    <div className="flex items-start gap-3">
                        <EnvelopeIcon className="h-5 w-5 text-zinc-500 dark:text-zinc-400 mt-0.5" />
                        <div className="flex-1">
                            <Text className="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Email Address
                            </Text>
                            <Text className="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                                {invitation.email}
                            </Text>
                        </div>
                    </div>

                    <div className="flex items-start gap-3">
                        <UserIcon className="h-5 w-5 text-zinc-500 dark:text-zinc-400 mt-0.5" />
                        <div className="flex-1">
                            <Text className="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Invited By
                            </Text>
                            <Text className="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                                Decade Coordination Unit
                            </Text>
                        </div>
                    </div>

                    <div className="flex items-start gap-3">
                        <ClockIcon className="h-5 w-5 text-zinc-500 dark:text-zinc-400 mt-0.5" />
                        <div className="flex-1">
                            <Text className="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Invitation Expires
                            </Text>
                            <Text className="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                                {invitation.expires_at}
                            </Text>
                        </div>
                    </div>
                </div>

                {/* Error Message */}
                {error && (
                    <div
                        role="alert"
                        aria-live="polite"
                        className="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg mb-6 transition-opacity duration-200"
                    >
                        <Text className="text-sm text-red-600 dark:text-red-400">
                            {error}
                        </Text>
                    </div>
                )}

                {/* Accept Button */}
                <Button
                    onClick={handleAccept}
                    disabled={isAccepting}
                    color="firefly"
                    className="w-full justify-center"
                >
                    {isAccepting ? (
                        <>
                            <svg
                                className="animate-spin -ml-1 mr-2 h-4 w-4"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    className="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    strokeWidth="4"
                                />
                                <path
                                    className="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                />
                            </svg>
                            Accepting...
                        </>
                    ) : (
                        <>
                            <CheckCircleIcon data-slot="icon" />
                            Accept Invitation
                        </>
                    )}
                </Button>

                {/* Footer */}
                <div className="mt-8 text-center">
                    <Text className="text-sm text-zinc-500 dark:text-zinc-400">
                        By accepting, you'll create an account with the email address above.
                    </Text>
                </div>
            </div>
        </FrontendLayout>
    );
}
