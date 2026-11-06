import React from 'react';
import { EnvelopeIcon } from '@heroicons/react/16/solid';
import { Button } from '@ui/primitives/button';
import { Input } from '@ui/primitives/input';
import { Field, Label } from '@ui/primitives/fieldset';
import { Heading } from '@ui/primitives/heading';
import { Text } from '@ui/primitives/text';
import { useUserInvitation } from '../hooks/use-user-invitation';
import { UserInvitationFormProps } from '../types/user.types';

// Sub-component: Loading Spinner
function LoadingSpinner() {
    return (
        <svg
            className="animate-spin -ml-1 mr-2 h-4 w-4"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            aria-hidden="true"
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
    );
}

// Sub-component: Alert Message
interface AlertMessageProps {
    type: 'success' | 'error';
    message: string;
    id?: string;
}

function AlertMessage({ type, message, id }: AlertMessageProps) {
    const isError = type === 'error';
    const baseClasses = 'p-3 rounded-lg';
    const colorClasses = isError
        ? 'bg-red-50 dark:bg-red-900/20'
        : 'bg-green-50 dark:bg-green-900/20';
    const textClasses = isError
        ? 'text-sm text-red-600 dark:text-red-400'
        : 'text-sm text-green-600 dark:text-green-400';

    return (
        <div
            id={id}
            role={isError ? 'alert' : 'status'}
            aria-live={isError ? 'assertive' : 'polite'}
            className={`${baseClasses} ${colorClasses}`}
        >
            <Text className={textClasses}>
                {message}
            </Text>
        </div>
    );
}

// Main component
export function UserInvitationForm({
    onSuccess,
    onError,
    className = '',
    title = 'Invite New User',
    description = 'Send an invitation email to add a new user to the Ocean Decade Portal.',
    inviteRoute = 'admin.users.invite',
    showCard = true
}: UserInvitationFormProps) {
    const {
        name,
        setName,
        email,
        setEmail,
        isInviting,
        error,
        success,
        handleSubmit
    } = useUserInvitation({
        inviteRoute,
        onSuccess,
        onError
    });

    const formContent = (
        <>
            <div className="mb-4">
                <Heading level={3} className="text-lg font-semibold">
                    {title}
                </Heading>
                <Text className="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                    {description}
                </Text>
            </div>

            <form
                onSubmit={handleSubmit}
                className="space-y-4"
                noValidate
                aria-busy={isInviting}
            >
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Field>
                        <Label htmlFor="invite-name">Full Name</Label>
                        <Input
                            id="invite-name"
                            type="text"
                            value={name}
                            onChange={(e) => setName(e.target.value)}
                            placeholder="John Doe"
                            required
                            disabled={isInviting}
                            autoComplete="name"
                            aria-describedby={error ? 'invite-error' : success ? 'invite-success' : undefined}
                            aria-invalid={error ? true : undefined}
                            className={error ? 'border-red-500' : ''}
                        />
                    </Field>
                    <Field>
                        <Label htmlFor="invite-email">Email Address</Label>
                        <Input
                            id="invite-email"
                            type="email"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            placeholder="user@example.com"
                            required
                            disabled={isInviting}
                            autoComplete="email"
                            aria-describedby={error ? 'invite-error' : success ? 'invite-success' : undefined}
                            aria-invalid={error ? true : undefined}
                            className={error ? 'border-red-500' : ''}
                        />
                    </Field>
                </div>
                <div className="flex justify-end">
                    <Button
                        type="submit"
                        color="firefly"
                        disabled={isInviting || !name.trim() || !email.trim()}
                        className="w-full sm:w-auto"
                    >
                        {isInviting ? (
                            <>
                                <LoadingSpinner />
                                Sending...
                            </>
                        ) : (
                            <>
                                <EnvelopeIcon data-slot="icon" />
                                Send Invitation
                            </>
                        )}
                    </Button>
                </div>

                {error && (
                    <AlertMessage
                        type="error"
                        message={error}
                        id="invite-error"
                    />
                )}

                {success && (
                    <AlertMessage
                        type="success"
                        message={success}
                        id="invite-success"
                    />
                )}
            </form>
        </>
    );

    if (showCard) {
        return (
            <div className={`bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 ${className}`}>
                {formContent}
            </div>
        );
    }

    return (
        <div className={className}>
            {formContent}
        </div>
    );
}
